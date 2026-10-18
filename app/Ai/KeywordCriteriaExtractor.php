<?php

namespace App\Ai;

use App\Enums\ProjectCategory;
use App\Enums\SkillCategory;
use App\Models\Country;
use App\Models\Skill;
use Illuminate\Support\Str;

/**
 * Local fallback for AfriCommit AI: recognises skills, countries and project types by keywords.
 * Works offline and without an API key, so the demo never breaks.
 */
class KeywordCriteriaExtractor implements CriteriaExtractor
{
    /** Extra words pointing to a skill slug. */
    private const SKILL_ALIASES = [
        'react-native' => ['react native', 'reactnative'],
        'node-js' => ['node', 'nodejs', 'node js'],
        'javascript' => ['js'],
        'typescript' => ['ts'],
        'ui-ux' => ['ui', 'ux', 'ui ux', 'designer', 'design ui', 'maquette', 'maquettes'],
        'intelligence-artificielle' => ['ia', 'ai'],
        'machine-learning' => ['ml'],
        'postgresql' => ['postgres'],
        'devops' => ['devops', 'deploiement', 'infrastructure'],
        'gestion-de-projet' => ['chef de projet', 'product manager', 'gestion de projet'],
    ];

    /** Words pointing to a broad skill area ("un développeur mobile"). */
    private const CATEGORY_KEYWORDS = [
        'mobile' => ['mobile', 'android', 'ios', 'application mobile', 'app mobile'],
        'frontend' => ['frontend', 'front-end', 'front end', 'interface web'],
        'backend' => ['backend', 'back-end', 'back end', 'api', 'serveur'],
        'design' => ['design', 'graphiste'],
        'data' => ['data', 'donnees', 'intelligence artificielle'],
        'devops' => ['cloud', 'hebergement'],
    ];

    /** Words pointing to a project type. */
    private const PROJECT_KEYWORDS = [
        'logistics' => ['livraison', 'logistique', 'transport', 'livreur', 'colis', 'delivery'],
        'fintech' => ['fintech', 'paiement', 'banque', 'mobile money', 'portefeuille', 'transfert', 'credit', 'microfinance'],
        'healthtech' => ['sante', 'medical', 'hopital', 'clinique', 'patient', 'pharmacie', 'healthtech'],
        'edtech' => ['education', 'ecole', 'cours', 'eleves', 'etudiants', 'apprentissage', 'edtech', 'formation'],
        'agritech' => ['agriculture', 'agricole', 'agriculteurs', 'ferme', 'recolte', 'agritech'],
        'ecommerce' => ['e-commerce', 'ecommerce', 'boutique', 'vente en ligne', 'marketplace', 'commerce en ligne'],
        'social' => ['ong', 'impact social', 'association', 'solidarite', 'humanitaire'],
        'govtech' => ['gouvernement', 'administration', 'mairie', 'etat civil', 'govtech', 'citoyens'],
    ];

    /** Demonyms and common spellings mapped to country codes. */
    private const COUNTRY_ALIASES = [
        'GN' => ['guinee conakry', 'guineen', 'guineenne', 'conakry'],
        'SN' => ['senegalais', 'senegalaise', 'dakar'],
        'CI' => ['ivoirien', 'ivoirienne', 'cote divoire', 'abidjan'],
        'ML' => ['malien', 'malienne', 'bamako'],
        'CM' => ['camerounais', 'camerounaise', 'douala', 'yaounde'],
        'NG' => ['nigerian', 'nigeriane', 'lagos', 'abuja'],
        'GH' => ['ghaneen', 'ghaneenne', 'accra'],
        'KE' => ['kenyan', 'kenyane', 'nairobi'],
        'MA' => ['marocain', 'marocaine', 'casablanca', 'rabat'],
        'BJ' => ['beninois', 'beninoise', 'cotonou'],
        'TG' => ['togolais', 'togolaise', 'lome'],
        'BF' => ['burkinabe', 'ouagadougou'],
        'RW' => ['rwandais', 'rwandaise', 'kigali'],
        'TN' => ['tunisien', 'tunisienne', 'tunis'],
        'DZ' => ['algerien', 'algerienne', 'alger'],
        'EG' => ['egyptien', 'egyptienne', 'le caire'],
        'ZA' => ['sud-africain', 'sud africain', 'johannesburg'],
        'CD' => ['rdc', 'kinshasa', 'congolais'],
    ];

    public function extract(string $query): SearchCriteria
    {
        $text = ' '.$this->normalize($query).' ';

        return new SearchCriteria(
            skills: $this->findSkills($text),
            skillCategories: $this->findCategories($text),
            country: $this->findCountry($text),
            projectType: $this->findProjectType($text),
        );
    }

    /**
     * @return list<string>
     */
    private function findSkills(string $text): array
    {
        $found = [];

        foreach (Skill::all(['name', 'slug']) as $skill) {
            $words = array_merge([$skill->name, str_replace('-', ' ', $skill->slug)], self::SKILL_ALIASES[$skill->slug] ?? []);

            if ($this->containsAny($text, $words)) {
                $found[] = $skill->slug;
            }
        }

        // "React Native" also contains "React": keep only the more precise skill.
        if (in_array('react-native', $found, true)) {
            $found = array_values(array_diff($found, ['react']));
        }

        return $found;
    }

    /**
     * @return list<SkillCategory>
     */
    private function findCategories(string $text): array
    {
        $found = [];

        foreach (self::CATEGORY_KEYWORDS as $category => $words) {
            if ($this->containsAny($text, $words)) {
                $found[] = SkillCategory::from($category);
            }
        }

        return $found;
    }

    private function findCountry(string $text): ?string
    {
        // Longest names first, so "Guinée-Bissau" wins over "Guinée".
        $countries = Country::all(['code', 'name', 'slug'])->sortByDesc(fn (Country $country) => mb_strlen($country->name));

        foreach ($countries as $country) {
            $words = array_merge([$country->name, $country->slug], self::COUNTRY_ALIASES[$country->code] ?? []);

            if ($this->containsAny($text, $words)) {
                return $country->slug;
            }
        }

        return null;
    }

    private function findProjectType(string $text): ?ProjectCategory
    {
        foreach (self::PROJECT_KEYWORDS as $category => $words) {
            if ($this->containsAny($text, $words)) {
                return ProjectCategory::from($category);
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $words
     */
    private function containsAny(string $text, array $words): bool
    {
        foreach ($words as $word) {
            $word = $this->normalize($word);

            if ($word !== '' && str_contains($text, ' '.$word.' ')) {
                return true;
            }
        }

        return false;
    }

    /**
     * "Développeur Flutter en Guinée !" → "developpeur flutter en guinee".
     */
    private function normalize(string $text): string
    {
        $text = Str::lower(Str::ascii($text));
        $text = str_replace(["'", '’'], ' ', $text);
        $text = preg_replace('/[^a-z0-9.#+\- ]+/', ' ', $text);

        // Keep dots and hyphens inside words ("node.js", "react-native") but not around them.
        $text = preg_replace('/(?<![a-z0-9])[.\-]|[.\-](?![a-z0-9])/', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}

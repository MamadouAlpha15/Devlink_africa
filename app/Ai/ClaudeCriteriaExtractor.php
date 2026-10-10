<?php

namespace App\Ai;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Enums\ProjectCategory;
use App\Enums\SkillCategory;
use App\Models\Country;
use App\Models\Skill;
use Illuminate\Support\Collection;

/**
 * DevLink AI with Claude: turns a request written in natural language into search criteria.
 * Structured outputs restrict the answer to the skills, countries and categories of the platform.
 */
class ClaudeCriteriaExtractor implements CriteriaExtractor
{
    private const NONE = 'aucun';

    public function __construct(
        private Client $client,
        private string $model,
    ) {}

    public function extract(string $query): SearchCriteria
    {
        $skills = Skill::orderBy('name')->get(['name', 'slug', 'category']);
        $countries = Country::orderBy('name')->get(['name', 'slug']);

        try {
            $message = $this->client->beta->messages->create(
                // If the model declines for policy reasons, the API retries on its default fallback model.
                betas: ['server-side-fallback-2026-07-01'],
                fallbacks: 'default',
                model: $this->model,
                maxTokens: 4096,
                system: $this->systemPrompt($skills, $countries),
                messages: [['role' => 'user', 'content' => $query]],
                outputConfig: [
                    // Simple extraction: low effort keeps it fast and cheap.
                    'effort' => 'low',
                    'format' => ['type' => 'json_schema', 'schema' => $this->schema($skills, $countries)],
                ],
            );
        } catch (AuthenticationException $e) {
            throw new CriteriaExtractionFailed('Invalid Anthropic API key.', previous: $e);
        } catch (RateLimitException $e) {
            throw new CriteriaExtractionFailed('Anthropic rate limit reached.', previous: $e);
        } catch (APIConnectionException $e) {
            throw new CriteriaExtractionFailed('Cannot reach the Anthropic API.', previous: $e);
        } catch (APIException $e) {
            throw new CriteriaExtractionFailed('Anthropic API error: '.$e->getMessage(), previous: $e);
        }

        if ($message->stopReason !== 'end_turn') {
            throw new CriteriaExtractionFailed('Unexpected stop reason: '.$message->stopReason);
        }

        return $this->toCriteria($this->firstJsonBlock($message->content), $skills, $countries);
    }

    private function systemPrompt(Collection $skills, Collection $countries): string
    {
        return <<<PROMPT
        Tu es DevLink AI, l'assistant de recrutement de la plateforme DevLink Africa.
        Un membre décrit en langage naturel le profil qu'il recherche. Extrais les critères de recherche :

        - skills : les compétences explicitement demandées, parmi la liste autorisée (utilise le slug).
          Déduis la compétence d'un outil proche seulement si c'est évident (« app Flutter » → flutter).
        - skill_categories : le domaine quand aucune compétence précise n'est nommée
          (« un développeur mobile » → mobile, « un designer » → design). Liste vide sinon.
        - country : le pays africain mentionné (slug), y compris via une ville ou un gentilé
          (« Conakry » → guinee, « un Sénégalais » → senegal). « aucun » si aucun pays.
        - project_type : le type de projet visé (« application de livraison » → logistics). « aucun » si non précisé.
        - summary : une phrase courte en français qui reformule la recherche.

        N'invente rien : si un critère n'est pas mentionné, laisse-le vide.

        Compétences (slug : nom) :
        {$skills->map(fn (Skill $skill) => "{$skill->slug} : {$skill->name}")->join("\n")}

        Pays (slug : nom) :
        {$countries->map(fn (Country $country) => "{$country->slug} : {$country->name}")->join("\n")}
        PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(Collection $skills, Collection $countries): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'skills' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => $skills->pluck('slug')->all()]],
                'skill_categories' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => array_column(SkillCategory::cases(), 'value')]],
                'country' => ['type' => 'string', 'enum' => [...$countries->pluck('slug')->all(), self::NONE]],
                'project_type' => ['type' => 'string', 'enum' => [...array_column(ProjectCategory::cases(), 'value'), self::NONE]],
                'summary' => ['type' => 'string'],
            ],
            'required' => ['skills', 'skill_categories', 'country', 'project_type', 'summary'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @param  iterable<object>  $content
     * @return array<string, mixed>
     */
    private function firstJsonBlock(iterable $content): array
    {
        foreach ($content as $block) {
            if ($block->type === 'text') {
                $data = json_decode($block->text, true);

                if (is_array($data)) {
                    return $data;
                }
            }
        }

        throw new CriteriaExtractionFailed('No JSON answer in the response.');
    }

    /**
     * Keep only known values, even though the schema already restricts them.
     *
     * @param  array<string, mixed>  $data
     */
    private function toCriteria(array $data, Collection $skills, Collection $countries): SearchCriteria
    {
        $country = $data['country'] ?? self::NONE;

        return new SearchCriteria(
            skills: array_values(array_intersect((array) ($data['skills'] ?? []), $skills->pluck('slug')->all())),
            skillCategories: array_values(array_filter(array_map(
                fn ($value) => is_string($value) ? SkillCategory::tryFrom($value) : null,
                (array) ($data['skill_categories'] ?? []),
            ))),
            country: $countries->contains('slug', $country) ? $country : null,
            projectType: is_string($data['project_type'] ?? null) ? ProjectCategory::tryFrom($data['project_type']) : null,
            summary: is_string($data['summary'] ?? null) ? $data['summary'] : null,
        );
    }
}

<?php

namespace App\Ai;

use App\Enums\ProjectCategory;
use App\Enums\SkillCategory;

/**
 * What AfriCommit AI understood from a request such as
 * "Je cherche un développeur Flutter en Guinée pour une application de livraison".
 */
final class SearchCriteria
{
    /**
     * @param  list<string>  $skills  Skill slugs ("flutter")
     * @param  list<SkillCategory>  $skillCategories  Broad areas when no precise skill is named ("mobile")
     */
    public function __construct(
        public readonly array $skills = [],
        public readonly array $skillCategories = [],
        public readonly ?string $country = null,
        public readonly ?ProjectCategory $projectType = null,
        public readonly ?string $summary = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->skills === [] && $this->skillCategories === [] && $this->country === null && $this->projectType === null;
    }

    /**
     * @return array{skills: list<string>, skill_categories: list<string>, country: ?string, project_type: ?string, summary: ?string}
     */
    public function toArray(): array
    {
        return [
            'skills' => $this->skills,
            'skill_categories' => array_map(fn (SkillCategory $category) => $category->value, $this->skillCategories),
            'country' => $this->country,
            'project_type' => $this->projectType?->value,
            'summary' => $this->summary,
        ];
    }
}

<?php

namespace App\Search;

use App\Enums\ProjectCategory;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Project list: /projects?q=livraison&category=logistics&country=guinee&status=recruiting&skill=flutter
 */
class ProjectSearch
{
    public const SORTS = [
        'recent' => 'Plus récents',
        'team' => 'Plus grandes équipes',
        'title' => 'Titre (A → Z)',
    ];

    private function __construct(
        public readonly ?string $q = null,
        public readonly ?ProjectCategory $category = null,
        public readonly ?string $country = null,
        public readonly ?ProjectStatus $status = null,
        public readonly ?string $skill = null,
        public readonly string $sort = 'recent',
    ) {}

    /**
     * Build the search from the query string. Invalid values are ignored rather than rejected.
     */
    public static function fromRequest(Request $request): self
    {
        $text = function (string $key) use ($request): ?string {
            $value = $request->query($key);

            return is_string($value) && trim($value) !== '' ? Str::limit(trim($value), 100, '') : null;
        };

        $sort = $text('sort');

        return new self(
            q: $text('q'),
            category: ProjectCategory::tryFrom((string) $text('category')),
            country: $text('country'),
            status: ProjectStatus::tryFrom((string) $text('status')),
            skill: $text('skill'),
            sort: array_key_exists((string) $sort, self::SORTS) ? $sort : 'recent',
        );
    }

    /**
     * Active filters as plain strings, to refill the search form.
     *
     * @return array<string, string|null>
     */
    public function input(): array
    {
        return [
            'q' => $this->q,
            'category' => $this->category?->value,
            'country' => $this->country,
            'status' => $this->status?->value,
            'skill' => $this->skill,
            'sort' => $this->sort,
        ];
    }

    public function hasFilters(): bool
    {
        return collect($this->input())->except('sort')->filter()->isNotEmpty();
    }

    public function query(): Builder
    {
        $query = Project::query()
            ->with(['owner', 'country', 'skills', 'roles' => fn ($roles) => $roles->withCount('members')])
            ->withCount('memberships')
            // Archived projects only show up when explicitly asked for.
            ->when(! $this->status, fn (Builder $q) => $q->where('status', '!=', ProjectStatus::Archived));

        foreach (preg_split('/\s+/', (string) $this->q, -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(fn (Builder $where) => $where
                ->where('title', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('skills', fn (Builder $skills) => $skills->where('name', 'like', $like))
                ->orWhereHas('roles', fn (Builder $roles) => $roles->where('title', 'like', $like)));
        }

        $query
            ->when($this->category, fn (Builder $q) => $q->where('category', $this->category))
            ->when($this->country, fn (Builder $q) => $q->whereRelation('country', 'slug', $this->country))
            ->when($this->status, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->skill, fn (Builder $q) => $q->where(fn (Builder $where) => $where
                ->whereRelation('skills', 'slug', $this->skill)
                ->orWhereRelation('roles.skill', 'slug', $this->skill)));

        return match ($this->sort) {
            'team' => $query->orderByDesc('memberships_count')->latest(),
            'title' => $query->orderBy('title'),
            default => $query->latest(),
        };
    }
}

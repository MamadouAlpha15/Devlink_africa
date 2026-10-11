<?php

namespace App\Search;

use App\Enums\ResourceType;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Dev Resources list: /resources?q=paiement&type=package&skill=laravel&country=guinee&tag=mobile-money
 */
class ResourceSearch
{
    public const SORTS = [
        'recent' => 'Plus récentes',
        'popular' => 'Plus appréciées',
        'rating' => 'Mieux notées',
        'downloads' => 'Plus téléchargées',
    ];

    private function __construct(
        public readonly ?string $q = null,
        public readonly ?ResourceType $type = null,
        public readonly ?string $skill = null,
        public readonly ?string $country = null,
        public readonly ?string $tag = null,
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
            type: ResourceType::tryFrom((string) $text('type')),
            skill: $text('skill'),
            country: $text('country'),
            tag: $text('tag'),
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
            'type' => $this->type?->value,
            'skill' => $this->skill,
            'country' => $this->country,
            'tag' => $this->tag,
            'sort' => $this->sort,
        ];
    }

    public function hasFilters(): bool
    {
        return collect($this->input())->except('sort')->filter()->isNotEmpty();
    }

    public function query(): Builder
    {
        $query = Resource::query()->published()->with(['user', 'country', 'skills', 'tags']);

        foreach (preg_split('/\s+/', (string) $this->q, -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(fn (Builder $where) => $where
                ->where('title', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('skills', fn (Builder $skills) => $skills->where('name', 'like', $like))
                ->orWhereHas('tags', fn (Builder $tags) => $tags->where('name', 'like', $like)));
        }

        $query
            ->when($this->type, fn (Builder $q) => $q->where('type', $this->type))
            ->when($this->skill, fn (Builder $q) => $q->whereRelation('skills', 'slug', $this->skill))
            ->when($this->country, fn (Builder $q) => $q->whereRelation('country', 'slug', $this->country))
            ->when($this->tag, fn (Builder $q) => $q->whereRelation('tags', 'slug', $this->tag));

        return match ($this->sort) {
            'popular' => $query->orderByDesc('likes_count')->latest(),
            'rating' => $query->orderByRaw('rating_avg IS NULL')->orderByDesc('rating_avg')->latest(),
            'downloads' => $query->orderByDesc('downloads_count')->latest(),
            default => $query->latest(),
        };
    }
}

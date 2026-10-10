<?php

namespace App\Search;

use App\Enums\Availability;
use App\Enums\ExperienceLevel;
use App\Enums\ProfileType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * "Trouver des développeurs" (cahier, section 4): keyword + filters, combinable.
 * Example: ?q=laravel&country=guinee&availability=available
 */
class DeveloperSearch
{
    public const SORTS = [
        'devscore' => 'Meilleur DevScore',
        'recent' => 'Nouveaux membres',
        'name' => 'Nom (A → Z)',
    ];

    private function __construct(
        public readonly ?string $q = null,
        public readonly ?string $country = null,
        public readonly ?string $city = null,
        public readonly ?string $skill = null,
        public readonly ?ExperienceLevel $level = null,
        public readonly ?Availability $availability = null,
        public readonly ?ProfileType $type = null,
        public readonly string $sort = 'devscore',
    ) {}

    /**
     * Build the search from the query string. Invalid values are ignored rather than rejected.
     */
    public static function fromRequest(Request $request): self
    {
        $text = function (string $key, int $max = 100) use ($request): ?string {
            $value = $request->query($key);

            return is_string($value) && trim($value) !== '' ? Str::limit(trim($value), $max, '') : null;
        };

        $sort = $text('sort');

        return new self(
            q: $text('q'),
            country: $text('country'),
            city: $text('city', 80),
            skill: $text('skill'),
            level: ExperienceLevel::tryFrom((string) $text('level')),
            availability: Availability::tryFrom((string) $text('availability')),
            type: ProfileType::tryFrom((string) $text('type')),
            sort: array_key_exists((string) $sort, self::SORTS) ? $sort : 'devscore',
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
            'country' => $this->country,
            'city' => $this->city,
            'skill' => $this->skill,
            'level' => $this->level?->value,
            'availability' => $this->availability?->value,
            'type' => $this->type?->value,
            'sort' => $this->sort,
        ];
    }

    public function hasFilters(): bool
    {
        return collect($this->input())->except('sort')->filter()->isNotEmpty();
    }

    public function query(): Builder
    {
        $query = User::query()->listed()->with(['country', 'skills']);

        // Every word must match somewhere: name, city, bio, a skill or the country ("laravel conakry").
        foreach (preg_split('/\s+/', (string) $this->q, -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(fn (Builder $where) => $where
                ->where('name', 'like', $like)
                ->orWhere('username', 'like', $like)
                ->orWhere('city', 'like', $like)
                ->orWhere('bio', 'like', $like)
                ->orWhereHas('skills', fn (Builder $skills) => $skills->where('name', 'like', $like))
                ->orWhereHas('country', fn (Builder $country) => $country->where('name', 'like', $like)));
        }

        $query
            ->when($this->country, fn (Builder $q) => $q->whereRelation('country', 'slug', $this->country))
            ->when($this->city, fn (Builder $q) => $q->where('city', 'like', '%'.addcslashes($this->city, '%_\\').'%'))
            ->when($this->skill, fn (Builder $q) => $q->whereRelation('skills', 'slug', $this->skill))
            ->when($this->level, fn (Builder $q) => $q->where('experience_level', $this->level))
            ->when($this->type, fn (Builder $q) => $q->where('profile_type', $this->type))
            // Never reveal an availability the developer chose to hide.
            ->when($this->availability, fn (Builder $q) => $q
                ->where('availability', $this->availability)
                ->where('show_availability', true));

        return match ($this->sort) {
            'recent' => $query->latest(),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('devscore')->orderBy('name'),
        };
    }
}

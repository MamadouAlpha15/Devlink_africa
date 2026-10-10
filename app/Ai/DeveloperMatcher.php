<?php

namespace App\Ai;

use App\Enums\Availability;
use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Ranks developers against DevLink AI criteria and gives each a match percentage.
 *
 * Score out of 100: skills 60, country 20, experience on the same project type 10, availability 10.
 * A criterion the user did not ask for gives its full points to everyone.
 */
class DeveloperMatcher
{
    private const LEVEL_WEIGHT = [
        'beginner' => 0.55,
        'intermediate' => 0.7,
        'advanced' => 0.85,
        'expert' => 1.0,
    ];

    /**
     * A skill from the right area but not the one asked for ("React Native" for "Flutter") counts 70 %:
     * enough for a close profile in the requested country to rank above the exact skill elsewhere.
     */
    private const SAME_AREA_WEIGHT = 0.7;

    /**
     * @return Collection<int, array{user: User, score: int, matched_skills: list<string>}>
     */
    public function match(SearchCriteria $criteria, int $limit = 10): Collection
    {
        $wanted = Skill::whereIn('slug', $criteria->skills)->get();
        $areas = $wanted->pluck('category')->merge($criteria->skillCategories)->unique(fn ($category) => $category->value);

        return $this->candidates($criteria, $wanted, $areas)
            ->map(fn (User $user) => $this->score($user, $criteria, $wanted, $areas))
            ->filter(fn (array $result) => $result['score'] > 0)
            ->sortByDesc(fn (array $result) => [$result['score'], $result['user']->devscore])
            ->take($limit)
            ->values();
    }

    /**
     * Listed developers with at least one wanted skill or area; when no skill is asked, everyone in the country.
     */
    private function candidates(SearchCriteria $criteria, Collection $wanted, Collection $areas): Collection
    {
        $query = User::query()->listed()->with(['country', 'skills', 'projects']);

        if ($wanted->isNotEmpty() || $areas->isNotEmpty()) {
            $query->whereHas('skills', fn (Builder $skills) => $skills
                ->whereIn('skills.id', $wanted->pluck('id'))
                ->orWhereIn('skills.category', $areas->map->value));
        } elseif ($criteria->country) {
            $query->whereRelation('country', 'slug', $criteria->country);
        }

        return $query->orderByDesc('devscore')->limit(200)->get();
    }

    /**
     * @return array{user: User, score: int, matched_skills: list<string>}
     */
    private function score(User $user, SearchCriteria $criteria, Collection $wanted, Collection $areas): array
    {
        [$skillScore, $matched] = $this->skillScore($user, $wanted, $areas);

        $countryScore = match (true) {
            $criteria->country === null => 1.0,
            $user->country?->slug === $criteria->country => 1.0,
            default => 0.0,
        };

        $projectScore = $criteria->projectType === null
            || $user->projects->contains(fn ($project) => $project->category === $criteria->projectType) ? 1.0 : 0.0;

        // A hidden availability is not used against the developer.
        $availabilityScore = ! $user->show_availability ? 0.6 : match ($user->availability) {
            Availability::Available => 1.0,
            Availability::Open => 0.6,
            Availability::Busy => 0.0,
        };

        $score = 60 * $skillScore + 20 * $countryScore + 10 * $projectScore + 10 * $availabilityScore;

        return ['user' => $user, 'score' => (int) round($score), 'matched_skills' => $matched];
    }

    /**
     * @return array{0: float, 1: list<string>} Score between 0 and 1, and the names of the matching skills.
     */
    private function skillScore(User $user, Collection $wanted, Collection $areas): array
    {
        if ($wanted->isEmpty() && $areas->isEmpty()) {
            return [1.0, []];
        }

        $matched = [];
        $userSkills = $user->skills->keyBy('id');

        // Precise skills asked for: average over each of them.
        if ($wanted->isNotEmpty()) {
            $total = 0.0;

            foreach ($wanted as $skill) {
                if ($owned = $userSkills->get($skill->id)) {
                    $total += $this->levelWeight($owned->pivot->level);
                    $matched[] = $owned->name;

                    continue;
                }

                $sameArea = $user->skills->first(fn (Skill $other) => $other->category === $skill->category);
                if ($sameArea) {
                    $total += self::SAME_AREA_WEIGHT * $this->levelWeight($sameArea->pivot->level);
                    $matched[] = $sameArea->name;
                }
            }

            return [$total / $wanted->count(), array_values(array_unique($matched))];
        }

        // Only an area was asked ("un développeur mobile"): best skill of that area.
        $best = $user->skills
            ->filter(fn (Skill $skill) => $areas->contains(fn ($area) => $area === $skill->category))
            ->sortByDesc(fn (Skill $skill) => $this->levelWeight($skill->pivot->level));

        return $best->isEmpty()
            ? [0.0, []]
            : [$this->levelWeight($best->first()->pivot->level), $best->pluck('name')->take(3)->values()->all()];
    }

    private function levelWeight(SkillLevel $level): float
    {
        return self::LEVEL_WEIGHT[$level->value];
    }
}

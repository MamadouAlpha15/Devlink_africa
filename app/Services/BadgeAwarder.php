<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Enums\SkillLevel;
use App\Models\Activity;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Badges awarded automatically (cahier, section 17). Each badge has a `criteria` key checked here.
 * Badges are permanent: once earned, they are never taken back.
 */
class BadgeAwarder
{
    /** Published resources needed for "Contributeur actif". */
    public const ACTIVE_CONTRIBUTOR_RESOURCES = 3;

    /** Countries in the user's teams needed for "Panafricain". */
    public const PANAFRICAN_COUNTRIES = 3;

    /**
     * Give the user every badge they now qualify for.
     *
     * @return Collection<int, Badge> The badges just earned.
     */
    public function evaluate(User $user): Collection
    {
        $owned = $user->badges()->pluck('badges.id');

        $earned = Badge::query()
            ->whereNotIn('id', $owned)
            ->get()
            ->filter(fn (Badge $badge) => $this->qualifies($user, $badge->criteria));

        foreach ($earned as $badge) {
            $user->badges()->attach($badge->id, ['awarded_at' => now()]);
            Activity::log($user, ActivityType::BadgeEarned, $badge);
        }

        return $earned->values();
    }

    public function qualifies(User $user, string $criteria): bool
    {
        return match ($criteria) {
            'first_project' => $user->ownedProjects()->exists(),
            'collaborator' => $user->projects()->wherePivot('role', '!=', ProjectMemberRole::Owner->value)->exists(),
            'active_contributor' => $user->resources()->published()->count() >= self::ACTIVE_CONTRIBUTOR_RESOURCES,
            'laravel_expert' => $user->skills()->where('slug', 'laravel')->wherePivot('level', SkillLevel::Expert->value)->exists(),
            'panafrican' => $this->teamCountries($user) >= self::PANAFRICAN_COUNTRIES,
            'builder' => $user->ownedProjects()->where('status', ProjectStatus::Completed)->exists(),
            default => false,
        };
    }

    /**
     * Number of different countries among the people the user works with (including themselves).
     */
    private function teamCountries(User $user): int
    {
        $projectIds = $user->projects()->pluck('projects.id');

        return User::query()
            ->where(fn ($query) => $query
                ->whereHas('projects', fn ($projects) => $projects->whereIn('projects.id', $projectIds))
                ->orWhere('users.id', $user->id))
            ->whereNotNull('country_id')
            ->distinct()
            ->count('country_id');
    }
}

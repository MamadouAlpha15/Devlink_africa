<?php

namespace App\Services;

use App\Enums\PointAction;
use App\Models\User;

class ProfileCompletion
{
    /**
     * Fields a profile needs before it counts as "complete" (cahier, section 1).
     * At least one skill is also required.
     */
    public const REQUIRED_FIELDS = ['first_name', 'last_name', 'country_id', 'city', 'bio', 'profile_type', 'availability'];

    public function __construct(private DevScore $devScore) {}

    public function isComplete(User $user): bool
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (blank($user->{$field})) {
                return false;
            }
        }

        return $user->skills()->exists();
    }

    /**
     * Completion percentage shown to the user ("Profil complété à 80 %").
     */
    public function percentage(User $user): int
    {
        $checks = array_map(fn (string $field) => filled($user->{$field}), self::REQUIRED_FIELDS);
        $checks[] = $user->skills()->exists();
        $checks[] = filled($user->avatar_path);
        $checks[] = filled($user->experience_level);
        $checks[] = filled($user->github_url) || filled($user->linkedin_url)
            || filled($user->portfolio_url) || filled($user->website_url);

        return (int) round(count(array_filter($checks)) / count($checks) * 100);
    }

    /**
     * Mark the profile as completed and award the DevScore bonus, only the first time.
     *
     * @return bool Whether the profile has just been completed.
     */
    public function sync(User $user): bool
    {
        if ($user->profile_completed_at !== null || ! $this->isComplete($user)) {
            return false;
        }

        $user->forceFill(['profile_completed_at' => now()])->save();
        $this->devScore->award($user, PointAction::ProfileCompleted, $user);

        return true;
    }
}

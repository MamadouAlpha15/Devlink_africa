<?php

namespace App\Services;

use App\Enums\ResourceStatus;
use App\Models\Activity;
use App\Models\Badge;
use App\Models\Project;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

/**
 * "Fil d'activité" (cahier, section 19): resources published, projects created and joined, badges earned.
 * "Pas besoin de transformer cela en Facebook": a short list, no likes or comments on events.
 */
class ActivityFeed
{
    public const SCOPES = [
        'network' => 'Mon réseau',
        'all' => 'Toute la communauté',
    ];

    /**
     * @param  string  $scope  "network" = me and the people I follow, "all" = the whole community
     * @return Collection<int, Activity>
     */
    public function for(User $viewer, string $scope = 'network', int $limit = 15): Collection
    {
        return Activity::query()
            ->with([
                'user.country',
                'subject' => fn (MorphTo $morph) => $morph->morphWith([Project::class => ['owner']]),
            ])
            // Only public, active members (and myself) show up in the feed.
            ->whereHas('user', fn (Builder $users) => $users
                ->where(fn (Builder $q) => $q->listed())
                ->orWhere('users.id', $viewer->id))
            ->when($scope === 'network', fn (Builder $q) => $q->whereIn('user_id', $viewer->following()->pluck('users.id')->push($viewer->id)))
            ->latest()
            ->latest('id')
            ->limit($limit * 2) // some events may point to deleted content
            ->get()
            ->filter(fn (Activity $activity) => $this->isDisplayable($activity))
            ->take($limit)
            ->values();
    }

    /**
     * Link of the event's subject, or null (badges have no page).
     */
    public function linkFor(Activity $activity): ?string
    {
        return match (true) {
            $activity->subject instanceof Resource => route('resources.show', $activity->subject),
            $activity->subject instanceof Project => route('projects.show', $activity->subject),
            default => null,
        };
    }

    /**
     * "Orange Money pour Laravel", "AfricaDelivery" or "🌍 Panafricain".
     */
    public function subjectTitle(Activity $activity): string
    {
        return match (true) {
            $activity->subject instanceof Badge => $activity->subject->icon.' '.$activity->subject->name,
            default => (string) $activity->subject?->title,
        };
    }

    /**
     * Deleted projects/resources come back as null; unpublished resources are hidden.
     */
    private function isDisplayable(Activity $activity): bool
    {
        return match (true) {
            $activity->subject === null => false,
            $activity->subject instanceof Resource => $activity->subject->status === ResourceStatus::Approved,
            default => true,
        };
    }
}

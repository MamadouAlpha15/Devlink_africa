<?php

namespace App\Http\Controllers;

use App\Ai\DeveloperMatcher;
use App\Ai\SearchCriteria;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Enums\RequestDirection;
use App\Models\CollaborationRequest;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\User;
use App\Search\Ranking;
use App\Services\ActivityFeed;
use App\Services\ProfileCompletion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Dashboard after login (cahier, section 20) with the activity feed (section 19).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, ProfileCompletion $completion, ActivityFeed $feed, DeveloperMatcher $matcher): View
    {
        $user = $request->user()->load(['country', 'skills', 'badges']);
        $scope = array_key_exists((string) $request->query('feed'), ActivityFeed::SCOPES) ? $request->query('feed') : 'network';

        $managedProjectIds = $user->projects()
            ->wherePivotIn('role', [ProjectMemberRole::Owner->value, ProjectMemberRole::CoOwner->value])
            ->pluck('projects.id');

        return view('dashboard', [
            'user' => $user,
            'completion' => $completion->percentage($user),
            'africaRank' => Ranking::make()->rankOf($user),
            'stats' => [
                'projects' => $user->projects()->count(),
                'resources' => $user->resources()->published()->count(),
                'collaborations' => $user->projects()->wherePivot('role', '!=', ProjectMemberRole::Owner->value)->count(),
                'followers' => $user->followers()->count(),
            ],
            'pending' => [
                'invitations' => CollaborationRequest::pending()
                    ->where('direction', RequestDirection::Invitation)->where('user_id', $user->id)->count(),
                'applications' => CollaborationRequest::pending()
                    ->where('direction', RequestDirection::Application)->whereIn('project_id', $managedProjectIds)->count(),
            ],
            'recommendedDevelopers' => $this->recommendedDevelopers($user, $managedProjectIds, $matcher),
            'interestingProjects' => $this->interestingProjects($user),
            'feed' => $feed->for($user, $scope),
            'feedScope' => $scope,
            'feedScopes' => ActivityFeed::SCOPES,
            'activityFeed' => $feed,
        ]);
    }

    /**
     * "Recommandations de développeurs": the best profiles for the open roles of my projects;
     * without open roles, active developers from my country I do not follow yet.
     *
     * @return Collection<int, array{user: User, score: ?int, reason: string}>
     */
    private function recommendedDevelopers(User $user, Collection $managedProjectIds, DeveloperMatcher $matcher): Collection
    {
        $excluded = $user->following()->pluck('users.id')
            ->merge(Project::whereIn('id', $managedProjectIds)->with('memberships')->get()->flatMap->memberships->pluck('user_id'))
            ->push($user->id);

        $openRoles = ProjectRole::with(['skill', 'project'])->withCount('members')
            ->whereIn('project_id', $managedProjectIds)
            ->whereNotNull('skill_id')
            ->get()
            ->reject->isFull();

        if ($openRoles->isNotEmpty()) {
            return $matcher->match(new SearchCriteria(skills: $openRoles->pluck('skill.slug')->unique()->values()->all()), 20)
                ->reject(fn (array $result) => $excluded->contains($result['user']->id))
                ->take(4)
                ->map(fn (array $result) => [
                    'user' => $result['user'],
                    'score' => $result['score'],
                    'reason' => 'Pour vos postes : '.collect($result['matched_skills'])->join(', '),
                ])
                ->values();
        }

        return User::query()->listed()
            ->with(['country', 'skills'])
            ->whereNotIn('id', $excluded)
            ->when($user->country_id, fn (Builder $q) => $q->where('country_id', $user->country_id))
            ->orderByDesc('devscore')
            ->limit(4)
            ->get()
            ->map(fn (User $developer) => [
                'user' => $developer,
                'score' => null,
                'reason' => $user->country ? 'Développeur actif en '.$user->country->name : 'Développeur actif',
            ]);
    }

    /**
     * "Projets intéressants": projects recruiting on my skills, otherwise the latest open projects.
     *
     * @return Collection<int, Project>
     */
    private function interestingProjects(User $user): Collection
    {
        $mySkillIds = $user->skills->pluck('id');

        $open = fn () => Project::query()
            ->with(['owner', 'country', 'roles' => fn ($roles) => $roles->with('skill')->withCount('members')])
            ->whereIn('status', [ProjectStatus::Recruiting, ProjectStatus::Idea, ProjectStatus::InProgress])
            ->whereDoesntHave('memberships', fn (Builder $q) => $q->where('user_id', $user->id));

        $matching = $mySkillIds->isEmpty() ? collect() : $open()
            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('skill_id', $mySkillIds))
            ->latest()->limit(10)->get()
            // Keep projects that still have a free place on one of my skills.
            ->filter(fn (Project $project) => $project->roles->contains(fn (ProjectRole $role) => $mySkillIds->contains($role->skill_id) && ! $role->isFull()))
            ->take(4);

        if ($matching->count() >= 4) {
            return $matching->values();
        }

        return $matching->merge($open()->whereNotIn('id', $matching->pluck('id'))->latest()->limit(4 - $matching->count())->get())->values();
    }
}

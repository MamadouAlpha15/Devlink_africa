<?php

namespace App\Http\Controllers;

use App\Enums\Availability;
use App\Enums\ExperienceLevel;
use App\Enums\ProfileType;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Models\Country;
use App\Models\Skill;
use App\Models\User;
use App\Search\DeveloperSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DeveloperController extends Controller
{
    /**
     * "Trouver des développeurs": /devs?q=laravel&country=guinee&availability=available
     */
    public function index(Request $request): View
    {
        $search = DeveloperSearch::fromRequest($request);

        // Developers per country (cahier, section 5), also used to label the country filter.
        $countries = Country::query()
            ->withCount(['users as developers_count' => fn ($query) => $query->listed()])
            ->orderBy('name')
            ->get();

        return view('devs.index', [
            'developers' => $search->query()->paginate(12)->withQueryString(),
            'filters' => $search->input(),
            'hasFilters' => $search->hasFilters(),
            'countries' => $countries,
            'topCountries' => $countries->where('developers_count', '>', 0)->sortByDesc('developers_count')->take(10)->values(),
            'skillsByCategory' => Skill::orderBy('name')->get()->groupBy(fn (Skill $skill) => $skill->category->label()),
            'experienceLevels' => ExperienceLevel::cases(),
            'availabilities' => Availability::cases(),
            'profileTypes' => ProfileType::cases(),
            'sorts' => DeveloperSearch::SORTS,
        ]);
    }

    /**
     * Public developer profile: /devs/{username}.
     */
    public function show(Request $request, User $user): View
    {
        // A private or suspended profile looks like it does not exist.
        abort_unless(Gate::allows('view', $user), 404);

        $user->load(['country', 'skills', 'badges'])
            ->loadCount([
                'followers',
                'following',
                'projects',
                'projects as collaborations_count' => fn ($query) => $query->where('project_members.role', '!=', ProjectMemberRole::Owner->value),
                'resources' => fn ($query) => $query->published(),
            ]);

        $viewer = $request->user();

        return view('devs.show', [
            'user' => $user,
            'projects' => $user->projects()->with('country')->latest('project_members.joined_at')->limit(6)->get(),
            'resources' => $user->resources()->published()->latest()->limit(6)->get(),
            'ratingAvg' => $user->resources()->published()->avg('rating_avg'),
            'isOwner' => $viewer?->is($user) ?? false,
            'isFollowing' => $viewer ? $viewer->following()->whereKey($user->id)->exists() : false,
            'canFollow' => $viewer ? Gate::forUser($viewer)->allows('follow', $user) : false,
            'invitableProjects' => $viewer && ! $viewer->is($user) ? $this->invitableProjects($viewer, $user) : collect(),
        ]);
    }

    /**
     * "Proposer une collaboration": projects I manage that are open and that the developer is not part of yet.
     */
    private function invitableProjects(User $viewer, User $developer): Collection
    {
        return $viewer->projects()
            ->wherePivotIn('role', [ProjectMemberRole::Owner->value, ProjectMemberRole::CoOwner->value])
            ->whereIn('status', [ProjectStatus::Idea, ProjectStatus::Recruiting, ProjectStatus::InProgress])
            ->whereDoesntHave('memberships', fn ($query) => $query->where('user_id', $developer->id))
            ->with(['roles' => fn ($roles) => $roles->withCount('members')])
            ->orderBy('title')
            ->get();
    }
}

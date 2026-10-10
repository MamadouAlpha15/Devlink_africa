<?php

namespace App\Http\Controllers;

use App\Enums\ProjectCategory;
use App\Enums\ProjectStatus;
use App\Enums\RequestDirection;
use App\Enums\RequestStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Country;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\Skill;
use App\Models\User;
use App\Search\ProjectSearch;
use App\Services\ProjectCollaboration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $search = ProjectSearch::fromRequest($request);

        return view('projects.index', [
            'projects' => $search->query()->paginate(12)->withQueryString(),
            'filters' => $search->input(),
            'hasFilters' => $search->hasFilters(),
            'sorts' => ProjectSearch::SORTS,
        ] + $this->formOptions());
    }

    public function create(): View
    {
        Gate::authorize('create', Project::class);

        return view('projects.create', array_merge($this->formOptions(), [
            // A new project cannot start as completed or archived.
            'statuses' => [ProjectStatus::Idea, ProjectStatus::Recruiting, ProjectStatus::InProgress],
        ]));
    }

    public function store(StoreProjectRequest $request, ProjectCollaboration $collaboration): RedirectResponse
    {
        Gate::authorize('create', Project::class);

        $project = $collaboration->createProject($request->user(), $request->projectData(), $request->file('cover'));

        return redirect()->route('projects.show', $project)
            ->with('status', 'Projet créé ! +'.config('devscore.points.project_created').' points DevScore.');
    }

    public function show(Request $request, Project $project): View
    {
        $project->load([
            'owner', 'country', 'skills',
            'roles' => fn ($roles) => $roles->with('skill')->withCount('members'),
            'memberships' => fn ($memberships) => $memberships->with(['user.country', 'projectRole'])->orderBy('joined_at'),
        ]);

        $viewer = $request->user();
        $canManage = $viewer && Gate::forUser($viewer)->allows('manage', $project);

        return view('projects.show', [
            'project' => $project,
            'viewerMembership' => $viewer ? $project->memberships->firstWhere('user_id', $viewer->id) : null,
            'viewerPendingRequest' => $viewer ? $project->collaborationRequests()->pending()->where('user_id', $viewer->id)->first() : null,
            'canUpdate' => $viewer && Gate::forUser($viewer)->allows('update', $project),
            'canManage' => $canManage,
            'canApply' => $viewer && Gate::forUser($viewer)->allows('apply', $project),
            'pendingApplications' => $canManage ? $this->pendingRequests($project, RequestDirection::Application) : collect(),
            'pendingInvitations' => $canManage ? $this->pendingRequests($project, RequestDirection::Invitation) : collect(),
            'suggestions' => $canManage ? $this->suggestedDevelopers($project) : collect(),
            'skills' => $canManage ? Skill::orderBy('name')->get() : collect(),
        ]);
    }

    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('projects.edit', ['project' => $project->load('skills')] + $this->formOptions());
    }

    public function update(UpdateProjectRequest $request, Project $project, ProjectCollaboration $collaboration): RedirectResponse
    {
        Gate::authorize('update', $project);

        $wasCompleted = $project->completed_at !== null;
        $collaboration->updateProject($project, $request->projectData(), $request->file('cover'));

        $message = ! $wasCompleted && $project->completed_at !== null
            ? 'Projet terminé, bravo ! +'.config('devscore.points.project_completed').' points DevScore.'
            : 'Projet mis à jour.';

        return redirect()->route('projects.show', $project)->with('status', $message);
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $project->collaborationRequests()->pending()->update([
            'status' => RequestStatus::Cancelled,
            'responded_at' => now(),
        ]);
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Projet supprimé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => ProjectCategory::cases(),
            'statuses' => ProjectStatus::cases(),
            'countries' => Country::orderBy('name')->get(),
            'skillsByCategory' => Skill::orderBy('name')->get()->groupBy(fn (Skill $skill) => $skill->category->label()),
        ];
    }

    private function pendingRequests(Project $project, RequestDirection $direction): Collection
    {
        return $project->collaborationRequests()
            ->pending()
            ->where('direction', $direction)
            ->with(['user.country', 'user.skills', 'projectRole'])
            ->latest()
            ->get();
    }

    /**
     * "Chercher des collaborateurs": for each open role, the 3 best available developers with that skill
     * who are not already in the team or asked.
     *
     * @return Collection<int, array{role: ProjectRole, developers: Collection<int, User>}>
     */
    private function suggestedDevelopers(Project $project): Collection
    {
        $excluded = $project->memberships->pluck('user_id')
            ->merge($project->collaborationRequests()->pending()->pluck('user_id'));

        return $project->roles
            ->filter(fn (ProjectRole $role) => $role->skill_id && ! $role->isFull())
            ->map(fn (ProjectRole $role) => [
                'role' => $role,
                'developers' => User::query()->listed()
                    ->with('country')
                    ->whereRelation('skills', 'skills.id', $role->skill_id)
                    ->whereNotIn('id', $excluded)
                    // Skip developers who say they are busy (hidden availability is not used).
                    ->where(fn ($query) => $query->where('availability', '!=', 'busy')->orWhere('show_availability', false))
                    ->orderByDesc('devscore')
                    ->limit(3)
                    ->get(),
            ])
            ->values();
    }
}

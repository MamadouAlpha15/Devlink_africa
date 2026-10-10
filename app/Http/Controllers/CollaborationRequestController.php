<?php

namespace App\Http\Controllers;

use App\Enums\RequestDirection;
use App\Enums\RequestStatus;
use App\Http\Requests\CollaborationRequestRequest;
use App\Http\Requests\InviteToProjectRequest;
use App\Models\CollaborationRequest;
use App\Models\Project;
use App\Services\ProjectCollaboration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * "Demandes de collaboration" (cahier, section 12): applications and invitations.
 */
class CollaborationRequestController extends Controller
{
    /**
     * "Mes collaborations": everything waiting for me, and what I sent.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $with = ['project.owner', 'project.country', 'user.country', 'projectRole'];

        $managedProjectIds = $user->projects()
            ->wherePivotIn('role', ['owner', 'co_owner'])
            ->pluck('projects.id');

        return view('collaborations.index', [
            // Developers asking to join my projects → I accept or reject.
            'receivedApplications' => CollaborationRequest::with($with)->pending()
                ->where('direction', RequestDirection::Application)
                ->whereIn('project_id', $managedProjectIds)
                ->latest()->get(),
            // Projects inviting me → I accept or reject.
            'receivedInvitations' => CollaborationRequest::with($with)->pending()
                ->where('direction', RequestDirection::Invitation)
                ->where('user_id', $user->id)
                ->latest()->get(),
            // My applications waiting for an answer → I can withdraw them.
            'sentApplications' => CollaborationRequest::with($with)->pending()
                ->where('direction', RequestDirection::Application)
                ->where('user_id', $user->id)
                ->latest()->get(),
            // Answered requests that concern me.
            'history' => CollaborationRequest::with($with)
                ->where('status', '!=', RequestStatus::Pending)
                ->where(fn ($query) => $query->where('user_id', $user->id)->orWhereIn('project_id', $managedProjectIds))
                ->latest('responded_at')->limit(20)->get(),
        ]);
    }

    /**
     * Ask to join a project.
     */
    public function store(CollaborationRequestRequest $request, Project $project, ProjectCollaboration $collaboration): RedirectResponse
    {
        Gate::authorize('apply', $project);

        $collaboration->apply($request->user(), $project, $request->role(), $request->validated('message'));

        return back()->with('status', 'Demande envoyée au propriétaire du projet.');
    }

    /**
     * "Proposer une collaboration": invite a developer to the project.
     */
    public function invite(InviteToProjectRequest $request, Project $project, ProjectCollaboration $collaboration): RedirectResponse
    {
        Gate::authorize('manage', $project);

        $invitee = $request->invitee();
        abort_unless(Gate::allows('view', $invitee), 404);

        $collaboration->invite($project, $invitee, $request->role(), $request->validated('message'));

        return back()->with('status', 'Invitation envoyée à '.$invitee->name.'.');
    }

    public function accept(CollaborationRequest $collaborationRequest, ProjectCollaboration $collaboration): RedirectResponse
    {
        Gate::authorize('respond', $collaborationRequest);

        $collaboration->accept($collaborationRequest);

        return back()->with('status', $collaborationRequest->direction === RequestDirection::Invitation
            ? 'Bienvenue dans l\'équipe de '.$collaborationRequest->project->title.' ! +'.config('devscore.points.collaboration').' points DevScore.'
            : $collaborationRequest->user->name.' a rejoint le projet.');
    }

    public function reject(CollaborationRequest $collaborationRequest, ProjectCollaboration $collaboration): RedirectResponse
    {
        Gate::authorize('respond', $collaborationRequest);

        $collaboration->reject($collaborationRequest);

        return back()->with('status', 'Demande refusée.');
    }

    public function destroy(CollaborationRequest $collaborationRequest, ProjectCollaboration $collaboration): RedirectResponse
    {
        Gate::authorize('cancel', $collaborationRequest);

        $collaboration->cancel($collaborationRequest);

        return back()->with('status', 'Demande annulée.');
    }
}

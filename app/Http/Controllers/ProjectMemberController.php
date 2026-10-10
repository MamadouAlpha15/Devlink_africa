<?php

namespace App\Http\Controllers;

use App\Enums\ProjectMemberRole;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectCollaboration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * "Gestion des membres" (cahier, section 11).
 */
class ProjectMemberController extends Controller
{
    /**
     * Promote a member to co-owner, or demote them (owner only).
     */
    public function update(Request $request, Project $project, User $member, ProjectCollaboration $collaboration): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(ProjectMemberRole::class)->only([ProjectMemberRole::CoOwner, ProjectMemberRole::Member])],
        ]);

        $collaboration->changeMemberRole($project, $member, ProjectMemberRole::from($validated['role']));

        return back()->with('status', 'Rôle de '.$member->name.' mis à jour.');
    }

    /**
     * Remove a member. Co-owners can only remove simple members.
     */
    public function destroy(Request $request, Project $project, User $member, ProjectCollaboration $collaboration): RedirectResponse
    {
        Gate::authorize('manage', $project);

        if ($project->membershipOf($member)?->role === ProjectMemberRole::CoOwner) {
            Gate::authorize('update', $project);
        }

        $collaboration->removeMember($project, $member);

        return back()->with('status', $member->name.' a été retiré du projet.');
    }

    public function leave(Request $request, Project $project, ProjectCollaboration $collaboration): RedirectResponse
    {
        $collaboration->leave($project, $request->user());

        return redirect()->route('projects.show', $project)->with('status', 'Vous avez quitté le projet.');
    }
}

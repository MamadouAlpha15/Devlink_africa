<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\PointAction;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Enums\RequestDirection;
use App\Enums\RequestStatus;
use App\Models\Activity;
use App\Models\CollaborationRequest;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Collaborative projects (cahier, sections 9 to 12): projects, recruitment, requests and members.
 */
class ProjectCollaboration
{
    public function __construct(private DevScore $devScore) {}

    /**
     * @param  array<string, mixed>  $data  Project fields + 'skills' (ids) + 'roles' (title, skill_id, slots)
     */
    public function createProject(User $owner, array $data, ?UploadedFile $cover = null): Project
    {
        return DB::transaction(function () use ($owner, $data, $cover) {
            $project = $owner->ownedProjects()->create(array_merge(
                Arr::except($data, ['skills', 'roles', 'cover']),
                ['cover_path' => $cover?->store('projects', 'public')],
            ));

            $project->skills()->sync($data['skills'] ?? []);

            foreach ($data['roles'] ?? [] as $role) {
                $project->roles()->create($role);
            }

            ProjectMember::create([
                'project_id' => $project->id,
                'user_id' => $owner->id,
                'role' => ProjectMemberRole::Owner,
                'joined_at' => now(),
            ]);

            $this->devScore->award($owner, PointAction::ProjectCreated, $project);
            Activity::log($owner, ActivityType::ProjectCreated, $project);

            return $project;
        });
    }

    /**
     * @param  array<string, mixed>  $data  Project fields + 'skills' (ids)
     */
    public function updateProject(Project $project, array $data, ?UploadedFile $cover = null): Project
    {
        return DB::transaction(function () use ($project, $data, $cover) {
            $fields = Arr::except($data, ['skills', 'cover', 'remove_cover']);

            if ($cover || ($data['remove_cover'] ?? false)) {
                if ($project->cover_path) {
                    Storage::disk('public')->delete($project->cover_path);
                }
                $fields['cover_path'] = $cover?->store('projects', 'public');
            }

            $project->update($fields);

            if (array_key_exists('skills', $data)) {
                $project->skills()->sync($data['skills'] ?? []);
            }

            // "Projet terminé" (+100) is awarded once, the first time the project is completed.
            if ($project->status === ProjectStatus::Completed && $project->completed_at === null) {
                $project->forceFill(['completed_at' => now()])->save();
                $this->devScore->award($project->owner, PointAction::ProjectCompleted, $project);
            }

            return $project;
        });
    }

    /**
     * A developer asks to join a project.
     */
    public function apply(User $user, Project $project, ?ProjectRole $role, ?string $message): CollaborationRequest
    {
        $this->ensureCanJoin($project, $user, $role, 'Vous avez déjà une demande en attente pour ce projet.');

        return $project->collaborationRequests()->create([
            'user_id' => $user->id,
            'direction' => RequestDirection::Application,
            'project_role_id' => $role?->id,
            'message' => $message,
            'status' => RequestStatus::Pending,
        ]);
    }

    /**
     * The project team invites a developer ("Proposer une collaboration").
     */
    public function invite(Project $project, User $invitee, ?ProjectRole $role, ?string $message): CollaborationRequest
    {
        if (! $project->acceptsCollaborators()) {
            throw ValidationException::withMessages(['collaboration' => 'Ce projet n\'accepte plus de nouveaux membres.']);
        }

        $this->ensureCanJoin($project, $invitee, $role, 'Une demande est déjà en attente entre ce développeur et ce projet.');

        return $project->collaborationRequests()->create([
            'user_id' => $invitee->id,
            'direction' => RequestDirection::Invitation,
            'project_role_id' => $role?->id,
            'message' => $message,
            'status' => RequestStatus::Pending,
        ]);
    }

    public function accept(CollaborationRequest $request): ProjectMember
    {
        return DB::transaction(function () use ($request) {
            $project = $request->project;
            $user = $request->user;

            if ($project->hasMember($user)) {
                throw ValidationException::withMessages(['collaboration' => $user->name.' fait déjà partie de l\'équipe.']);
            }

            if ($request->projectRole && $request->projectRole->isFull()) {
                throw ValidationException::withMessages(['collaboration' => 'Le poste « '.$request->projectRole->title.' » est déjà pourvu.']);
            }

            $membership = ProjectMember::create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'project_role_id' => $request->project_role_id,
                'role' => ProjectMemberRole::Member,
                'joined_at' => now(),
            ]);

            $request->update(['status' => RequestStatus::Accepted, 'responded_at' => now()]);

            $this->devScore->award($user, PointAction::Collaboration, $project);
            Activity::log($user, ActivityType::ProjectJoined, $project);

            return $membership;
        });
    }

    public function reject(CollaborationRequest $request): void
    {
        $request->update(['status' => RequestStatus::Rejected, 'responded_at' => now()]);
    }

    public function cancel(CollaborationRequest $request): void
    {
        $request->update(['status' => RequestStatus::Cancelled, 'responded_at' => now()]);
    }

    /**
     * Promote a member to co-owner, or demote a co-owner back to member.
     */
    public function changeMemberRole(Project $project, User $member, ProjectMemberRole $role): void
    {
        $membership = $this->membershipOrFail($project, $member);

        if ($membership->role === ProjectMemberRole::Owner || $role === ProjectMemberRole::Owner) {
            throw ValidationException::withMessages(['member' => 'Le rôle du propriétaire ne peut pas être modifié.']);
        }

        $membership->update(['role' => $role]);
    }

    public function removeMember(Project $project, User $member): void
    {
        $membership = $this->membershipOrFail($project, $member);

        if ($membership->role === ProjectMemberRole::Owner) {
            throw ValidationException::withMessages(['member' => 'Le propriétaire ne peut pas être retiré du projet.']);
        }

        $membership->delete();
    }

    public function leave(Project $project, User $member): void
    {
        $membership = $this->membershipOrFail($project, $member);

        if ($membership->role === ProjectMemberRole::Owner) {
            throw ValidationException::withMessages(['member' => 'Le propriétaire ne peut pas quitter son projet.']);
        }

        $membership->delete();
    }

    private function ensureCanJoin(Project $project, User $user, ?ProjectRole $role, string $pendingMessage): void
    {
        if ($project->hasMember($user)) {
            throw ValidationException::withMessages(['collaboration' => $user->name.' fait déjà partie de l\'équipe.']);
        }

        $hasPending = $project->collaborationRequests()->pending()->where('user_id', $user->id)->exists();

        if ($hasPending) {
            throw ValidationException::withMessages(['collaboration' => $pendingMessage]);
        }

        if ($role && $role->isFull()) {
            throw ValidationException::withMessages(['project_role_id' => 'Le poste « '.$role->title.' » est déjà pourvu.']);
        }
    }

    private function membershipOrFail(Project $project, User $member): ProjectMember
    {
        return $project->membershipOf($member)
            ?? throw ValidationException::withMessages(['member' => $member->name.' ne fait pas partie de ce projet.']);
    }
}

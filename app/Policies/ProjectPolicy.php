<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function create(User $user): bool
    {
        return ! $user->isSuspended();
    }

    /** Edit the project details and status, delete it, promote members. */
    public function update(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) || $user->is_admin;
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /** Owner and co-owners: roles, collaboration requests, invitations, members. */
    public function manage(User $user, Project $project): bool
    {
        return $project->isManagedBy($user) || $user->is_admin;
    }

    /** Ask to join: anyone who is not already in the team, while the project accepts collaborators. */
    public function apply(User $user, Project $project): bool
    {
        return ! $user->isSuspended()
            && $project->acceptsCollaborators()
            && ! $project->hasMember($user);
    }
}

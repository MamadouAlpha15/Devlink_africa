<?php

namespace App\Policies;

use App\Enums\ResourceStatus;
use App\Models\Resource;
use App\Models\User;

class ResourcePolicy
{
    /** Published resources are public; pending or rejected ones only for their author and admins. */
    public function view(?User $user, Resource $resource): bool
    {
        if ($resource->status === ResourceStatus::Approved) {
            return true;
        }

        return $user !== null && ($resource->user_id === $user->id || $user->is_admin);
    }

    public function create(User $user): bool
    {
        return ! $user->isSuspended();
    }

    public function update(User $user, Resource $resource): bool
    {
        return $resource->user_id === $user->id || $user->is_admin;
    }

    public function delete(User $user, Resource $resource): bool
    {
        return $this->update($user, $resource);
    }

    /** Like, rate or comment: a published resource, and not your own for likes and ratings. */
    public function interact(User $user, Resource $resource): bool
    {
        return ! $user->isSuspended()
            && $resource->status === ResourceStatus::Approved
            && $resource->user_id !== $user->id;
    }

    public function comment(User $user, Resource $resource): bool
    {
        return ! $user->isSuspended() && $resource->status === ResourceStatus::Approved;
    }
}

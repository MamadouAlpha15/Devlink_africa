<?php

namespace App\Policies;

use App\Enums\RequestDirection;
use App\Enums\RequestStatus;
use App\Models\CollaborationRequest;
use App\Models\User;

class CollaborationRequestPolicy
{
    /**
     * Accept or reject: the project team answers applications,
     * the invited developer answers invitations.
     */
    public function respond(User $user, CollaborationRequest $request): bool
    {
        if ($request->status !== RequestStatus::Pending) {
            return false;
        }

        return $request->direction === RequestDirection::Application
            ? $request->project->isManagedBy($user)
            : $request->user_id === $user->id;
    }

    /**
     * Withdraw: the applicant withdraws an application, the project team withdraws an invitation.
     */
    public function cancel(User $user, CollaborationRequest $request): bool
    {
        if ($request->status !== RequestStatus::Pending) {
            return false;
        }

        return $request->direction === RequestDirection::Application
            ? $request->user_id === $user->id
            : $request->project->isManagedBy($user);
    }
}

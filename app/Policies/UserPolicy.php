<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Public profile page: visible to everyone once the email is verified,
     * unless private or suspended. The owner and admins can always see it.
     */
    public function view(?User $viewer, User $user): bool
    {
        if ($viewer && ($viewer->is($user) || $viewer->is_admin)) {
            return true;
        }

        return $user->is_public && $user->hasVerifiedEmail() && ! $user->isSuspended();
    }

    public function follow(User $viewer, User $user): bool
    {
        return ! $viewer->is($user) && $this->view($viewer, $user);
    }
}

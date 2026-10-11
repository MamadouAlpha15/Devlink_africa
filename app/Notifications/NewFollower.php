<?php

namespace App\Notifications;

use App\Models\User;

/**
 * "Nouveau follower".
 */
class NewFollower extends DevLinkNotification
{
    public function __construct(public User $follower) {}

    protected function message(): string
    {
        return $this->follower->name.' vous suit maintenant.';
    }

    protected function url(): string
    {
        return route('devs.show', $this->follower);
    }

    protected function icon(): string
    {
        return '👋';
    }

    public function databaseType(object $notifiable): string
    {
        return 'new_follower';
    }
}

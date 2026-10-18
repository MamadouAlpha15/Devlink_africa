<?php

namespace App\Notifications;

use App\Enums\RequestDirection;
use App\Models\CollaborationRequest;

/**
 * "Nouvelle demande de collaboration": to the project team for an application,
 * to the developer for an invitation.
 */
class CollaborationRequested extends AfriCommitNotification
{
    public function __construct(public CollaborationRequest $request) {}

    protected function message(): string
    {
        $project = $this->request->project->title;
        $role = $this->request->projectRole ? ' comme « '.$this->request->projectRole->title.' »' : '';

        return $this->request->direction === RequestDirection::Application
            ? $this->request->user->name.' souhaite rejoindre '.$project.$role.'.'
            : $this->request->project->owner->name.' vous invite à rejoindre '.$project.$role.'.';
    }

    protected function url(): string
    {
        return route('collaborations.index');
    }

    protected function icon(): string
    {
        return '🤝';
    }

    protected function sendsMail(): bool
    {
        return true;
    }

    public function databaseType(object $notifiable): string
    {
        return 'collaboration_requested';
    }
}

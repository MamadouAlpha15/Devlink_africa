<?php

namespace App\Notifications;

use App\Models\CollaborationRequest;

/**
 * "Demande acceptée": the developer's application was accepted by the project team.
 */
class CollaborationAccepted extends DevLinkNotification
{
    public function __construct(public CollaborationRequest $request) {}

    protected function message(): string
    {
        return 'Votre demande pour rejoindre '.$this->request->project->title.' a été acceptée. Bienvenue dans l\'équipe !';
    }

    protected function url(): string
    {
        return route('projects.show', $this->request->project);
    }

    protected function icon(): string
    {
        return '🎉';
    }

    protected function sendsMail(): bool
    {
        return true;
    }

    public function databaseType(object $notifiable): string
    {
        return 'collaboration_accepted';
    }
}

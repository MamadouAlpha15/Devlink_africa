<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;

/**
 * "Projet rejoint": a developer accepted the team's invitation.
 */
class ProjectJoined extends DevLinkNotification
{
    public function __construct(public Project $project, public User $member) {}

    protected function message(): string
    {
        return $this->member->name.' a rejoint votre projet '.$this->project->title.'.';
    }

    protected function url(): string
    {
        return route('projects.show', $this->project);
    }

    protected function icon(): string
    {
        return '🚀';
    }

    protected function sendsMail(): bool
    {
        return true;
    }

    public function databaseType(object $notifiable): string
    {
        return 'project_joined';
    }
}

<?php

namespace App\Notifications;

use App\Models\Resource;

/**
 * "Ressource publiée": to the followers of the author.
 */
class ResourcePublished extends AfriCommitNotification
{
    public function __construct(public Resource $resource) {}

    protected function message(): string
    {
        return $this->resource->user->name.' a publié une ressource : '.$this->resource->title.'.';
    }

    protected function url(): string
    {
        return route('resources.show', $this->resource);
    }

    protected function icon(): string
    {
        return '📦';
    }

    public function databaseType(object $notifiable): string
    {
        return 'resource_published';
    }
}

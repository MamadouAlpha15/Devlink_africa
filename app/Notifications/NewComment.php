<?php

namespace App\Notifications;

use App\Models\Comment;

/**
 * "Commentaire": someone commented on one of my resources.
 */
class NewComment extends DevLinkNotification
{
    public function __construct(public Comment $comment) {}

    protected function message(): string
    {
        return $this->comment->user->name.' a commenté votre ressource '.$this->comment->resource->title.'.';
    }

    protected function url(): string
    {
        return route('resources.show', $this->comment->resource);
    }

    protected function icon(): string
    {
        return '💬';
    }

    public function databaseType(object $notifiable): string
    {
        return 'new_comment';
    }
}

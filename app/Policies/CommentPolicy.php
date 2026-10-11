<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /** The comment's author, the resource's author (moderates their page) or an admin. */
    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id
            || $comment->resource?->user_id === $user->id
            || $user->is_admin;
    }
}

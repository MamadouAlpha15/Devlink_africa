<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Only the two members of a private conversation can read it.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }

    /**
     * Replying also needs the other member to still be reachable (not suspended).
     */
    public function reply(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user)
            && ! $conversation->otherParticipant($user)->isSuspended();
    }
}

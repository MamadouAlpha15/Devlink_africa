<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Personal channel: navigation badge and conversation list.
Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

// One private conversation: only its two members may listen.
Broadcast::channel('conversations.{conversation}', function (User $user, Conversation $conversation) {
    return $conversation->hasParticipant($user);
});

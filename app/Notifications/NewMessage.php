<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\User;

/**
 * "Nouveau message". Sent once per conversation until the recipient reads it,
 * so a quick back-and-forth does not flood the bell.
 */
class NewMessage extends DevLinkNotification
{
    public function __construct(public User $sender, public Conversation $conversation) {}

    protected function message(): string
    {
        return $this->sender->name.' vous a envoyé un message.';
    }

    protected function url(): string
    {
        return route('messages.show', $this->conversation);
    }

    protected function icon(): string
    {
        return '💬';
    }

    public function databaseType(object $notifiable): string
    {
        return 'new_message';
    }
}

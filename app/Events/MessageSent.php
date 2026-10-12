<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A new private message, pushed instantly to the open conversation and to the recipient's
 * navigation badge. Broadcast right away (no queue worker needed for a chat).
 */
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array<string, mixed>  $conversationForRecipient  the conversation line as the recipient sees it
     */
    public function __construct(
        public Message $message,
        public User $recipient,
        public array $conversationForRecipient,
        public int $recipientUnreadTotal,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.'.$this->message->conversation_id),
            new PrivateChannel('App.Models.User.'.$this->recipient->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array{message: array{id: int, sender_id: int, body: string, sent_at: string}, conversation: array<string, mixed>, unread_total: int}
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
                'body' => $this->message->body,
                'sent_at' => $this->message->created_at->toIso8601String(),
            ],
            'conversation' => $this->conversationForRecipient,
            'unread_total' => $this->recipientUnreadTotal,
        ];
    }
}

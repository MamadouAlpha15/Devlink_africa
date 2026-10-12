<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The reader has seen the messages up to `lastReadId`: the sender's page switches them to "Lu".
 */
class MessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Conversation $conversation,
        public User $reader,
        public int $lastReadId,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversations.'.$this->conversation->id)];
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    /**
     * @return array{reader_id: int, read_up_to: int}
     */
    public function broadcastWith(): array
    {
        return [
            'reader_id' => $this->reader->id,
            'read_up_to' => $this->lastReadId,
        ];
    }
}

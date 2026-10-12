<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * "Messagerie" (cahier, section 13): private conversations, dated messages, read / unread.
 * Messages reach the other member in real time through Reverb; the page falls back
 * to polling when the WebSocket server cannot be reached.
 */
class Messaging
{
    /** Messages loaded when a conversation is opened. */
    public const HISTORY_SIZE = 100;

    public function send(Conversation $conversation, User $sender, string $body): Message
    {
        $recipient = $conversation->otherParticipant($sender);

        // Only the first unread message of a conversation rings the bell, not every line of a chat.
        $recipientWasUpToDate = ! $conversation->messages()
            ->unread()
            ->where('sender_id', $sender->id)
            ->exists();

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);
        $message->setRelation('conversation', $conversation);

        if ($recipientWasUpToDate) {
            $recipient->notify(new NewMessage($sender, $conversation));
        }

        $this->broadcastSafely(new MessageSent(
            $message,
            $recipient,
            $this->summary($conversation, $recipient),
            $recipient->unreadMessagesCount(),
        ));

        return $message;
    }

    /**
     * Mark the messages received by the reader in this conversation as read, and tell the sender.
     */
    public function markAsRead(Conversation $conversation, User $reader): void
    {
        $unread = $conversation->messages()
            ->unread()
            ->where('sender_id', '!=', $reader->id);

        $lastReadId = (clone $unread)->max('id');

        if ($lastReadId === null) {
            return;
        }

        $unread->update(['read_at' => now()]);

        $this->broadcastSafely(new MessagesRead($conversation, $reader, (int) $lastReadId));
    }

    /**
     * A WebSocket server that is down must never prevent a message from being saved:
     * the error is logged and the pages fall back to polling.
     *
     * The broadcast is sent when the pending broadcast is destroyed, so it must not
     * leave the rescued closure (an arrow function would return it, outside the try).
     */
    private function broadcastSafely(MessageSent|MessagesRead $event): void
    {
        rescue(function () use ($event): void {
            broadcast($event)->toOthers();
        });
    }

    /**
     * The viewer's conversations that contain at least one message, most recent first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function inbox(User $viewer): Collection
    {
        return Conversation::query()
            ->involving($viewer)
            ->whereNotNull('last_message_at')
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($messages) => $messages
                ->unread()
                ->where('sender_id', '!=', $viewer->id)])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn (Conversation $conversation) => $this->summary($conversation, $viewer));
    }

    /**
     * The latest messages of a conversation, oldest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function history(Conversation $conversation, ?int $afterId = null): Collection
    {
        return $conversation->messages()
            ->when($afterId, fn ($messages) => $messages->where('id', '>', $afterId))
            ->latest('id')
            ->limit(self::HISTORY_SIZE)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (Message $message) => $this->present($message));
    }

    /**
     * Id of the last message of the viewer that the other member has read (shows "Lu").
     */
    public function readUpTo(Conversation $conversation, User $viewer): int
    {
        return (int) $conversation->messages()
            ->where('sender_id', $viewer->id)
            ->whereNotNull('read_at')
            ->max('id');
    }

    /**
     * @return array{id: int, sender_id: int, body: string, sent_at: string}
     */
    public function present(Message $message): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'body' => $message->body,
            'sent_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * One line of the conversation list, as seen by the viewer.
     *
     * @return array{id: int, url: string, other: array{name: string, initials: string, avatar_url: ?string, profile_url: string}, last: ?array{body: string, mine: bool, sent_at: string}, unread: int}
     */
    public function summary(Conversation $conversation, User $viewer): array
    {
        $other = $conversation->otherParticipant($viewer);
        $last = $conversation->relationLoaded('latestMessage')
            ? $conversation->latestMessage
            : $conversation->latestMessage()->first();

        return [
            'id' => $conversation->id,
            'url' => route('messages.show', $conversation),
            'other' => [
                'name' => $other->name,
                'initials' => $other->initials,
                'avatar_url' => $other->avatar_url,
                'profile_url' => route('devs.show', $other),
            ],
            'last' => $last ? [
                'body' => Str::limit($last->body, 80),
                'mine' => $last->sender_id === $viewer->id,
                'sent_at' => $last->created_at->toIso8601String(),
            ] : null,
            'unread' => (int) ($conversation->unread_count ?? $conversation->messages()
                ->unread()
                ->where('sender_id', '!=', $viewer->id)
                ->count()),
        ];
    }
}

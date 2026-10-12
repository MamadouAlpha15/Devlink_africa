<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_one_id', 'user_two_id', 'last_message_at'])]
class Conversation extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * Get (or start) the private conversation between two users.
     */
    public static function between(User $a, User $b): self
    {
        [$one, $two] = $a->id < $b->id ? [$a, $b] : [$b, $a];

        return static::firstOrCreate(['user_one_id' => $one->id, 'user_two_id' => $two->id]);
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Conversations where the user is either participant.
     */
    public function scopeInvolving(Builder $query, User $user): void
    {
        $query->where(fn (Builder $pair) => $pair
            ->where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id));
    }

    public function hasParticipant(User $user): bool
    {
        return in_array($user->id, [$this->user_one_id, $this->user_two_id], true);
    }

    public function otherParticipantId(User $user): int
    {
        return $this->user_one_id === $user->id ? $this->user_two_id : $this->user_one_id;
    }

    /**
     * The member on the other side of the conversation.
     */
    public function otherParticipant(User $user): User
    {
        return $this->user_one_id === $user->id ? $this->userTwo : $this->userOne;
    }
}

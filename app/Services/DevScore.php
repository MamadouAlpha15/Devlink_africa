<?php

namespace App\Services;

use App\Enums\PointAction;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DevScore
{
    public function __construct(private BadgeAwarder $badges) {}

    /**
     * Give a user the points for an action, keep the cached total in sync,
     * then check whether the action unlocked a badge.
     */
    public function award(User $user, PointAction $action, ?Model $subject = null): PointTransaction
    {
        $transaction = $this->record($user, $action, $action->points(), $subject);

        $this->badges->evaluate($user);

        return $transaction;
    }

    /**
     * Take back the points of an action that was undone (a like removed),
     * so points cannot be farmed by repeating the action.
     */
    public function revoke(User $user, PointAction $action, ?Model $subject = null): PointTransaction
    {
        return $this->record($user, $action, -$action->points(), $subject);
    }

    private function record(User $user, PointAction $action, int $points, ?Model $subject): PointTransaction
    {
        return DB::transaction(function () use ($user, $action, $points, $subject) {
            $transaction = $user->pointTransactions()->create([
                'action' => $action,
                'points' => $points,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
            ]);

            // The cached total never goes below zero (unsigned column).
            $points >= 0
                ? $user->increment('devscore', $points)
                : $user->decrement('devscore', min(-$points, $user->devscore));

            return $transaction;
        });
    }
}

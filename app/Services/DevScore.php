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
        $transaction = DB::transaction(function () use ($user, $action, $subject) {
            $transaction = $user->pointTransactions()->create([
                'action' => $action,
                'points' => $action->points(),
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
            ]);

            $user->increment('devscore', $transaction->points);

            return $transaction;
        });

        $this->badges->evaluate($user);

        return $transaction;
    }
}

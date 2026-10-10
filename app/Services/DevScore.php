<?php

namespace App\Services;

use App\Enums\PointAction;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DevScore
{
    /**
     * Give a user the points for an action and keep the cached total in sync.
     */
    public function award(User $user, PointAction $action, ?Model $subject = null): PointTransaction
    {
        return DB::transaction(function () use ($user, $action, $subject) {
            $transaction = $user->pointTransactions()->create([
                'action' => $action,
                'points' => $action->points(),
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
            ]);

            $user->increment('devscore', $transaction->points);

            return $transaction;
        });
    }
}

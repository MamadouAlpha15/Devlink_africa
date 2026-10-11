<?php

namespace App\Http\Controllers;

use App\Enums\PointAction;
use App\Models\Badge;
use App\Search\Ranking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * "Mon DevScore" (cahier, section 15): total, points per action, history, ranks and badges.
 */
class DevScoreController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load(['country', 'badges']);

        $totals = $user->pointTransactions()
            ->selectRaw('action, SUM(points) as total, COUNT(*) as times')
            ->groupBy('action')
            ->get()
            ->keyBy(fn ($row) => $row->action->value);

        // One line per action of the barème, even when the user has not done it yet.
        $breakdown = collect(PointAction::cases())->map(fn (PointAction $action) => [
            'action' => $action,
            'points_each' => $action->points(),
            'times' => (int) ($totals[$action->value]->times ?? 0),
            'total' => (int) ($totals[$action->value]->total ?? 0),
        ]);

        $earnedIds = $user->badges->pluck('id');

        return view('devscore.show', [
            'user' => $user,
            'breakdown' => $breakdown,
            'history' => $user->pointTransactions()->with('subject')->latest()->limit(20)->get(),
            'ranks' => [
                'africa' => Ranking::make()->rankOf($user),
                'country' => $user->country ? Ranking::make($user->country->slug)->rankOf($user) : null,
                'month' => Ranking::make(period: 'month')->rankOf($user),
            ],
            'badges' => Badge::orderBy('id')->get()->map(fn (Badge $badge) => [
                'badge' => $badge,
                'earned_at' => $earnedIds->contains($badge->id)
                    ? Carbon::parse($user->badges->find($badge->id)->pivot->awarded_at)
                    : null,
            ]),
        ]);
    }
}

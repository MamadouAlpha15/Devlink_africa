<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FollowController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('follow', $user);

        $changes = $request->user()->following()->syncWithoutDetaching([$user->id]);

        // Only a new follow notifies (clicking "Suivre" twice does not).
        if ($changes['attached'] !== []) {
            $user->notify(new NewFollower($request->user()));
        }

        return back()->with('status', 'Vous suivez maintenant '.$user->name.'.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $request->user()->following()->detach($user->id);

        return back()->with('status', 'Vous ne suivez plus '.$user->name.'.');
    }
}

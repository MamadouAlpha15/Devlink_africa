<?php

namespace App\Http\Controllers;

use App\Enums\ProjectMemberRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DeveloperController extends Controller
{
    /**
     * Public developer profile: /devs/{username}.
     */
    public function show(Request $request, User $user): View
    {
        // A private or suspended profile looks like it does not exist.
        abort_unless(Gate::allows('view', $user), 404);

        $user->load(['country', 'skills', 'badges'])
            ->loadCount([
                'followers',
                'following',
                'projects',
                'projects as collaborations_count' => fn ($query) => $query->where('project_members.role', '!=', ProjectMemberRole::Owner->value),
                'resources' => fn ($query) => $query->published(),
            ]);

        $viewer = $request->user();

        return view('devs.show', [
            'user' => $user,
            'projects' => $user->projects()->with('country')->latest('project_members.joined_at')->limit(6)->get(),
            'resources' => $user->resources()->published()->latest()->limit(6)->get(),
            'ratingAvg' => $user->resources()->published()->avg('rating_avg'),
            'isOwner' => $viewer?->is($user) ?? false,
            'isFollowing' => $viewer ? $viewer->following()->whereKey($user->id)->exists() : false,
            'canFollow' => $viewer ? Gate::forUser($viewer)->allows('follow', $user) : false,
        ]);
    }
}

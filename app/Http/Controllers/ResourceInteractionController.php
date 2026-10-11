<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Resource;
use App\Services\ResourceLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Likes, ratings (1 to 5 stars) and comments on a resource.
 */
class ResourceInteractionController extends Controller
{
    public function like(Request $request, Resource $resource, ResourceLibrary $library): RedirectResponse
    {
        Gate::authorize('interact', $resource);

        $library->like($request->user(), $resource);

        return back()->with('status', 'Vous aimez cette ressource.');
    }

    public function unlike(Request $request, Resource $resource, ResourceLibrary $library): RedirectResponse
    {
        $library->unlike($request->user(), $resource);

        return back()->with('status', 'Vous n\'aimez plus cette ressource.');
    }

    /**
     * Rate (or re-rate) the resource.
     */
    public function review(Request $request, Resource $resource, ResourceLibrary $library): RedirectResponse
    {
        Gate::authorize('interact', $resource);

        $validated = $request->validate(
            ['rating' => ['required', 'integer', 'between:1,5'], 'comment' => ['nullable', 'string', 'max:1000']],
            [],
            ['rating' => 'note', 'comment' => 'commentaire'],
        );

        $library->review($request->user(), $resource, (int) $validated['rating'], $validated['comment'] ?? null);

        return back()->with('status', 'Merci pour votre note !');
    }

    public function deleteReview(Request $request, Resource $resource, ResourceLibrary $library): RedirectResponse
    {
        $library->deleteReview($request->user(), $resource);

        return back()->with('status', 'Note supprimée.');
    }

    public function comment(Request $request, Resource $resource, ResourceLibrary $library): RedirectResponse
    {
        Gate::authorize('comment', $resource);

        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']], [], ['body' => 'commentaire']);

        $library->comment($request->user(), $resource, $validated['body']);

        return back()->with('status', 'Commentaire publié.');
    }

    public function deleteComment(Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return back()->with('status', 'Commentaire supprimé.');
    }
}

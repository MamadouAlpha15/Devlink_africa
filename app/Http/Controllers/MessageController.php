<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Messaging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * "Messagerie" (cahier, section 13): conversation list, one conversation, sending and reading.
 */
class MessageController extends Controller
{
    public function __construct(private Messaging $messaging) {}

    public function index(Request $request): View
    {
        return view('messages.index', [
            'conversations' => $this->messaging->inbox($request->user()),
            'conversation' => null,
        ]);
    }

    /**
     * "Contacter" from a profile: open (or create) the conversation with this member.
     */
    public function start(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('message', $user);

        return redirect()->route('messages.show', Conversation::between($request->user(), $user));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        $viewer = $request->user();
        $this->messaging->markAsRead($conversation, $viewer);
        $conversation->load(['userOne', 'userTwo']);

        return view('messages.index', [
            'conversations' => $this->messaging->inbox($viewer),
            'conversation' => $conversation,
            'other' => $conversation->otherParticipant($viewer),
            'messages' => $this->messaging->history($conversation),
            'readUpTo' => $this->messaging->readUpTo($conversation, $viewer),
            'canReply' => Gate::allows('reply', $conversation),
        ]);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        Gate::authorize('reply', $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [], ['body' => 'message']);

        $message = $this->messaging->send($conversation, $request->user(), trim($validated['body']));

        return $request->expectsJson()
            ? response()->json(['message' => $this->messaging->present($message)], 201)
            : redirect()->route('messages.show', $conversation);
    }

    /**
     * Called by the open conversation when a new message arrives.
     */
    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $this->messaging->markAsRead($conversation, $request->user());

        return response()->json(['unread_total' => $request->user()->unreadMessagesCount()]);
    }

    /**
     * Fallback when the WebSocket server cannot be reached: the page asks every few seconds.
     */
    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $afterId = max(0, (int) $request->query('after'));

        return response()->json([
            'messages' => $this->messaging->history($conversation, $afterId),
            'read_up_to' => $this->messaging->readUpTo($conversation, $request->user()),
        ]);
    }
}

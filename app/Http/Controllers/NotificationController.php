<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Notifications (cahier, section 14). Near real time: the bell asks /notifications/poll every 10 seconds.
 */
class NotificationController extends Controller
{
    /** Notifications shown in the bell's dropdown. */
    private const DROPDOWN_SIZE = 8;

    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    /**
     * Called by the bell every 10 seconds.
     * Without `since`: the latest notifications (first load, no sound).
     * With `since` (the `server_time` of the previous answer): only what arrived since then (→ sound).
     */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = $this->parseSince($request->query('since'));

        $notifications = $since
            // ">=" plus de-duplication by id in the browser: nothing is missed within the same second.
            ? $user->notifications()->where('created_at', '>=', $since)->limit(20)->get()
            : $user->notifications()->limit(self::DROPDOWN_SIZE)->get();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'notifications' => $notifications->map(fn (DatabaseNotification $notification) => $this->present($notification))->values(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Click on a notification: mark it as read and open its page.
     */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('dashboard'));
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $request->expectsJson()
            ? response()->json(['unread' => 0])
            : back()->with('status', 'Toutes les notifications sont marquées comme lues.');
    }

    /**
     * @return array{id: string, message: string, icon: string, url: string, read: bool, time: string}
     */
    private function present(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'message' => $notification->data['message'] ?? '',
            'icon' => $notification->data['icon'] ?? '🔔',
            'url' => route('notifications.open', $notification->id),
            'read' => $notification->read_at !== null,
            'time' => $notification->created_at->diffForHumans(),
        ];
    }

    private function parseSince(mixed $since): ?Carbon
    {
        if (! is_string($since) || $since === '') {
            return null;
        }

        try {
            return Carbon::parse($since);
        } catch (\Throwable) {
            return null;
        }
    }
}

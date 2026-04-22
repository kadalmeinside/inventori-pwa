<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Halaman daftar notifikasi (Inertia).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notifications = AppNotification::where('user_id', $user->id)
            ->latest()
            ->paginate(30);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Return 10 most recent notifications as pure JSON (for bell dropdown).
     */
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = AppNotification::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get(['id', 'title', 'body', 'url', 'type', 'tag', 'read_at', 'created_at']);

        return response()->json([
            'data'  => $notifications,
            'unread'=> $notifications->whereNull('read_at')->count(),
        ]);
    }

    /**
     * Return unread count as JSON (untuk polling ringan).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = AppNotification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark single notification as read.
     */
    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        $this->authorize('update', $notification);
        $notification->markAsRead();
        return response()->json(['ok' => true]);
    }

    /**
     * Mark all user notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, AppNotification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);
        $notification->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Clear all read notifications.
     */
    public function clearRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->whereNotNull('read_at')
            ->delete();

        return response()->json(['ok' => true]);
    }
}

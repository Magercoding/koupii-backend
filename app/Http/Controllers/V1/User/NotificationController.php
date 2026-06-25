<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()->paginate(15);

        $notifications->getCollection()->transform(function ($notification) {
            $data = $notification->data;
            if (is_string($data)) {
                $data = json_decode($data, true) ?? [];
            }
            $notification->data = $data;
            return $notification;
        });

        return response()->json([
            'message' => 'Notifications retrieved successfully',
            'data' => [
                'unread_count' => $user->unreadNotifications()->count(),
                'notifications' => $notifications,
            ],
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete a specific notification.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->delete();
        }

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }
}

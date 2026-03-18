<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DatabaseNotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->notifications();

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        $notifications = $query
            ->orderByDesc('created_at')
            ->paginateFromRequest();

        return DatabaseNotificationResource::collection($notifications);
    }

    public function markAsRead(Request $request, string $notification): DatabaseNotificationResource
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($notification);

        $notification->markAsRead();

        return new DatabaseNotificationResource($notification);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json(['unreadCount' => $count]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarkNotificationAsReadRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $notifications = $this->notificationService->listForUser($request->user(), $perPage);

        return response()->json([
            'data' => NotificationResource::collection($notifications)->resolve(),
            'pagination' => [
                'page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'total_pages' => $notifications->lastPage(),
            ],
            'unread_count' => $this->notificationService->countUnread($request->user()),
        ], 200);
    }

    public function unread(Request $request)
    {
        $limit = (int) $request->input('limit', 5);

        $notifications = $this->notificationService->listUnread($request->user(), $limit);

        return response()->json([
            'data' => NotificationResource::collection($notifications)->resolve(),
            'count' => $this->notificationService->countUnread($request->user()),
        ], 200);
    }

    public function show(Notification $notification)
    {
        $this->authorizeOwnership($notification);
        $notification->load('type');

        return response()->json(new NotificationResource($notification), 200);
    }

    public function markAsRead(MarkNotificationAsReadRequest $request, Notification $notification)
    {
        $this->notificationService->markAsRead($notification);

        return response()->json(['message' => 'Notification marcada como lida'], 200);
    }

    private function authorizeOwnership(Notification $notification): void
    {
        abort_unless($notification->user_id === auth()->id(), 403);
    }
}


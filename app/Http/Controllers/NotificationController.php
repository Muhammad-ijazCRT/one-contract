<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function readUser(UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $this->notifications->markUserAsRead($notification);

        return redirect($this->notifications->urlForUserNotification($notification));
    }

    public function markAllAdminRead(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $this->notifications->markAllAdminAsRead($request->user());

        return response()->json([
            'ok' => true,
            'unread' => 0,
        ]);
    }

    public function readAdmin(string $type, int $id): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_unless(in_array($type, ['deposit', 'withdrawal', 'support'], true), 404);

        $this->notifications->markAdminItemAsRead(auth()->user(), $type, $id);

        return redirect($this->notifications->urlForAdminItem($type, $id));
    }
}

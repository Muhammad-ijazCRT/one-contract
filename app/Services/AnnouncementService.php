<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;

class AnnouncementService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ChatService $chat,
    ) {}

    public function broadcast(string $title, string $message): int
    {
        $count = 0;

        User::query()
            ->where('role', UserRole::User)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($title, $message, &$count) {
                foreach ($users as $user) {
                    $this->notifications->notifyUser($user, 'announcement', $title, $message);
                    $count++;
                }
            });

        $this->chat->postAnnouncement($title, $message);

        return $count;
    }
}

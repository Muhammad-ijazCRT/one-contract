<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chat,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->chat->ensureChannelDefaults();
        $this->chat->touchPresence($user);
        $this->chat->markSeen($user);

        $channel = strtolower((string) $request->query('channel', 'global'));
        if (! array_key_exists($channel, ChatService::CHANNELS)) {
            $channel = 'global';
        }

        $room = $this->chat->roomFor($channel);
        $messages = $this->chat->latestMessages($room);
        $pinned = $this->chat->pinnedMessage($room);

        $serialized = $messages
            ->reject(fn (ChatMessage $m) => $m->is_pinned)
            ->map(fn (ChatMessage $m) => $this->chat->serializeMessage($m, $user))
            ->values();

        return view('user.chat.index', [
            'user' => $user,
            'channel' => $channel,
            'channels' => $this->chat->channels(),
            'room' => $room,
            'messages' => $serialized,
            'pinned' => $pinned ? $this->chat->serializeMessage($pinned, $user) : null,
            'canPost' => $this->chat->canPost($user, $room),
            'onlineCount' => $this->chat->onlineCount(),
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->chat->touchPresence($user);

        $room = $this->chat->roomFor((string) $request->query('room', 'global'));
        abort_unless($this->chat->canAccess($user, $room), 403);
        $afterId = (int) $request->query('after_id', 0);
        $messages = $this->chat->latestMessages($room, $afterId);

        $latestId = (int) ($messages->max('id') ?: $afterId);
        $this->chat->markSeen($user, $latestId > 0 ? $latestId : null);

        return response()->json([
            'room' => $room->slug,
            'online' => $this->chat->onlineCount(),
            'can_post' => $this->chat->canPost($user, $room),
            'unread' => $this->chat->unreadCountFor($user),
            'pinned' => ($pinned = $this->chat->pinnedMessage($room))
                ? $this->chat->serializeMessage($pinned, $user)
                : null,
            'messages' => $messages
                ->reject(fn (ChatMessage $m) => $m->is_pinned && $afterId <= 0)
                ->map(fn (ChatMessage $m) => $this->chat->serializeMessage($m, $user))
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'room' => ['nullable', 'string', 'max:40'],
        ]);

        $user = $request->user();
        $this->chat->touchPresence($user);
        $room = $this->chat->roomFor($data['room'] ?? 'global');
        abort_unless($this->chat->canAccess($user, $room), 403);
        $message = $this->chat->send($user, $room, $data['body']);
        $message->load('user:id,name,member_id,role,avatar');

        return response()->json([
            'message' => $this->chat->serializeMessage($message, $user),
            'online' => $this->chat->onlineCount(),
        ], 201);
    }

    public function react(Request $request, ChatMessage $message): JsonResponse
    {
        $data = $request->validate([
            'emoji' => ['required', 'string', 'max:8'],
        ]);

        $user = $request->user();
        $this->chat->touchPresence($user);
        $message->loadMissing('room');
        if ($message->room) {
            abort_unless($this->chat->canAccess($user, $message->room), 403);
        }
        $updated = $this->chat->react($message, $user, $data['emoji']);

        return response()->json([
            'message' => $this->chat->serializeMessage($updated, $user),
        ]);
    }
}

<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\UserRole;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\RpsvMatch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatService
{
    /** @var list<string> */
    public const REACTION_EMOJIS = ['👍', '❤️', '🔥', '😂', '🎉', '👏'];

    /** @var list<string> */
    public const COMPOSER_EMOJIS = ['😀', '😁', '😂', '🤩', '😍', '🥳', '😎', '🙏', '👍', '👎', '❤️', '🔥', '🎉', '🚀', '💯', '✅', '💪', '🤝', '😭', '🤔'];

    /** @var array<string, array{name: string, type: string, icon: string, can_post: bool}> */
    public const CHANNELS = [
        'global' => [
            'name' => 'Global',
            'type' => 'global',
            'icon' => 'fa-globe',
            'can_post' => true,
        ],
        'announcements' => [
            'name' => 'Announcements',
            'type' => 'channel',
            'icon' => 'fa-bullhorn',
            'can_post' => false,
        ],
        'support' => [
            'name' => 'Support',
            'type' => 'channel',
            'icon' => 'fa-headset',
            'can_post' => true,
        ],
        'rules' => [
            'name' => 'Rules',
            'type' => 'channel',
            'icon' => 'fa-shield-halved',
            'can_post' => false,
        ],
    ];

    /** @var array<string, string> */
    private const GAME_ROOMS = [
        'prediction' => 'Prediction Chat',
        'azubogu' => 'Azubogu Chat',
    ];

    public function channels(): array
    {
        return self::CHANNELS;
    }

    public function roomFor(string $slug): ChatRoom
    {
        $slug = strtolower(trim($slug));

        if (isset(self::CHANNELS[$slug])) {
            $meta = self::CHANNELS[$slug];

            return ChatRoom::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $meta['name'], 'type' => $meta['type']]
            );
        }

        if (isset(self::GAME_ROOMS[$slug])) {
            return ChatRoom::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => self::GAME_ROOMS[$slug], 'type' => 'game']
            );
        }

        if (preg_match('/^rpsv-(\d+)$/', $slug, $m)) {
            return ChatRoom::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => 'RPSV Match #'.$m[1], 'type' => 'match']
            );
        }

        return $this->roomFor('global');
    }

    public function canAccess(User $user, ChatRoom $room): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! preg_match('/^rpsv-(\d+)$/', $room->slug, $matches)) {
            return true;
        }

        $match = RpsvMatch::query()->find((int) $matches[1]);

        return $match !== null && $match->isPlayer($user->id);
    }

    public function canPost(User $user, ChatRoom $room): bool
    {
        if (! $this->canAccess($user, $room)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $slug = $room->slug;
        if (! isset(self::CHANNELS[$slug])) {
            return true;
        }

        return (bool) self::CHANNELS[$slug]['can_post'];
    }

    public function ensureChannelDefaults(): void
    {
        foreach (array_keys(self::CHANNELS) as $slug) {
            $this->roomFor($slug);
        }

        $global = $this->roomFor('global');
        if (! $global->messages()->where('is_pinned', true)->exists()) {
            $author = $this->systemAuthor();
            if ($author) {
                ChatMessage::query()->create([
                    'chat_room_id' => $global->id,
                    'user_id' => $author->id,
                    'body' => 'Welcome to One-Contract Global Chat! Play fair. Be respectful. Have fun! 🚀',
                    'is_pinned' => true,
                    'reaction_emoji' => null,
                    'reaction_count' => 0,
                ]);
            }
        }

        $rules = $this->roomFor('rules');
        if ($rules->messages()->count() === 0) {
            $author = $this->systemAuthor();
            if ($author) {
                $lines = [
                    '1. Be respectful — no harassment, hate speech, or personal attacks.',
                    '2. No spam, scams, or phishing links.',
                    '3. Do not share private keys, passwords, or sensitive account details.',
                    '4. Play fair in games. Cheating or collusion may result in bans.',
                    '5. Support requests belong in Support. Admins will reply as soon as possible.',
                ];
                foreach ($lines as $line) {
                    ChatMessage::query()->create([
                        'chat_room_id' => $rules->id,
                        'user_id' => $author->id,
                        'body' => $line,
                        'is_pinned' => false,
                    ]);
                }
            }
        }

        $support = $this->roomFor('support');
        if (! $support->messages()->where('is_pinned', true)->exists()) {
            $author = $this->systemAuthor();
            if ($author) {
                ChatMessage::query()->create([
                    'chat_room_id' => $support->id,
                    'user_id' => $author->id,
                    'body' => 'Need help? Describe your issue here. Our team monitors this channel and will assist you.',
                    'is_pinned' => true,
                ]);
            }
        }

        $announcements = $this->roomFor('announcements');
        if ($announcements->messages()->count() === 0) {
            $author = $this->systemAuthor();
            if ($author) {
                ChatMessage::query()->create([
                    'chat_room_id' => $announcements->id,
                    'user_id' => $author->id,
                    'body' => 'Official announcements will appear here. Stay tuned for platform updates, rewards, and events.',
                    'is_pinned' => true,
                    'reaction_emoji' => '📢',
                    'reaction_count' => 0,
                ]);
            }
        }
    }

    public function postAnnouncement(string $title, string $message): void
    {
        $author = $this->systemAuthor();
        if (! $author) {
            return;
        }

        $room = $this->roomFor('announcements');
        $body = trim($title) !== '' ? trim($title).' — '.trim($message) : trim($message);

        ChatMessage::query()->create([
            'chat_room_id' => $room->id,
            'user_id' => $author->id,
            'body' => Str::limit($body, 500, ''),
            'is_pinned' => false,
            'reaction_emoji' => '📢',
            'reaction_count' => 0,
        ]);
    }

    public function pinnedMessage(ChatRoom $room): ?ChatMessage
    {
        return ChatMessage::query()
            ->with('user:id,name,member_id,role,avatar')
            ->where('chat_room_id', $room->id)
            ->where('is_pinned', true)
            ->latest('id')
            ->first();
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function latestMessages(ChatRoom $room, int $afterId = 0, int $limit = 50): Collection
    {
        return ChatMessage::query()
            ->with('user:id,name,member_id,role,avatar')
            ->where('chat_room_id', $room->id)
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->when($afterId <= 0, fn ($q) => $q->orderByDesc('id')->limit($limit))
            ->when($afterId > 0, fn ($q) => $q->orderBy('id')->limit($limit))
            ->get()
            ->when($afterId <= 0, fn ($c) => $c->sortBy('id')->values());
    }

    public function send(User $user, ChatRoom $room, string $body): ChatMessage
    {
        if (! $this->canPost($user, $room)) {
            throw ValidationException::withMessages([
                'body' => 'You cannot post in this channel.',
            ]);
        }

        $body = trim(Str::of($body)->replaceMatches('/\s+/', ' ')->toString());

        if ($body === '' || mb_strlen($body) > 500) {
            throw ValidationException::withMessages([
                'body' => 'Message must be 1–500 characters.',
            ]);
        }

        $recent = ChatMessage::query()
            ->where('user_id', $user->id)
            ->where('chat_room_id', $room->id)
            ->where('created_at', '>=', now()->subSeconds(3))
            ->exists();

        if ($recent) {
            throw ValidationException::withMessages([
                'body' => 'Please wait a moment before sending again.',
            ]);
        }

        $body = strip_tags($body);

        return ChatMessage::query()->create([
            'chat_room_id' => $room->id,
            'user_id' => $user->id,
            'body' => $body,
            'is_pinned' => false,
            'reaction_count' => 0,
            'reactions' => [],
        ]);
    }

    public function react(ChatMessage $message, User $user, string $emoji = '👍'): ChatMessage
    {
        $emoji = trim($emoji);
        if ($emoji === '' || ! in_array($emoji, self::REACTION_EMOJIS, true)) {
            throw ValidationException::withMessages([
                'emoji' => 'Invalid reaction.',
            ]);
        }

        $reactions = is_array($message->reactions) ? $message->reactions : [];
        $cacheKey = 'chat:ureact:'.$message->id.':'.$user->id;
        $previous = Cache::get($cacheKey);

        if (is_string($previous) && $previous === $emoji) {
            $reactions[$emoji] = max(0, (int) ($reactions[$emoji] ?? 0) - 1);
            if (($reactions[$emoji] ?? 0) <= 0) {
                unset($reactions[$emoji]);
            }
            Cache::forget($cacheKey);
        } else {
            if (is_string($previous) && isset($reactions[$previous])) {
                $reactions[$previous] = max(0, (int) $reactions[$previous] - 1);
                if ($reactions[$previous] <= 0) {
                    unset($reactions[$previous]);
                }
            }
            $reactions[$emoji] = (int) ($reactions[$emoji] ?? 0) + 1;
            Cache::put($cacheKey, $emoji, now()->addDays(30));
        }

        [$topEmoji, $total] = $this->summarizeReactions($reactions);

        $message->reactions = $reactions;
        $message->reaction_emoji = $topEmoji;
        $message->reaction_count = $total;
        $message->save();

        return $message->fresh(['user:id,name,member_id,role,avatar']);
    }

    /**
     * @param  array<string, int>  $reactions
     * @return array{0: ?string, 1: int}
     */
    private function summarizeReactions(array $reactions): array
    {
        $total = 0;
        $topEmoji = null;
        $topCount = 0;
        foreach ($reactions as $emoji => $count) {
            $count = (int) $count;
            if ($count <= 0) {
                continue;
            }
            $total += $count;
            if ($count > $topCount) {
                $topCount = $count;
                $topEmoji = (string) $emoji;
            }
        }

        return [$topEmoji, $total];
    }

    public function touchPresence(User $user): void
    {
        $key = 'chat:online';
        $map = Cache::get($key, []);
        if (! is_array($map)) {
            $map = [];
        }
        $cutoff = now()->subMinutes(5)->timestamp;
        $map[$user->id] = now()->timestamp;
        $map = array_filter($map, fn ($ts) => is_numeric($ts) && (int) $ts >= $cutoff);
        Cache::put($key, $map, now()->addMinutes(15));
    }

    public function onlineCount(): int
    {
        $map = Cache::get('chat:online', []);
        if (! is_array($map)) {
            return 1;
        }
        $cutoff = now()->subMinutes(5)->timestamp;
        $alive = array_filter($map, fn ($ts) => is_numeric($ts) && (int) $ts >= $cutoff);

        return max(1, count($alive));
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMessage(ChatMessage $message, ?User $viewer = null): array
    {
        $author = $message->user;
        $isAdmin = $author?->role === UserRole::Admin;
        $vip = $isAdmin ? 0 : $this->vipLevelFor($author);

        return [
            'id' => $message->id,
            'body' => $message->body,
            'user' => $author?->name ?? 'User',
            'avatar' => $author?->avatarUrl(),
            'mine' => $viewer && $message->user_id === $viewer->id,
            'is_admin' => (bool) $isAdmin,
            'is_pinned' => (bool) $message->is_pinned,
            'vip' => $vip,
            'badge' => $isAdmin ? 'ADMIN' : ($vip > 0 ? 'Level '.$vip : null),
            'tone' => $isAdmin ? 'admin' : (
                stripos($author?->name ?? '', 'cryptoqueen') !== false ? 'purple' : (
                    stripos($author?->name ?? '', 'cryptoking') !== false ? 'blue' : (
                        stripos($author?->name ?? '', 'kingtrader') !== false ? 'red' : (
                            stripos($author?->name ?? '', 'betmaster') !== false || stripos($author?->name ?? '', 'luckystar') !== false ? 'gold' : 'green'
                        )
                    )
                )
            ),
            'reactions' => $this->normalizeReactions($message),
            'reaction_emoji' => $message->reaction_emoji,
            'reaction_count' => (int) $message->reaction_count,
            'my_reaction' => $viewer
                ? Cache::get('chat:ureact:'.$message->id.':'.$viewer->id)
                : null,
            'at' => $message->created_at?->format('H:i'),
            'day' => $message->created_at?->isToday()
                ? 'Today'
                : ($message->created_at?->isYesterday() ? 'Yesterday' : $message->created_at?->format('M j, Y')),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function normalizeReactions(ChatMessage $message): array
    {
        $reactions = is_array($message->reactions) ? $message->reactions : [];
        if ($reactions === [] && $message->reaction_emoji && (int) $message->reaction_count > 0) {
            $reactions[$message->reaction_emoji] = (int) $message->reaction_count;
        }

        $clean = [];
        foreach ($reactions as $emoji => $count) {
            $count = (int) $count;
            if ($count > 0 && in_array((string) $emoji, self::REACTION_EMOJIS, true)) {
                $clean[(string) $emoji] = $count;
            }
        }

        return $clean;
    }

    public function vipLevelFor(?User $user): int
    {
        if (! $user) {
            return 1;
        }

        $cacheKey = 'chat:vip:'.$user->id;

        return (int) Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            $deposited = (float) $user->deposits()
                ->whereIn('status', [DepositStatus::Active, DepositStatus::Completed])
                ->sum('amount');
            $invested = (float) $user->clubInvestments()->sum('amount');

            return match (true) {
                $deposited >= 10000 || $invested >= 5000 => 5,
                $deposited >= 5000 || $invested >= 2500 => 4,
                $deposited >= 2000 || $invested >= 1000 => 3,
                $deposited >= 500 || $invested >= 250 => 2,
                default => 1,
            };
        });
    }

    public function markSeen(User $user, ?int $upToMessageId = null): void
    {
        $roomIds = ChatRoom::query()
            ->whereIn('slug', array_keys(self::CHANNELS))
            ->pluck('id');

        $maxId = $upToMessageId;
        if ($maxId === null) {
            $maxId = $roomIds->isEmpty()
                ? 0
                : (int) ChatMessage::query()->whereIn('chat_room_id', $roomIds)->max('id');
        }

        $previous = (int) Cache::get($this->seenCacheKey($user), 0);
        $next = max($previous, $maxId);

        Cache::put($this->seenCacheKey($user), $next, now()->addDays(30));
        session([$this->seenCacheKey($user) => $next]);
    }

    public function unreadCountFor(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        $lastSeenId = max(
            (int) Cache::get($this->seenCacheKey($user), 0),
            (int) session($this->seenCacheKey($user), 0)
        );

        $roomIds = ChatRoom::query()
            ->whereIn('slug', array_keys(self::CHANNELS))
            ->pluck('id');

        if ($roomIds->isEmpty()) {
            return 0;
        }

        return (int) ChatMessage::query()
            ->whereIn('chat_room_id', $roomIds)
            ->where('user_id', '!=', $user->id)
            ->where('id', '>', $lastSeenId)
            ->when($user->created_at, fn ($query) => $query->where('created_at', '>', $user->created_at))
            ->count();
    }

    private function seenCacheKey(User $user): string
    {
        return 'chat:last_seen_id:'.$user->id;
    }

    private function systemAuthor(): ?User
    {
        return User::query()->where('role', UserRole::Admin)->orderBy('id')->first()
            ?? User::query()->orderBy('id')->first();
    }
}

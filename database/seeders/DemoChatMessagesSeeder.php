<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoChatMessagesSeeder extends Seeder
{
    public function run(): void
    {
        $room = ChatRoom::query()->firstOrCreate(
            ['slug' => 'global'],
            ['name' => 'Global', 'type' => 'global']
        );

        // Demo users matching the reference screenshot
        $usersData = [
            'admin' => [
                'name' => 'GameMaster',
                'email' => 'gamemaster@one-contract.com',
                'role' => UserRole::Admin,
            ],
            'naijaboss' => [
                'name' => 'NaijaBoss',
                'email' => 'naijaboss@example.com',
                'role' => UserRole::User,
            ],
            'cryptoqueen' => [
                'name' => 'CryptoQueen',
                'email' => 'cryptoqueen@example.com',
                'role' => UserRole::User,
            ],
            'cryptoking' => [
                'name' => 'CryptoKing',
                'email' => 'cryptoking@example.com',
                'role' => UserRole::User,
            ],
            'betmaster' => [
                'name' => 'BetMaster',
                'email' => 'betmaster@example.com',
                'role' => UserRole::User,
            ],
            'luckystar' => [
                'name' => 'LuckyStar',
                'email' => 'luckystar@example.com',
                'role' => UserRole::User,
            ],
            'kingtrader' => [
                'name' => 'KingTrader',
                'email' => 'kingtrader@example.com',
                'role' => UserRole::User,
            ],
        ];

        $users = [];
        foreach ($usersData as $key => $data) {
            $users[$key] = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => $data['role'],
                    'status' => UserStatus::Active,
                    'member_id' => 'OC' . rand(100000, 999999),
                    'referral_code' => strtoupper($key) . rand(10, 99),
                    'email_verified_at' => now(),
                ]
            );
        }

        // Messages exactly matching the reference image
        $messages = [
            [
                'user' => $users['naijaboss'],
                'body' => 'Just won my match! 🔥 This platform is amazing!',
                'time_sub' => 15,
                'is_pinned' => false,
                'reactions' => ['👍' => 12],
            ],
            [
                'user' => $users['cryptoqueen'],
                'body' => 'Anyone playing Rock Paper Scissors? 👋',
                'time_sub' => 13,
                'is_pinned' => false,
                'reactions' => ['❤️' => 8],
            ],
            [
                'user' => $users['cryptoking'],
                'body' => 'Yes! Let\'s play. Add me! 🎮',
                'time_sub' => 12,
                'is_pinned' => false,
                'reactions' => ['🔥' => 6],
            ],
            [
                'user' => $users['admin'],
                'body' => "📢 Welcome to One Contract!\nPlay responsibly and follow the community rules.",
                'time_sub' => 10,
                'is_pinned' => true,
                'reactions' => ['👍' => 20, '❤️' => 10],
            ],
            [
                'user' => $users['betmaster'],
                'body' => 'This platform is really different. Smooth and fair!',
                'time_sub' => 9,
                'is_pinned' => false,
                'reactions' => ['👍' => 5],
            ],
            [
                'user' => $users['luckystar'],
                'body' => 'Just claimed my reward! ✅',
                'time_sub' => 7,
                'is_pinned' => false,
                'reactions' => ['❤️' => 7],
            ],
            [
                'user' => $users['kingtrader'],
                'body' => 'Good luck to everyone! 🍀',
                'time_sub' => 6,
                'is_pinned' => false,
                'reactions' => ['👍' => 4],
            ],
        ];

        foreach ($messages as $m) {
            $created = Carbon::now()->subMinutes($m['time_sub']);
            ChatMessage::query()->create([
                'chat_room_id' => $room->id,
                'user_id' => $m['user']->id,
                'body' => $m['body'],
                'is_pinned' => $m['is_pinned'],
                'reaction_emoji' => array_key_first($m['reactions']),
                'reaction_count' => array_sum($m['reactions']),
                'reactions' => $m['reactions'],
                'created_at' => $created,
                'updated_at' => $created,
            ]);
        }
    }
}


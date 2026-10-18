<?php

namespace Database\Seeders;

use App\Enums\DepositStatus;
use App\Enums\RewardType;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Deposit;
use App\Models\Reward;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Treasury;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@one-contract.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
                'member_id' => 'OC000001',
                'referral_code' => 'ADMIN001',
                'email_verified_at' => now(),
            ]
        );

        $user = User::query()->updateOrCreate(
            ['email' => 'user@one-contract.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
                'status' => UserStatus::Active,
                'member_id' => 'OC123456',
                'referral_code' => 'JOHN1234',
                'email_verified_at' => now(),
            ]
        );

        if (! Treasury::query()->exists()) {
            Treasury::query()->create([
                'balance' => 50000,
                'reserved_balance' => 0,
            ]);
        }

        $settings = [
            'daily_reward_rate' => ['value' => '0.01', 'group' => 'rewards'],
            'withdrawal_interval_days' => ['value' => '7', 'group' => 'withdrawals'],
            'platform_wallet_network' => ['value' => 'TRC20', 'group' => 'deposits'],
            'referral_level_1' => ['value' => '0.05', 'group' => 'referrals'],
            'referral_level_2' => ['value' => '0.03', 'group' => 'referrals'],
            'referral_level_3' => ['value' => '0.02', 'group' => 'referrals'],
            'currency_symbol' => ['value' => '$', 'group' => 'platform'],
            'currency_code' => ['value' => 'USDT', 'group' => 'platform'],
            'deposits_enabled' => ['value' => '1', 'group' => 'deposits'],
            'savings_target_days' => ['value' => '100', 'group' => 'savings'],
            'savings_max_daily' => ['value' => '450.00', 'group' => 'savings'],
            'savings_reward_percent' => ['value' => '12.5', 'group' => 'savings'],
        ];

        foreach ($settings as $key => $data) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $data['value'], 'group' => $data['group']]
            );
        }

        $this->call(DualWalletSystemSeeder::class);

        $deposit = Deposit::query()->firstOrCreate(
            ['transaction_hash' => '0xabc123def4567890approved'],
            [
                'user_id' => $user->id,
                'amount' => 1000,
                'deposited_at' => now()->subDays(5),
                'status' => DepositStatus::Active,
                'approved_at' => now()->subDays(5),
                'approved_by' => $admin->id,
            ]
        );

        $pendingUser = User::query()->updateOrCreate(
            ['email' => 'shafi@one-contract.com'],
            [
                'name' => 'shafi',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
                'status' => UserStatus::Active,
                'member_id' => 'OC308050',
                'referral_code' => 'SHAFI3080',
                'email_verified_at' => now(),
            ]
        );

        Deposit::query()->firstOrCreate(
            ['transaction_hash' => '0xpending9876543210abcdef'],
            [
                'user_id' => $pendingUser->id,
                'amount' => 500,
                'deposited_at' => now()->subHours(2),
                'status' => DepositStatus::Pending,
            ]
        );

        if (! Transaction::query()->where('user_id', $pendingUser->id)->where('type', TransactionType::Deposit)->exists()) {
            Transaction::query()->create([
                'user_id' => $pendingUser->id,
                'type' => TransactionType::Deposit,
                'amount' => 500,
                'description' => 'USDT Deposit (Pending)',
                'status' => 'pending',
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ]);
        }

        if (! Reward::query()->where('user_id', $user->id)->where('type', RewardType::DailyReward)->exists()) {
            Reward::query()->create([
                'user_id' => $user->id,
                'deposit_id' => $deposit->id,
                'type' => RewardType::DailyReward,
                'amount' => 125,
                'credited_at' => now()->subDay(),
            ]);
        }

        if (! Reward::query()->where('user_id', $user->id)->where('type', RewardType::ReferralCommission)->where('referral_level', 1)->exists()) {
            Reward::query()->create([
                'user_id' => $user->id,
                'type' => RewardType::ReferralCommission,
                'amount' => 12.50,
                'referral_level' => 1,
                'credited_at' => now()->subDays(2),
            ]);
        }

        if (! Reward::query()->where('user_id', $user->id)->where('type', RewardType::ReferralCommission)->where('referral_level', 2)->exists()) {
            Reward::query()->create([
                'user_id' => $user->id,
                'type' => RewardType::ReferralCommission,
                'amount' => 7.50,
                'referral_level' => 2,
                'credited_at' => now()->subDays(3),
            ]);
        }

        if (! Reward::query()->where('user_id', $user->id)->where('type', RewardType::ReferralCommission)->where('referral_level', 3)->exists()) {
            Reward::query()->create([
                'user_id' => $user->id,
                'type' => RewardType::ReferralCommission,
                'amount' => 5.50,
                'referral_level' => 3,
                'credited_at' => now()->subDays(4),
            ]);
        }

        if (! Transaction::query()->where('user_id', $user->id)->where('type', TransactionType::Deposit)->exists()) {
            Transaction::query()->insert([
                [
                    'user_id' => $user->id,
                    'type' => TransactionType::Deposit,
                    'amount' => 1000,
                    'description' => 'USDT Deposit',
                    'status' => 'completed',
                    'created_at' => now()->subDays(5),
                    'updated_at' => now()->subDays(5),
                ],
                [
                    'user_id' => $user->id,
                    'type' => TransactionType::DailyReward,
                    'amount' => 5,
                    'description' => 'Daily Reward (0.5%)',
                    'status' => 'completed',
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subDay(),
                ],
                [
                    'user_id' => $user->id,
                    'type' => TransactionType::ReferralCommission,
                    'amount' => 2.5,
                    'description' => 'Referral Commission (Level 1)',
                    'status' => 'completed',
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ],
            ]);
        }

        if (! UserNotification::query()->where('user_id', $user->id)->where('type', 'announcement')->exists()) {
            UserNotification::query()->insert([
                [
                    'user_id' => $user->id,
                    'type' => 'announcement',
                    'title' => 'System Maintenance Notice',
                    'message' => 'Scheduled maintenance on Sunday 2:00 AM UTC.',
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subDay(),
                ],
                [
                    'user_id' => $user->id,
                    'type' => 'welcome',
                    'title' => 'Welcome to One-Contract™',
                    'message' => 'Your account has been created successfully. Start earning today!',
                    'created_at' => now()->subDays(5),
                    'updated_at' => now()->subDays(5),
                ],
            ]);
        }
    }
}

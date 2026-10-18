<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\RewardType;
use App\Enums\TransactionType;
use App\Enums\WithdrawalStatus;
use App\Enums\WithdrawalType;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Carbon;

class UserWalletService
{
    public function __construct(
        private readonly RewardCalculationService $rewardCalculation,
        private readonly MultiWalletService $multiWallet,
    ) {}

    public function totalDeposited(User $user): float
    {
        return (float) $user->deposits()
            ->whereIn('status', [DepositStatus::Active, DepositStatus::Withdrawn, DepositStatus::Completed])
            ->sum('amount');
    }

    public function activeBalance(User $user): float
    {
        return $user->activePrincipal();
    }

    public function totalDailyRewards(User $user): float
    {
        return (float) $user->rewards()
            ->where('type', RewardType::DailyReward)
            ->sum('amount');
    }

    public function referralEarnings(User $user): float
    {
        return (float) $user->rewards()
            ->whereIn('type', RewardType::referralTypeValues())
            ->sum('amount');
    }

    public function totalWithdrawn(User $user): float
    {
        return (float) $user->withdrawalRequests()
            ->where('status', WithdrawalStatus::Completed)
            ->sum('amount');
    }

    public function availableRewardBalance(User $user): float
    {
        $earned = $this->totalDailyRewards($user) + $this->referralEarnings($user);

        $reserved = (float) $user->withdrawalRequests()
            ->where('type', WithdrawalType::Reward)
            ->whereIn('status', [
                WithdrawalStatus::Pending,
                WithdrawalStatus::Approved,
                WithdrawalStatus::Completed,
            ])
            ->sum('amount');

        return max(0, round($earned - $reserved, 8));
    }

    public function lastCompletedWithdrawal(User $user): ?WithdrawalRequest
    {
        return $user->withdrawalRequests()
            ->where('status', WithdrawalStatus::Completed)
            ->latest('processed_at')
            ->first();
    }

    public function intervalAnchor(User $user): ?Carbon
    {
        $lastWithdrawal = $this->lastCompletedWithdrawal($user);

        if ($lastWithdrawal?->processed_at) {
            return Carbon::parse($lastWithdrawal->processed_at);
        }

        return null;
    }

    public function nextWithdrawalAt(User $user): ?Carbon
    {
        $anchor = $this->intervalAnchor($user);

        if (! $anchor) {
            // No completed withdrawal yet — Main Wallet withdraw is available now.
            return null;
        }

        return $anchor->copy()->addDays(PlatformSettings::withdrawalIntervalDays());
    }

    public function canWithdraw(User $user): bool
    {
        $next = $this->nextWithdrawalAt($user);

        return $next === null || $next->isPast();
    }

    /**
     * Daily earnings across schemes, savings, lending, referrals, and gaming wins.
     *
     * @return array<int, array{date: string, rewards: float, referral: float, gaming: float, total: float}>
     */
    public function earningsChart(User $user, int $days = 30): array
    {
        $days = max(2, $days);
        $start = now()->subDays($days - 1)->startOfDay();

        $rewards = $user->rewards()
            ->where(function ($query) use ($start) {
                $query->where('credited_at', '>=', $start)
                    ->orWhere(function ($inner) use ($start) {
                        $inner->whereNull('credited_at')->where('created_at', '>=', $start);
                    });
            })
            ->get()
            ->groupBy(function ($reward) {
                $at = $reward->credited_at ?? $reward->created_at;

                return $at?->format('Y-m-d') ?? now()->format('Y-m-d');
            });

        $gaming = $user->transactions()
            ->whereIn('type', [
                TransactionType::PredictionWin,
                TransactionType::AzuboguWin,
                TransactionType::RpsvWin,
            ])
            ->where('created_at', '>=', $start)
            ->get()
            ->groupBy(fn ($tx) => $tx->created_at->format('Y-m-d'));

        $points = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');
            $dayRewards = $rewards[$date] ?? collect();
            $dayGaming = $gaming[$date] ?? collect();

            $product = (float) $dayRewards->whereIn('type', [
                RewardType::DailyReward,
                RewardType::ClubReward,
                RewardType::LendingInterest,
            ])->sum('amount');

            $referral = (float) $dayRewards->whereIn('type', RewardType::referralTypes())->sum('amount');
            $gamingWins = (float) $dayGaming->sum('amount');

            $points[] = [
                'date' => $date,
                'rewards' => $product,
                'referral' => $referral,
                'gaming' => $gamingWins,
                'total' => round($product + $referral + $gamingWins, 2),
            ];
        }

        return $points;
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(User $user): array
    {
        $clubRewards = $this->totalClubRewards($user);
        $referralEarnings = $this->referralEarnings($user);
        $savingsRewards = $this->totalRewardsByType($user, RewardType::SavingsInterest);
        $lendingRewards = $this->totalRewardsByType($user, RewardType::LendingInterest);
        $legacyDaily = $this->totalDailyRewards($user);
        $mainWallet = $this->multiWallet->balance($user, \App\Enums\WalletCurrency::Usdt);
        $totalInvested = $user->totalActiveClubInvestment();
        $gamingWins = (float) $user->transactions()
            ->whereIn('type', [
                TransactionType::PredictionWin,
                TransactionType::AzuboguWin,
                TransactionType::RpsvWin,
            ])
            ->sum('amount');

        return [
            'total_deposited' => $this->totalDeposited($user),
            'active_balance' => $totalInvested,
            'daily_reward' => $this->rewardCalculation->dailyRewardForUser($user),
            'total_rewards' => $clubRewards + $legacyDaily + $lendingRewards,
            'club_rewards' => $clubRewards,
            'savings_rewards' => $savingsRewards,
            'lending_rewards' => $lendingRewards,
            'legacy_daily_rewards' => $legacyDaily,
            'gaming_wins' => $gamingWins,
            'referral_earnings' => $referralEarnings,
            'total_withdrawn' => $this->totalWithdrawn($user),
            'wallet_balance' => $mainWallet,
            'main_wallet' => $mainWallet,
            'total_invested' => $totalInvested,
            'savings_saved' => (float) ($user->activeSavingsPlan()?->total_saved ?? 0),
        ];
    }

    public function totalRewardsByType(User $user, RewardType $type): float
    {
        return (float) $user->rewards()
            ->where('type', $type)
            ->sum('amount');
    }

    public function totalClubRewards(User $user): float
    {
        return (float) $user->rewards()
            ->where('type', RewardType::ClubReward)
            ->sum('amount');
    }

    public function referralStats(User $user): array
    {
        $stats = [];

        foreach ([1, 2, 3] as $level) {
            $stats[$level] = [
                'count' => $user->referrals()->where('level', $level)->count(),
                'earnings' => (float) $user->rewards()
                    ->whereIn('type', RewardType::referralTypeValues())
                    ->where('referral_level', $level)
                    ->sum('amount'),
                'rate' => PlatformSettings::referralRate($level) * 100,
            ];
        }

        return $stats;
    }
}

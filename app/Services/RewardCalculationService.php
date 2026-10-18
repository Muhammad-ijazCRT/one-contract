<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Support\Collection;

class RewardCalculationService
{
    /**
     * Simple (non-compounding) daily reward on original active principal only.
     * Daily Reward = Original Active Deposit × daily_reward_rate
     */
    public function dailyRewardForPrincipal(float $principal): float
    {
        if ($principal <= 0) {
            return 0.0;
        }

        return round($principal * PlatformSettings::dailyRewardRate(), 8);
    }

    public function dailyRewardForDeposit(Deposit $deposit): float
    {
        if ($deposit->status !== DepositStatus::Active) {
            return 0.0;
        }

        return $this->dailyRewardForPrincipal((float) $deposit->amount);
    }

    public function dailyRewardForUser(User $user): float
    {
        return $this->activeDepositsForUser($user)
            ->sum(fn (Deposit $deposit) => $this->dailyRewardForDeposit($deposit));
    }

    public function totalDailyRewardsOwed(): float
    {
        return $this->totalDailyRewardsOwedForPrincipal($this->totalActivePrincipal());
    }

    public function totalDailyRewardsOwedForPrincipal(float $principal): float
    {
        return $this->dailyRewardForPrincipal($principal);
    }

    public function totalActivePrincipal(): float
    {
        return (float) Deposit::query()
            ->where('status', DepositStatus::Active)
            ->sum('amount');
    }

    /**
     * @return Collection<int, Deposit>
     */
    public function activeDeposits(): Collection
    {
        return Deposit::query()
            ->where('status', DepositStatus::Active)
            ->get();
    }

    /**
     * @return Collection<int, Deposit>
     */
    public function activeDepositsForUser(User $user): Collection
    {
        return $user->deposits()
            ->where('status', DepositStatus::Active)
            ->get();
    }
}

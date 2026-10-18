<?php

namespace App\Services;

use App\Enums\RewardType;
use App\Enums\TransactionType;
use App\Models\Deposit;
use App\Models\Referral;
use App\Models\Reward;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyRewardProcessor
{
    public function __construct(
        private readonly RewardCalculationService $rewardCalculation,
        private readonly ReferralCommissionService $referralCommissions,
        private readonly NotificationService $notifications,
    ) {}

    public function process(?Carbon $forDate = null): int
    {
        $date = ($forDate ?? now())->copy()->startOfDay();
        $processed = 0;

        foreach ($this->rewardCalculation->activeDeposits() as $deposit) {
            if ($this->alreadyCredited($deposit, $date)) {
                continue;
            }

            $amount = $this->rewardCalculation->dailyRewardForDeposit($deposit);

            if ($amount <= 0) {
                continue;
            }

            DB::transaction(function () use ($deposit, $amount, $date) {
                $reward = Reward::query()->create([
                    'user_id' => $deposit->user_id,
                    'deposit_id' => $deposit->id,
                    'type' => RewardType::DailyReward,
                    'amount' => $amount,
                    'credited_at' => $date->copy()->endOfDay(),
                ]);

                Transaction::query()->create([
                    'user_id' => $deposit->user_id,
                    'type' => TransactionType::DailyReward,
                    'amount' => $amount,
                    'reference_type' => Reward::class,
                    'reference_id' => $reward->id,
                    'description' => 'Daily Reward ('.(PlatformSettings::dailyRewardRate() * 100).'%)',
                    'status' => 'completed',
                ]);

                $this->referralCommissions->creditForDailyReward($deposit->user, $amount, $deposit, $date);

                $this->notifications->notifyUser(
                    $deposit->user,
                    'reward',
                    'Daily Reward Credited',
                    number_format($amount, 2).' USDT has been added to your reward balance.',
                );
            });

            $processed++;
        }

        return $processed;
    }

    private function alreadyCredited(Deposit $deposit, Carbon $date): bool
    {
        return Reward::query()
            ->where('deposit_id', $deposit->id)
            ->where('type', RewardType::DailyReward)
            ->whereDate('credited_at', $date->toDateString())
            ->exists();
    }
}

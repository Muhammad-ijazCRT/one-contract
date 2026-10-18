<?php

namespace App\Services;

use App\Enums\RewardType;
use App\Enums\TransactionType;
use App\Models\ClubInvestment;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClubRewardProcessor
{
    public function __construct(
        private readonly ReferralCommissionService $referralCommissions,
        private readonly NotificationService $notifications,
    ) {}

    public function process(?Carbon $asOf = null): int
    {
        $asOf = $asOf ?? now();
        $processed = 0;

        $investments = ClubInvestment::query()
            ->with(['club', 'user'])
            ->where('status', \App\Enums\ClubInvestmentStatus::Active)
            ->where('amount', '>', 0)
            ->whereNotNull('next_reward_at')
            ->where('next_reward_at', '<=', $asOf)
            ->get();

        foreach ($investments as $investment) {
            if ($this->creditReward($investment, $asOf)) {
                $processed++;
            }
        }

        return $processed;
    }

    public function creditReward(ClubInvestment $investment, ?Carbon $asOf = null): bool
    {
        $asOf = $asOf ?? now();
        $club = $investment->club;

        if (! $club || ! $investment->isActive()) {
            return false;
        }

        $principal = (float) $investment->amount;

        if ($principal <= 0) {
            return false;
        }

        $percent = (float) $club->reward_percent;
        $cycleDays = max(1, (int) $club->cycle_days);
        // reward_percent is a daily rate; pay the full period amount each cycle
        $amount = round($principal * ($percent / 100) * $cycleDays, 8);

        if ($amount <= 0) {
            return false;
        }

        DB::transaction(function () use ($investment, $club, $amount, $asOf, $percent, $cycleDays) {
            $locked = ClubInvestment::query()->whereKey($investment->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isActive() || ! $locked->next_reward_at || $locked->next_reward_at->isFuture()) {
                return;
            }

            // Accrue matured reward — NOT credited to Main Wallet until user claims (with Platform Fee).
            Reward::query()->create([
                'user_id' => $locked->user_id,
                'deposit_id' => null,
                'club_investment_id' => $locked->id,
                'type' => RewardType::ClubReward,
                'amount' => $amount,
                'credited_at' => $asOf,
                'claimed_at' => null,
            ]);

            $locked->total_rewards_earned = (float) $locked->total_rewards_earned + $amount;
            $locked->last_reward_at = $asOf;
            $locked->next_reward_at = $asOf->copy()->addDays((int) $club->cycle_days);
            $locked->save();

            $this->referralCommissions->creditForClubReward($locked->user, $amount, $locked, $asOf);

            $this->notifications->notifyUser(
                $locked->user,
                'reward',
                'Club Reward Matured',
                number_format($amount, 2).' USDT from '.$club->name
                    .' ('.$percent.'% daily × '.$cycleDays.' days) is ready to claim. Pay the '.PlatformSettings::platformFeePercent()
                    .'% Platform Fee from Main Wallet to transfer the full reward.',
            );
        });

        return true;
    }
}

<?php

namespace App\Services;

use App\Enums\RewardType;
use App\Models\ClubInvestment;
use App\Models\Deposit;
use App\Models\PredictionBet;
use App\Models\Referral;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReferralCommissionService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Legacy daily deposit rewards do not earn referral commissions.
     * Referral applies to Investment Scheme rewards and Prediction (gaming) wins only — not savings or lending.
     */
    public function creditForDailyReward(User $earner, float $dailyRewardAmount, Deposit $deposit, Carbon $date): void
    {
        // No-op — savings/deposit daily rewards are excluded.
    }

    public function creditForClubReward(User $earner, float $rewardAmount, ClubInvestment $investment, Carbon $date): void
    {
        $this->creditUpline(
            earner: $earner,
            baseAmount: $rewardAmount,
            date: $date,
            depositId: null,
            clubInvestmentId: $investment->id,
            predictionBetId: null,
            rewardType: RewardType::InvestmentSchemeReferral,
            sourceLabel: 'investment scheme',
        );
    }

    public function creditForPredictionWin(User $earner, float $profitAmount, PredictionBet $bet, Carbon $date): void
    {
        $this->creditUpline(
            earner: $earner,
            baseAmount: $profitAmount,
            date: $date,
            depositId: null,
            clubInvestmentId: null,
            predictionBetId: $bet->id,
            rewardType: RewardType::GamingReferral,
            sourceLabel: 'prediction game',
        );
    }

    /** Gaming wins (Azubogu, etc.) — gaming referral only. */
    public function creditForGamingWin(User $earner, float $profitAmount, Carbon $date, string $sourceLabel = 'prediction game'): void
    {
        $this->creditUpline(
            earner: $earner,
            baseAmount: $profitAmount,
            date: $date,
            depositId: null,
            clubInvestmentId: null,
            predictionBetId: null,
            rewardType: RewardType::GamingReferral,
            sourceLabel: $sourceLabel,
        );
    }

    private function creditUpline(
        User $earner,
        float $baseAmount,
        Carbon $date,
        ?int $depositId,
        ?int $clubInvestmentId,
        ?int $predictionBetId,
        RewardType $rewardType,
        string $sourceLabel,
    ): void {
        $referrals = Referral::query()
            ->where('referred_id', $earner->id)
            ->whereIn('level', [1, 2, 3])
            ->with('referrer')
            ->get();

        foreach ($referrals as $referral) {
            $rate = PlatformSettings::referralRate($referral->level);

            if ($rate <= 0 || ! $referral->referrer) {
                continue;
            }

            $commission = round($baseAmount * $rate, 8);

            if ($commission <= 0) {
                continue;
            }

            DB::transaction(function () use ($referral, $commission, $depositId, $clubInvestmentId, $predictionBetId, $earner, $date, $sourceLabel, $rewardType) {
                $payload = [
                    'user_id' => $referral->referrer_id,
                    'deposit_id' => $depositId,
                    'club_investment_id' => $clubInvestmentId,
                    'type' => $rewardType,
                    'amount' => $commission,
                    'referral_level' => $referral->level,
                    'referred_user_id' => $earner->id,
                    'credited_at' => $date,
                    'claimed_at' => null,
                ];

                if (\Illuminate\Support\Facades\Schema::hasColumn('rewards', 'prediction_bet_id')) {
                    $payload['prediction_bet_id'] = $predictionBetId;
                }

                Reward::query()->create($payload);

                $this->notifications->notifyUser(
                    $referral->referrer,
                    'referral',
                    'Referral Commission Credited',
                    'You earned '.number_format($commission, 2).' USDT from Level '.$referral->level
                        .' '.$sourceLabel.' referral. Claim it from Rewards after paying the Platform Fee from Main Wallet.',
                );
            });
        }
    }
}

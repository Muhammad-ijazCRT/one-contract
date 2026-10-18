<?php

namespace App\Http\Controllers\User;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\InvestmentClub;
use App\Services\PlatformSettings;
use App\Services\UserWalletService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function __construct(
        private readonly UserWalletService $wallet,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $chart = $this->wallet->earningsChart($user, 30);
        $stats = $this->wallet->stats($user);

        $scheme = InvestmentClub::query()->where('is_active', true)->orderBy('sort_order')->first();
        $schemeDaily = $scheme ? (float) $scheme->reward_percent : 0.1;
        $schemeCycle = $scheme ? (int) $scheme->cycle_days : 35;
        $schemePayout = round($schemeDaily * $schemeCycle, 2);

        $rates = [
            'scheme_daily' => $schemeDaily,
            'scheme_cycle' => $schemeCycle,
            'scheme_payout' => $schemePayout,
            'savings' => PlatformSettings::savingsRewardPercent(),
            'lending_regular' => PlatformSettings::regularLendingDailyRatePercent(),
            'lending_premium' => PlatformSettings::premiumLendingDailyRatePercent(),
            'referral_l1' => PlatformSettings::referralRate(1) * 100,
            'referral_l2' => PlatformSettings::referralRate(2) * 100,
            'referral_l3' => PlatformSettings::referralRate(3) * 100,
            'prediction_payout' => PlatformSettings::predictionPayoutMultiplier(),
        ];

        return view('user.earnings.index', [
            'stats' => $stats,
            'chart' => $chart,
            'history' => $this->paginatedHistory($user),
            'rates' => $rates,
        ]);
    }

    /**
     * Merge reward rows + gaming win transactions into one newest-first history list.
     */
    private function paginatedHistory($user): LengthAwarePaginator
    {
        $rewardRows = $user->rewards()
            ->latest('credited_at')
            ->latest('id')
            ->get()
            ->filter(fn ($reward) => ($reward->type->value ?? '') !== 'savings_interest')
            ->map(function ($reward) {
                $type = $reward->type->value;

                return [
                    'source' => 'reward',
                    'type' => $type,
                    'label' => match ($type) {
                        'gaming_referral' => 'Gaming Referral',
                        'investment_scheme_referral' => 'Scheme Referral',
                        'referral_commission' => 'Referral',
                        'club_reward' => 'Investment Scheme',
                        'savings_interest' => 'Savings Reward',
                        'lending_interest' => 'Lending Reward',
                        'daily_reward' => 'Legacy Daily Reward',
                        default => str_replace('_', ' ', ucfirst($type)),
                    },
                    'badge' => match ($type) {
                        'referral_commission', 'gaming_referral', 'investment_scheme_referral' => 'earn-type-badge--referral',
                        'savings_interest' => 'earn-type-badge--savings',
                        'lending_interest' => 'earn-type-badge--lending',
                        'club_reward' => 'earn-type-badge--scheme',
                        default => 'earn-type-badge--reward',
                    },
                    'amount' => (float) $reward->amount,
                    'level' => $reward->referral_level,
                    'at' => $reward->credited_at ?? $reward->created_at,
                ];
            });

        $gamingRows = $user->transactions()
            ->whereIn('type', [
                TransactionType::PredictionWin,
                TransactionType::AzuboguWin,
            ])
            ->latest('id')
            ->get()
            ->map(function ($tx) {
                $isPrediction = $tx->type === TransactionType::PredictionWin;

                return [
                    'source' => 'gaming',
                    'type' => $tx->type->value,
                    'label' => $isPrediction ? 'Prediction Win' : 'Azubogu Win',
                    'badge' => 'earn-type-badge--gaming',
                    'amount' => (float) $tx->amount,
                    'level' => null,
                    'at' => $tx->created_at,
                ];
            });

        /** @var Collection<int, array<string, mixed>> $merged */
        $merged = $rewardRows
            ->concat($gamingRows)
            ->sortByDesc(fn (array $row) => optional($row['at'])->timestamp ?? 0)
            ->values();

        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $slice = $merged->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $merged->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}

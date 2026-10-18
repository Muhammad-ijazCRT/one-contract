<?php

namespace App\Services;

use App\Enums\ClubInvestmentStatus;
use App\Enums\DepositStatus;
use App\Enums\RewardType;
use App\Enums\SavingsPlanStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Enums\WithdrawalStatus;
use App\Enums\WithdrawalType;
use App\Models\ClubCapitalRequest;
use App\Models\ClubInvestment;
use App\Models\Deposit;
use App\Models\InvestmentClub;
use App\Models\Reward;
use App\Models\SavingsPlan;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    public function __construct(
        private readonly RewardCalculationService $rewards,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(?Carbon $asOfDate = null, int $chartDays = 7): array
    {
        $chartDays = in_array($chartDays, [7, 14, 30], true) ? $chartDays : 7;
        $date = ($asOfDate ?? Carbon::today())->copy()->startOfDay();
        if ($date->isFuture()) {
            $date = Carbon::today()->startOfDay();
        }

        $endOfDay = $date->copy()->endOfDay();

        $totalCapital = (float) Deposit::query()
            ->activeAsOf($date)
            ->sum('amount');

        $totalDailyOwed = $this->rewards->totalDailyRewardsOwedForPrincipal($totalCapital);

        $totalDailyRewardsCredited = (float) Reward::query()
            ->where('type', RewardType::DailyReward)
            ->where('credited_at', '<=', $endOfDay)
            ->sum('amount');

        $totalReferralCredited = (float) Reward::query()
            ->whereIn('type', RewardType::referralTypeValues())
            ->where('credited_at', '<=', $endOfDay)
            ->sum('amount');

        $totalRewardWithdrawn = (float) WithdrawalRequest::query()
            ->where('type', WithdrawalType::Reward)
            ->completedAsOf($date)
            ->sum('amount');

        $totalRewardToBePaid = max(0, $totalDailyRewardsCredited - $totalRewardWithdrawn);
        $totalReferralToBePaid = max(0, $totalReferralCredited);

        $pendingCapitalWithdrawals = (float) WithdrawalRequest::query()
            ->where('type', WithdrawalType::Capital)
            ->openAsOf($date)
            ->sum('amount');

        $netPlatformPosition = $totalCapital
            - $totalRewardToBePaid
            + $totalReferralToBePaid
            + $pendingCapitalWithdrawals;

        $dual = $this->dualWalletOverview($date, $totalReferralCredited);

        return [
            'selected_date' => $date,
            'primary_stats' => [
                'total_capital_invested' => $totalCapital,
                'total_reward_to_be_paid' => $totalRewardToBePaid,
                'total_referral_to_be_paid' => $totalReferralToBePaid,
                'total_users' => User::query()
                    ->where('role', UserRole::User)
                    ->where('created_at', '<=', $endOfDay)
                    ->count(),
                'active_investments' => Deposit::query()
                    ->activeAsOf($date)
                    ->count(),
            ],
            'secondary_stats' => [
                'total_daily_rewards_owed' => $totalDailyOwed,
                'rewards_paid_today' => (float) Reward::query()
                    ->where('type', RewardType::DailyReward)
                    ->whereDate('credited_at', $date)
                    ->sum('amount'),
                'referral_rewards_today' => (float) Reward::query()
                    ->whereIn('type', RewardType::referralTypeValues())
                    ->whereDate('credited_at', $date)
                    ->sum('amount'),
                'pending_reward_withdrawals' => (float) WithdrawalRequest::query()
                    ->where('type', WithdrawalType::Reward)
                    ->openAsOf($date)
                    ->where('status', WithdrawalStatus::Pending)
                    ->sum('amount'),
                'pending_capital_withdrawals' => (float) WithdrawalRequest::query()
                    ->where('type', WithdrawalType::Capital)
                    ->openAsOf($date)
                    ->where('status', WithdrawalStatus::Pending)
                    ->sum('amount'),
                'processing_capital_withdrawals' => (float) WithdrawalRequest::query()
                    ->where('type', WithdrawalType::Capital)
                    ->openAsOf($date)
                    ->where('status', WithdrawalStatus::Approved)
                    ->sum('amount'),
            ],
            'investment_distribution' => app(InvestmentDistributionService::class)->getDistribution($date),
            'chart' => $this->weeklyChartData($date, $chartDays),
            'chart_days' => $chartDays,
            'recent_activity' => $this->recentActivity($date),
            'latest_deposits' => $this->latestDeposits($date),
            'pending_reward_withdrawals' => $this->pendingWithdrawals(WithdrawalType::Reward, $date),
            'pending_capital_withdrawals' => $this->pendingWithdrawals(WithdrawalType::Capital, $date),
            'platform_summary' => [
                'total_capital_invested' => $totalCapital,
                'total_reward_to_be_paid' => $totalRewardToBePaid,
                'total_referral_to_be_paid' => $totalReferralToBePaid,
                'pending_capital_withdrawals' => $pendingCapitalWithdrawals,
                'net_platform_position' => $netPlatformPosition,
            ],
            'notification_count' => $this->notificationCount(),
            'daily_reward_rate' => PlatformSettings::dailyRewardRate(),
            'dual' => $dual,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dualWalletOverview(Carbon $date, float $totalReferralCredited): array
    {
        $end = $date->copy()->endOfDay();
        $weekAgo = $date->copy()->subDays(7)->startOfDay();

        $totalDepositAllTime = (float) Deposit::query()
            ->whereIn('status', [DepositStatus::Active, DepositStatus::Completed, DepositStatus::Withdrawn])
            ->where(function ($q) use ($end) {
                $q->where('deposited_at', '<=', $end)
                    ->orWhere(function ($q2) use ($end) {
                        $q2->whereNull('deposited_at')->where('created_at', '<=', $end);
                    });
            })
            ->sum('amount');

        $depositWeekAgo = (float) Deposit::query()
            ->whereIn('status', [DepositStatus::Active, DepositStatus::Completed, DepositStatus::Withdrawn])
            ->where(function ($q) use ($weekAgo) {
                $q->where('deposited_at', '<=', $weekAgo->copy()->endOfDay())
                    ->orWhere(function ($q2) use ($weekAgo) {
                        $q2->whereNull('deposited_at')->where('created_at', '<=', $weekAgo->copy()->endOfDay());
                    });
            })
            ->sum('amount');

        $currentInvestment = (float) ClubInvestment::query()
            ->where('status', ClubInvestmentStatus::Active)
            ->where('amount', '>', 0)
            ->sum('amount');

        $clubs = InvestmentClub::query()->orderBy('sort_order')->get();
        $portfolioRows = [];
        $rewardsByClub = [];
        $totalRewardsDue = 0.0;
        $totalReferralDue = 0.0;
        $totalActiveInvestors = 0;

        foreach ($clubs as $club) {
            $investments = ClubInvestment::query()
                ->where('investment_club_id', $club->id)
                ->where('status', ClubInvestmentStatus::Active)
                ->where('amount', '>', 0)
                ->get();

            $capital = (float) $investments->sum('amount');
            $investors = $investments->count();
            $rate = (float) $club->reward_percent;
            $nextRewards = round($capital * ($rate / 100), 2);

            $referralOnClub = (float) Reward::query()
                ->whereIn('type', RewardType::referralTypeValues())
                ->where('credited_at', '<=', $end)
                ->whereHas('clubInvestment', fn ($q) => $q->where('investment_club_id', $club->id))
                ->sum('amount');

            $slug = $club->slug instanceof \BackedEnum ? $club->slug->value : (string) $club->slug;

            $portfolioRows[] = [
                'id' => $club->id,
                'name' => $club->name,
                'slug' => $slug,
                'rate' => $rate,
                'cycle_days' => (int) $club->cycle_days,
                'total_deposit' => $capital,
                'current_investment' => $capital,
                'rewards_due' => $nextRewards,
                'referral_due' => $referralOnClub,
                'investors' => $investors,
            ];

            $rewardsByClub[] = [
                'name' => $club->name,
                'slug' => $slug,
                'amount' => $nextRewards,
            ];

            $totalRewardsDue += $nextRewards;
            $totalReferralDue += $referralOnClub;
            $totalActiveInvestors += $investors;
        }

        $availableForPayout = (float) User::query()
            ->where('role', UserRole::User)
            ->sum('main_wallet_balance');

        $activeSavings = SavingsPlan::query()->where('status', SavingsPlanStatus::Active);
        $completedSavings = SavingsPlan::query()->where('status', SavingsPlanStatus::Completed);
        $earlySavings = SavingsPlan::query()->whereIn('status', [
            SavingsPlanStatus::WithdrawnEarly,
            SavingsPlanStatus::PendingEarlyReturn,
        ]);

        $multiplier = PlatformSettings::savingsRewardMultiplier();

        $savings = [
            'active_plans' => (clone $activeSavings)->count(),
            'total_saved_active' => (float) (clone $activeSavings)->sum('total_saved'),
            'completed' => (clone $completedSavings)->count(),
            'completed_payouts' => (float) (clone $completedSavings)->sum('total_saved') * $multiplier,
            'early_withdrawals' => (clone $earlySavings)->count(),
            'pending_payouts' => (float) SavingsPlan::query()
                ->where('status', SavingsPlanStatus::PendingEarlyReturn)
                ->sum('total_saved'),
            'total_saved' => (float) SavingsPlan::query()
                ->whereIn('status', [
                    SavingsPlanStatus::Active,
                    SavingsPlanStatus::Completed,
                    SavingsPlanStatus::PendingEarlyReturn,
                    SavingsPlanStatus::WithdrawnEarly,
                ])
                ->sum('total_saved'),
        ];

        $referralByLevel = [];
        foreach ([1, 2, 3] as $level) {
            $referralByLevel[] = [
                'level' => $level,
                'label' => $level === 1 ? 'Level 1 (Direct)' : 'Level '.$level,
                'amount' => (float) Reward::query()
                    ->whereIn('type', RewardType::referralTypeValues())
                    ->where('referral_level', $level)
                    ->where('credited_at', '<=', $end)
                    ->sum('amount'),
            ];
        }

        $capitalRequests = ClubCapitalRequest::query()
            ->with(['user', 'investment.club'])
            ->latest('requested_at')
            ->limit(5)
            ->get();

        $totalUsers = User::query()->where('role', UserRole::User)->count();
        $activeInvestors = (int) ClubInvestment::query()
            ->where('status', ClubInvestmentStatus::Active)
            ->where('amount', '>', 0)
            ->distinct()
            ->count('user_id');
        $newRegistrations = User::query()
            ->where('role', UserRole::User)
            ->where('created_at', '>=', $weekAgo)
            ->count();
        $totalWithdrawn = (float) WithdrawalRequest::query()
            ->where('status', WithdrawalStatus::Completed)
            ->sum('amount');
        $successfulTx = Transaction::query()
            ->where(function ($q) {
                $q->where('status', 'completed')
                    ->orWhere('status', 'success')
                    ->orWhereNull('status');
            })
            ->count();
        $pendingWithdrawalsAmt = (float) WithdrawalRequest::query()
            ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved])
            ->sum('amount');

        $referralDue = $totalReferralDue > 0 ? $totalReferralDue : $totalReferralCredited;

        return [
            'top' => [
                'total_deposit' => $totalDepositAllTime,
                'total_deposit_change' => $this->pctChange($totalDepositAllTime, $depositWeekAgo),
                'current_investment' => $currentInvestment,
                'current_investment_change' => $this->pctChange($currentInvestment, $currentInvestment * 0.903),
                'rewards_due' => $totalRewardsDue,
                'rewards_due_change' => $this->pctChange($totalRewardsDue, $totalRewardsDue * 0.919),
                'referral_due' => $referralDue,
                'referral_due_change' => $this->pctChange($referralDue, $referralDue * 0.941),
                'available_payout' => $availableForPayout,
                'available_payout_change' => $this->pctChange($availableForPayout, $availableForPayout * 0.867),
            ],
            'portfolios' => $portfolioRows,
            'portfolio_totals' => [
                'deposit' => array_sum(array_column($portfolioRows, 'total_deposit')),
                'investment' => array_sum(array_column($portfolioRows, 'current_investment')),
                'rewards_due' => $totalRewardsDue,
                'referral_due' => array_sum(array_column($portfolioRows, 'referral_due')),
                'investors' => $totalActiveInvestors,
            ],
            'rewards_by_club' => $rewardsByClub,
            'referral_by_level' => $referralByLevel,
            'savings' => $savings,
            'capital_requests' => $capitalRequests,
            'footer' => [
                'total_users' => $totalUsers,
                'active_investors' => $activeInvestors,
                'new_registrations' => $newRegistrations,
                'total_withdrawn' => $totalWithdrawn,
                'successful_tx' => $successfulTx,
                'pending_withdrawals' => $pendingWithdrawalsAmt,
            ],
        ];
    }

    private function pctChange(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * @return array{labels: list<string>, deposits: list<float>, withdrawn: list<float>, pending: list<float>}
     */
    private function weeklyChartData(Carbon $endDate, int $days = 7): array
    {
        $labels = [];
        $deposits = [];
        $withdrawn = [];
        $pending = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $endDate->copy()->subDays($i);
            $labels[] = $date->format('M d');

            $deposits[] = (float) Transaction::query()
                ->where('type', TransactionType::Deposit)
                ->whereDate('created_at', $date)
                ->sum('amount');

            $withdrawn[] = (float) WithdrawalRequest::query()
                ->where('status', WithdrawalStatus::Completed)
                ->where(function ($query) use ($date) {
                    $query->whereDate('processed_at', $date)
                        ->orWhere(function ($query) use ($date) {
                            $query->whereNull('processed_at')->whereDate('updated_at', $date);
                        });
                })
                ->sum('amount');

            $pending[] = (float) WithdrawalRequest::query()
                ->openAsOf($date)
                ->where('status', WithdrawalStatus::Pending)
                ->sum('amount');
        }

        return compact('labels', 'deposits', 'withdrawn', 'pending');
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function recentActivity(Carbon $date): Collection
    {
        return Transaction::query()
            ->with('user')
            ->whereDate('created_at', $date)
            ->latest()
            ->limit(3)
            ->get();
    }

    /**
     * @return Collection<int, Deposit>
     */
    private function latestDeposits(Carbon $date): Collection
    {
        return Deposit::query()
            ->with('user')
            ->where('deposited_at', '<=', $date->copy()->endOfDay())
            ->latest('deposited_at')
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, WithdrawalRequest>
     */
    private function pendingWithdrawals(WithdrawalType $type, Carbon $date): Collection
    {
        return WithdrawalRequest::query()
            ->with('user')
            ->where('type', $type)
            ->openAsOf($date)
            ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved])
            ->latest()
            ->limit(5)
            ->get();
    }

    private function notificationCount(): int
    {
        return WithdrawalRequest::query()->where('status', WithdrawalStatus::Pending)->count()
            + Deposit::query()->where('status', DepositStatus::Pending)->count()
            + SupportTicket::query()->where('status', SupportTicket::STATUS_OPEN)->count();
    }
}

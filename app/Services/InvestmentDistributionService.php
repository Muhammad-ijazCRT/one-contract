<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\WithdrawalStatus;
use App\Enums\WithdrawalType;
use App\Models\Deposit;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Carbon;

class InvestmentDistributionService
{
    /**
     * @return array{
     *     active: float,
     *     withdrawn: float,
     *     pending: float,
     *     active_pct: float,
     *     withdrawn_pct: float,
     *     pending_pct: float,
     *     center_pct: float,
     *     center_label: string
     * }
     */
    public function getDistribution(?Carbon $asOfDate = null): array
    {
        $date = ($asOfDate ?? Carbon::today())->copy()->startOfDay();

        $active = (float) Deposit::query()
            ->activeAsOf($date)
            ->sum('amount');

        $endOfDay = $date->copy()->endOfDay();

        $withdrawnCapital = (float) Deposit::query()
            ->where('status', DepositStatus::Withdrawn)
            ->where('updated_at', '<=', $endOfDay)
            ->sum('amount');

        $withdrawnRewards = (float) WithdrawalRequest::query()
            ->where('type', WithdrawalType::Reward)
            ->completedAsOf($date)
            ->sum('amount');

        $withdrawn = $withdrawnCapital + $withdrawnRewards;

        $pendingCapital = (float) WithdrawalRequest::query()
            ->where('type', WithdrawalType::Capital)
            ->openAsOf($date)
            ->sum('amount');

        $pendingRewards = (float) WithdrawalRequest::query()
            ->where('type', WithdrawalType::Reward)
            ->openAsOf($date)
            ->sum('amount');

        $pending = $pendingCapital + $pendingRewards;

        $total = $active + $withdrawn + $pending;

        if ($total <= 0) {
            return [
                'active' => 0,
                'withdrawn' => 0,
                'pending' => 0,
                'active_pct' => 0,
                'withdrawn_pct' => 0,
                'pending_pct' => 0,
                'center_pct' => 0,
                'center_label' => 'No Data',
            ];
        }

        $activePct = round(($active / $total) * 100, 1);
        $withdrawnPct = round(($withdrawn / $total) * 100, 1);
        $pendingPct = round(100 - $activePct - $withdrawnPct, 1);

        $segments = [
            ['pct' => $activePct, 'label' => 'Active'],
            ['pct' => $withdrawnPct, 'label' => 'Withdrawn'],
            ['pct' => $pendingPct, 'label' => 'Pending'],
        ];

        usort($segments, fn ($a, $b) => $b['pct'] <=> $a['pct']);
        $top = $segments[0];

        return [
            'active' => $active,
            'withdrawn' => $withdrawn,
            'pending' => $pending,
            'active_pct' => $activePct,
            'withdrawn_pct' => $withdrawnPct,
            'pending_pct' => $pendingPct,
            'center_pct' => $top['pct'],
            'center_label' => $top['label'],
        ];
    }
}

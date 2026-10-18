<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\RewardType;
use App\Enums\WithdrawalStatus;
use App\Enums\WithdrawalType;
use App\Models\Deposit;
use App\Models\Reward;
use App\Models\Treasury;
use App\Models\WithdrawalRequest;

class TreasuryStatsService
{
    /**
     * @return array{
     *     balance: float,
     *     reserved_balance: float,
     *     available_liquidity: float,
     *     capacity_percent: float,
     *     health: string,
     *     currency_symbol: string,
     *     currency_code: string
     * }
     */
    public function getStats(): array
    {
        $record = Treasury::query()->first();

        $activeCapital = (float) Deposit::query()
            ->where('status', DepositStatus::Active)
            ->sum('amount');

        $withdrawnCapital = (float) Deposit::query()
            ->where('status', DepositStatus::Withdrawn)
            ->sum('amount');

        $balance = (float) ($record?->balance ?? 0);
        if ($balance <= 0) {
            $balance = $activeCapital + $withdrawnCapital;
        }

        $reserved = (float) ($record?->reserved_balance ?? 0);
        if ($reserved <= 0) {
            $reserved = (float) WithdrawalRequest::query()
                ->where('type', WithdrawalType::Capital)
                ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved])
                ->sum('amount');

            $reserved += max(0, (float) Reward::query()
                ->where('type', RewardType::DailyReward)
                ->sum('amount')
                - (float) WithdrawalRequest::query()
                    ->where('type', WithdrawalType::Reward)
                    ->where('status', WithdrawalStatus::Completed)
                    ->sum('amount'));
        }

        $available = max(0, $balance - $reserved);
        $capacityPercent = $balance > 0
            ? round(($available / $balance) * 100, 1)
            : 100.0;

        return [
            'balance' => $balance,
            'reserved_balance' => $reserved,
            'available_liquidity' => $available,
            'capacity_percent' => $capacityPercent,
            'health' => $this->healthLabel($capacityPercent),
            'currency_symbol' => PlatformSettings::currencySymbol(),
            'currency_code' => PlatformSettings::currencyCode(),
        ];
    }

    private function healthLabel(float $capacityPercent): string
    {
        if ($capacityPercent >= 70) {
            return 'Healthy';
        }

        if ($capacityPercent >= 40) {
            return 'Moderate';
        }

        return 'Low';
    }
}

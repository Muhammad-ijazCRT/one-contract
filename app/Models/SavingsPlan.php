<?php

namespace App\Models;

use App\Enums\SavingsDeductionStatus;
use App\Enums\SavingsPlanStatus;
use App\Services\PlatformSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsPlan extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'principal',
        'daily_amount',
        'target_days',
        'successful_days',
        'total_saved',
        'accrued_interest',
        'last_interest_date',
        'status',
        'started_at',
        'completed_at',
        'matured_at',
        'estimated_completion_at',
        'early_withdrawal_requested_at',
        'early_withdrawal_available_at',
    ];

    protected function casts(): array
    {
        return [
            'principal' => 'decimal:8',
            'daily_amount' => 'decimal:8',
            'target_days' => 'integer',
            'successful_days' => 'integer',
            'total_saved' => 'decimal:8',
            'accrued_interest' => 'decimal:8',
            'last_interest_date' => 'date',
            'status' => SavingsPlanStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'matured_at' => 'datetime',
            'estimated_completion_at' => 'datetime',
            'early_withdrawal_requested_at' => 'datetime',
            'early_withdrawal_available_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(SavingsDeduction::class);
    }

    public function remainingDays(): int
    {
        return max(0, (int) $this->target_days - (int) $this->successful_days);
    }

    public function isActive(): bool
    {
        return $this->status === SavingsPlanStatus::Active;
    }

    /** Target Amount = daily × target days (e.g. $1 × 100 = $100). */
    public function targetAmount(): float
    {
        $fromDaily = round((float) $this->daily_amount * max(1, (int) $this->target_days), 2);
        if ($fromDaily > 0) {
            return $fromDaily;
        }

        // Legacy lump-sum plans (principal set, daily = 0)
        return round(max((float) $this->principal, (float) $this->total_saved), 2);
    }

    /** Target Interest = reward% of Target Amount (e.g. 12.5% of $100 = $12.50). */
    public function targetInterest(?float $rewardPercent = null): float
    {
        $percent = $rewardPercent ?? PlatformSettings::savingsRewardPercent();

        return round($this->targetAmount() * ((float) $percent / 100), 2);
    }

    public function daysProgressPercent(): float
    {
        $target = max(1, (int) $this->target_days);

        return min(100, round(((int) $this->successful_days / $target) * 100, 1));
    }

    public function savedProgressPercent(): float
    {
        $target = $this->targetAmount();
        if ($target <= 0) {
            return 0;
        }

        return min(100, round(((float) $this->total_saved / $target) * 100, 1));
    }

    /**
     * Keep successful_days / total_saved aligned with successful daily deductions.
     */
    public function syncProgressFromDeductions(): bool
    {
        $success = $this->deductions()
            ->where('status', SavingsDeductionStatus::Success);

        $count = (int) (clone $success)->count();
        $sum = round((float) (clone $success)->sum('amount'), 8);

        if ($count > 0) {
            // Successful deductions are the source of truth for daily plans
            $days = $count;
            $saved = $sum;
        } else {
            $days = (int) $this->successful_days;
            $saved = (float) $this->total_saved;
            $daily = round((float) $this->daily_amount, 8);
            if ($daily > 0 && $saved > 0) {
                $days = max($days, (int) floor(($saved + 1e-9) / $daily));
            }
        }

        $dirty = false;

        if ($days !== (int) $this->successful_days) {
            $this->successful_days = $days;
            $dirty = true;
        }

        if (abs($saved - (float) $this->total_saved) > 0.00000001) {
            $this->total_saved = $saved;
            $dirty = true;
        }

        if ($dirty) {
            $remaining = max(0, (int) $this->target_days - (int) $this->successful_days);
            $this->estimated_completion_at = $remaining > 0 ? now()->addDays($remaining) : now();
            $this->save();
        }

        return $dirty;
    }
}

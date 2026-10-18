<?php

namespace App\Models;

use App\Enums\LendingPlanStatus;
use App\Enums\LendingPlanType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LendingPlan extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'principal',
        'daily_rate_percent',
        'duration_days',
        'accrued_interest',
        'status',
        'started_at',
        'matures_at',
        'matured_at',
        'completed_at',
        'last_interest_date',
        'early_withdrawal_requested_at',
        'early_withdrawal_available_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => LendingPlanType::class,
            'status' => LendingPlanStatus::class,
            'principal' => 'decimal:8',
            'daily_rate_percent' => 'decimal:4',
            'duration_days' => 'integer',
            'accrued_interest' => 'decimal:8',
            'started_at' => 'datetime',
            'matures_at' => 'datetime',
            'matured_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_interest_date' => 'date',
            'early_withdrawal_requested_at' => 'datetime',
            'early_withdrawal_available_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === LendingPlanStatus::Active;
    }
}

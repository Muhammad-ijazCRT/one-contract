<?php

namespace App\Models;

use App\Enums\SavingsDeductionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsDeduction extends Model
{
    protected $fillable = [
        'savings_plan_id',
        'user_id',
        'amount',
        'status',
        'deduction_date',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'status' => SavingsDeductionStatus::class,
            'deduction_date' => 'date',
            'processed_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SavingsPlan::class, 'savings_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

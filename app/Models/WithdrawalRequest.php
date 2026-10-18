<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use App\Enums\WithdrawalType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WithdrawalRequest extends Model
{
    protected $fillable = [
        'user_id',
        'deposit_id',
        'type',
        'currency',
        'amount',
        'fee',
        'wallet_address',
        'network',
        'status',
        'processed_at',
        'processed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'fee' => 'decimal:8',
            'processed_at' => 'datetime',
            'type' => WithdrawalType::class,
            'status' => WithdrawalStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function netAmount(): float
    {
        return round(max(0, (float) $this->amount - (float) $this->fee), 8);
    }

    public function scopeOpenAsOf(Builder $query, Carbon $date): Builder
    {
        $end = $date->copy()->endOfDay();

        return $query
            ->where('created_at', '<=', $end)
            ->where(function (Builder $query) use ($end) {
                $query->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved])
                    ->orWhere('processed_at', '>', $end);
            });
    }

    public function scopeCompletedAsOf(Builder $query, Carbon $date): Builder
    {
        $end = $date->copy()->endOfDay();

        return $query
            ->where('status', WithdrawalStatus::Completed)
            ->where(function (Builder $query) use ($end) {
                $query->where('processed_at', '<=', $end)
                    ->orWhere(function (Builder $query) use ($end) {
                        $query->whereNull('processed_at')
                            ->where('updated_at', '<=', $end);
                    });
            });
    }
}

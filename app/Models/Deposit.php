<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Enums\WalletCurrency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Deposit extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'credit_currency',
        'pay_currency',
        'transaction_hash',
        'payment_id',
        'payment_url',
        'payment_status',
        'pay_address',
        'purchase_id',
        'order_id',
        'network',
        'deposited_at',
        'status',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'deposited_at' => 'datetime',
            'approved_at' => 'datetime',
            'status' => DepositStatus::class,
        ];
    }

    public function creditWallet(): WalletCurrency
    {
        return WalletCurrency::tryNormalize($this->credit_currency)
            ?? WalletCurrency::tryFromPayCurrency($this->pay_currency)
            ?? WalletCurrency::Usdt;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function scopeActiveAsOf(Builder $query, Carbon $date): Builder
    {
        $end = $date->copy()->endOfDay();

        return $query
            ->where(function (Builder $query) use ($end) {
                $query->where(function (Builder $query) use ($end) {
                    $query->whereNotNull('approved_at')
                        ->where('approved_at', '<=', $end);
                })->orWhere(function (Builder $query) use ($end) {
                    $query->whereNull('approved_at')
                        ->where('deposited_at', '<=', $end);
                });
            })
            ->where(function (Builder $query) use ($end) {
                $query->where('status', DepositStatus::Active)
                    ->orWhere(function (Builder $query) use ($end) {
                        $query->where('status', DepositStatus::Withdrawn)
                            ->where('updated_at', '>', $end);
                    });
            });
    }
}

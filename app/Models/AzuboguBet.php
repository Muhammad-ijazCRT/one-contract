<?php

namespace App\Models;

use App\Enums\AzuboguBetStatus;
use App\Enums\WalletCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AzuboguBet extends Model
{
    protected $fillable = [
        'user_id',
        'azubogu_round_id',
        'numbers',
        'amount',
        'currency',
        'combined_multiplier',
        'payout',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'numbers' => 'array',
            'amount' => 'decimal:8',
            'combined_multiplier' => 'decimal:8',
            'payout' => 'decimal:8',
            'status' => AzuboguBetStatus::class,
        ];
    }

    public function walletCurrency(): WalletCurrency
    {
        return WalletCurrency::tryNormalize((string) ($this->currency ?: 'USDT'))
            ?? WalletCurrency::Usdt;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(AzuboguRound::class, 'azubogu_round_id');
    }
}

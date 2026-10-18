<?php

namespace App\Models;

use App\Enums\PredictionBetStatus;
use App\Enums\WalletCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionBet extends Model
{
    protected $fillable = [
        'user_id',
        'prediction_round_id',
        'outcome',
        'amount',
        'currency',
        'payout',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'outcome' => 'integer',
            'amount' => 'decimal:8',
            'payout' => 'decimal:8',
            'status' => PredictionBetStatus::class,
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
        return $this->belongsTo(PredictionRound::class, 'prediction_round_id');
    }
}

<?php

namespace App\Models;

use App\Enums\RewardType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reward extends Model
{
    protected $fillable = [
        'user_id',
        'deposit_id',
        'club_investment_id',
        'prediction_bet_id',
        'type',
        'amount',
        'referral_level',
        'referred_user_id',
        'credited_at',
        'claimed_at',
        'platform_fee_paid',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'platform_fee_paid' => 'decimal:8',
            'credited_at' => 'datetime',
            'claimed_at' => 'datetime',
            'type' => RewardType::class,
        ];
    }

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function clubInvestment(): BelongsTo
    {
        return $this->belongsTo(ClubInvestment::class);
    }

    public function predictionBet(): BelongsTo
    {
        return $this->belongsTo(PredictionBet::class);
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}

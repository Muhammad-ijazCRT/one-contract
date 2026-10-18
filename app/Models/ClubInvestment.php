<?php

namespace App\Models;

use App\Enums\ClubInvestmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClubInvestment extends Model
{
    protected $fillable = [
        'user_id',
        'investment_club_id',
        'amount',
        'status',
        'started_at',
        'last_reward_at',
        'next_reward_at',
        'total_rewards_earned',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'status' => ClubInvestmentStatus::class,
            'started_at' => 'datetime',
            'last_reward_at' => 'datetime',
            'next_reward_at' => 'datetime',
            'total_rewards_earned' => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(InvestmentClub::class, 'investment_club_id');
    }

    public function capitalRequests(): HasMany
    {
        return $this->hasMany(ClubCapitalRequest::class);
    }

    public function isActive(): bool
    {
        return $this->status === ClubInvestmentStatus::Active && (float) $this->amount > 0;
    }
}

<?php

namespace App\Models;

use App\Enums\PredictionRoundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictionRound extends Model
{
    protected $fillable = [
        'round_number',
        'status',
        'server_seed',
        'server_seed_hash',
        'client_seed',
        'nonce',
        'winning_outcome',
        'total_bets',
        'total_payout',
        'total_players',
        'betting_ends_at',
        'round_ends_at',
        'started_at',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PredictionRoundStatus::class,
            'nonce' => 'integer',
            'winning_outcome' => 'integer',
            'total_bets' => 'decimal:8',
            'total_payout' => 'decimal:8',
            'total_players' => 'integer',
            'betting_ends_at' => 'datetime',
            'round_ends_at' => 'datetime',
            'started_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function bets(): HasMany
    {
        return $this->hasMany(PredictionBet::class);
    }

    public function isBettingOpen(): bool
    {
        return $this->status === PredictionRoundStatus::Betting
            && $this->betting_ends_at->isFuture();
    }
}

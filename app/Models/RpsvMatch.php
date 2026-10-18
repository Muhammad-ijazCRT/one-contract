<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RpsvMatch extends Model
{
    protected $table = 'rpsv_matches';

    protected $fillable = [
        'match_code',
        'mode',
        'currency',
        'stake',
        'prize_pool',
        'platform_fee',
        'winner_payout',
        'player1_id',
        'player2_id',
        'player1_score',
        'player2_score',
        'player1_ready',
        'player2_ready',
        'current_round',
        'max_rounds',
        'status',
        'phase',
        'phase_ends_at',
        'winner_id',
        'forfeit_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'stake' => 'decimal:8',
            'prize_pool' => 'decimal:8',
            'platform_fee' => 'decimal:8',
            'winner_payout' => 'decimal:8',
            'player1_ready' => 'boolean',
            'player2_ready' => 'boolean',
            'phase_ends_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(RpsvRound::class, 'rpsv_match_id');
    }

    public function invite(): HasOne
    {
        return $this->hasOne(RpsvInvite::class, 'match_id');
    }

    public function opponentId(int $userId): ?int
    {
        if ($this->player1_id === $userId) {
            return $this->player2_id;
        }
        if ($this->player2_id === $userId) {
            return $this->player1_id;
        }

        return null;
    }

    public function isPlayer(int $userId): bool
    {
        return $this->player1_id === $userId || $this->player2_id === $userId;
    }
}

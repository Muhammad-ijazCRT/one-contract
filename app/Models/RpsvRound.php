<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RpsvRound extends Model
{
    protected $fillable = [
        'rpsv_match_id',
        'round_number',
        'player1_move',
        'player2_move',
        'winner_id',
        'status',
        'select_ends_at',
        'reveal_ends_at',
        'result_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'select_ends_at' => 'datetime',
            'reveal_ends_at' => 'datetime',
            'result_ends_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(RpsvMatch::class, 'rpsv_match_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }
}

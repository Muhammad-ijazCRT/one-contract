<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RpsvInvite extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'currency',
        'stake',
        'status',
        'match_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'stake' => 'decimal:8',
            'expires_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(RpsvMatch::class, 'match_id');
    }
}

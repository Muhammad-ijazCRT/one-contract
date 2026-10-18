<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RpsvQueueEntry extends Model
{
    protected $table = 'rpsv_queue';

    protected $fillable = [
        'user_id',
        'currency',
        'stake',
        'match_id',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'stake' => 'decimal:8',
            'joined_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

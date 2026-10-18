<?php

namespace App\Models;

use App\Enums\ClubCapitalRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubCapitalRequest extends Model
{
    protected $fillable = [
        'user_id',
        'club_investment_id',
        'amount',
        'status',
        'requested_at',
        'available_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'status' => ClubCapitalRequestStatus::class,
            'requested_at' => 'datetime',
            'available_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(ClubInvestment::class, 'club_investment_id');
    }

    public function isReady(): bool
    {
        return $this->status === ClubCapitalRequestStatus::Pending
            && $this->available_at !== null
            && $this->available_at->isPast();
    }
}

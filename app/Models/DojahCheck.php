<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DojahCheck extends Model
{
    public const TYPE_LIVENESS = 'liveness';

    public const TYPE_ONBOARDING = 'onboarding';

    public const TYPE_TRANSACTION = 'transaction';

    protected $fillable = [
        'user_id',
        'type',
        'purpose',
        'reference',
        'staging_token',
        'passed',
        'decision',
        'score',
        'probability',
        'message',
        'request_meta',
        'response',
    ];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'score' => 'decimal:2',
            'probability' => 'decimal:2',
            'request_meta' => 'array',
            'response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

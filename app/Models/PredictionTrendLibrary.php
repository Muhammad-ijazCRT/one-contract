<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictionTrendLibrary extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'game_mode',
        'source_type',
        'source_name',
        'source_path',
        'source_checksum',
        'difficulty',
        'pattern_type',
        'use_weighted_selection',
        'is_active',
        'trend_count',
        'outcomes_per_trend',
        'starts_at',
        'ends_at',
        'uploaded_by',
        'uploaded_via',
    ];

    protected function casts(): array
    {
        return [
            'use_weighted_selection' => 'boolean',
            'is_active' => 'boolean',
            'trend_count' => 'integer',
            'outcomes_per_trend' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'uploaded_by' => 'integer',
        ];
    }

    public function trends(): HasMany
    {
        return $this->hasMany(PredictionTrend::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

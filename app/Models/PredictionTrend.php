<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionTrend extends Model
{
    protected $fillable = [
        'prediction_trend_library_id',
        'pattern',
        'outcome_count',
        'difficulty',
        'pattern_type',
        'weight',
        'ordinal',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'outcome_count' => 'integer',
            'weight' => 'integer',
            'ordinal' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(PredictionTrendLibrary::class, 'prediction_trend_library_id');
    }
}

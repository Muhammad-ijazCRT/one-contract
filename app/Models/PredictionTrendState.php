<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionTrendState extends Model
{
    protected $fillable = [
        'game_mode',
        'prediction_trend_library_id',
        'prediction_trend_id',
        'cursor',
        'last_outcome',
        'activated_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'prediction_trend_library_id' => 'integer',
            'prediction_trend_id' => 'integer',
            'cursor' => 'integer',
            'last_outcome' => 'integer',
            'activated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(PredictionTrendLibrary::class, 'prediction_trend_library_id');
    }

    public function trend(): BelongsTo
    {
        return $this->belongsTo(PredictionTrend::class, 'prediction_trend_id');
    }
}

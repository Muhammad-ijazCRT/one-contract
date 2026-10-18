<?php

namespace App\Models;

use App\Enums\FaceVerificationPurpose;
use App\Enums\FaceVerificationSessionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceVerificationSession extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'purpose',
        'status',
        'session_token',
        'challenges',
        'challenges_completed',
        'liveness_passed',
        'liveness_confidence',
        'liveness_metrics',
        'face_match_passed',
        'face_match_confidence',
        'face_match_distance',
        'capture_image_path',
        'reference_image_path',
        'failure_reason',
        'ip_address',
        'user_agent',
        'started_at',
        'completed_at',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => FaceVerificationPurpose::class,
            'status' => FaceVerificationSessionStatus::class,
            'challenges' => 'array',
            'challenges_completed' => 'array',
            'liveness_passed' => 'boolean',
            'liveness_confidence' => 'decimal:2',
            'liveness_metrics' => 'array',
            'face_match_passed' => 'boolean',
            'face_match_confidence' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function challengesWithLabels(): array
    {
        return array_map(
            fn (string $key) => ['key' => $key, 'label' => self::challengeLabel($key)],
            $this->challenges ?? [],
        );
    }

    public static function challengeLabel(string $key): string
    {
        return match ($key) {
            'center' => 'Look straight at the camera',
            'blink' => 'Blink your eyes slowly 2–3 times',
            'nod' => 'Nod your head up and down',
            'shake' => 'Gently shake your head left and right',
            default => 'Follow the on-screen instruction',
        };
    }
}

<?php

namespace App\Services;

use App\Enums\FaceVerificationPurpose;
use App\Enums\FaceVerificationSessionStatus;
use App\Models\FaceVerificationSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FaceVerificationSessionService
{
    public function __construct(
        private readonly FaceVerificationService $faceVerification,
        private readonly IdCardStagingService $idCardStaging,
        private readonly SelfieStagingService $selfieStaging,
        private readonly DojahKycService $dojah,
    ) {}

    public function start(FaceVerificationPurpose $purpose, ?User $user, Request $request): FaceVerificationSession
    {
        if ($purpose === FaceVerificationPurpose::Withdrawal) {
            if (! $user || ! $user->face_image_path) {
                throw ValidationException::withMessages([
                    'purpose' => 'No registered face found for withdrawal verification.',
                ]);
            }
        }

        $referencePath = null;

        if ($purpose === FaceVerificationPurpose::Withdrawal) {
            $referencePath = $user?->face_image_path;
        } elseif ($purpose === FaceVerificationPurpose::Registration) {
            $selfieToken = (string) $request->input('selfie_staging_token', '');
            $referencePath = $this->selfieStaging->resolve($selfieToken);

            if (! $referencePath) {
                throw ValidationException::withMessages([
                    'selfie_staging_token' => 'Please complete selfie verification before the live check.',
                ]);
            }
        }

        $pool = config('face_verification.challenges', ['center', 'blink', 'nod', 'shake']);
        $count = (int) config('face_verification.challenges_per_session', 3);
        $remaining = array_values(array_diff($pool, ['center']));
        shuffle($remaining);
        $challenges = array_merge(['center'], array_slice($remaining, 0, max(0, $count - 1)));

        return FaceVerificationSession::query()->create([
            'user_id' => $user?->id,
            'purpose' => $purpose,
            'status' => FaceVerificationSessionStatus::Pending,
            'session_token' => Str::random(64),
            'challenges' => $challenges,
            'challenges_completed' => [],
            'reference_image_path' => $referencePath,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500),
            'started_at' => now(),
            'expires_at' => now()->addMinutes((int) config('face_verification.session_ttl_minutes', 10)),
        ]);
    }

    public function recordChallenge(string $token, string $challengeKey): FaceVerificationSession
    {
        $session = $this->findActiveSession($token);
        $assigned = $session->challenges ?? [];
        $completed = $session->challenges_completed ?? [];
        $nextIndex = count($completed);

        if (! isset($assigned[$nextIndex]) || $assigned[$nextIndex] !== $challengeKey) {
            throw ValidationException::withMessages([
                'challenge' => 'Invalid challenge order.',
            ]);
        }

        if ($completed !== []) {
            $last = $completed[array_key_last($completed)];
            $lastAt = (int) ($last['completed_at'] ?? 0);
            if (now()->timestamp - $lastAt < 1) {
                throw ValidationException::withMessages([
                    'challenge' => 'Please follow the prompts at a natural pace.',
                ]);
            }
        }

        $completed[] = [
            'key' => $challengeKey,
            'completed_at' => now()->timestamp,
        ];

        $session->update(['challenges_completed' => $completed]);

        return $session->fresh();
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    public function complete(string $token, string $faceImage, array $metrics): FaceVerificationSession
    {
        $session = $this->findActiveSession($token);

        if (count($session->challenges_completed ?? []) !== count($session->challenges ?? [])) {
            throw ValidationException::withMessages([
                'session' => 'Complete all liveness challenges first.',
            ]);
        }

        $duration = (float) ($metrics['duration_seconds'] ?? 0);
        if ($duration < (float) config('face_verification.min_liveness_seconds', 4)) {
            return $this->failAndThrow($session, 'Liveness duration too short.', 'Liveness check failed. Please try again.');
        }

        if (! $this->validateLivenessMetrics($session, $metrics)) {
            return $this->failAndThrow($session, 'Liveness motion validation failed.', 'Liveness check failed. Please try again.');
        }

        try {
            $path = $this->faceVerification->storeFaceImage($faceImage);
        } catch (\InvalidArgumentException) {
            return $this->failAndThrow($session, 'Invalid capture image.', 'Invalid face image. Please try again.');
        }

        $livenessConfidence = $this->calculateLivenessConfidence($metrics);

        if (DojahSettings::isLivenessReady()) {
            try {
                $dojah = $this->dojah->checkLiveness(
                    $faceImage,
                    $session->purpose->value,
                    $session->user,
                    $session->session_token,
                );
                $livenessConfidence = $dojah['probability'];
            } catch (ValidationException $e) {
                $reason = $e->validator->errors()->first() ?: 'Live verification failed.';

                return $this->failAndThrow($session, 'Dojah liveness failed.', $reason);
            }
        }
        $faceMatchPassed = null;
        $faceMatchConfidence = null;
        $faceMatchDistance = null;

        if ($session->purpose === FaceVerificationPurpose::Withdrawal) {
            $reference = $session->reference_image_path;
            if (! $reference) {
                return $this->failAndThrow($session, 'No reference face on file.', 'No registered face found.');
            }

            $score = $this->faceVerification->matchScore($reference, $faceImage);
            $faceMatchPassed = $score['passed'];
            $faceMatchConfidence = $score['confidence'];
            $faceMatchDistance = $score['difference_distance'];

            if (! $faceMatchPassed) {
                $session->update([
                    'status' => FaceVerificationSessionStatus::Failed,
                    'capture_image_path' => $path,
                    'liveness_passed' => true,
                    'liveness_confidence' => $livenessConfidence,
                    'liveness_metrics' => $metrics,
                    'face_match_passed' => false,
                    'face_match_confidence' => $faceMatchConfidence,
                    'face_match_distance' => $faceMatchDistance,
                    'failure_reason' => 'Face does not match registered profile.',
                    'completed_at' => now(),
                ]);

                throw ValidationException::withMessages([
                    'face_verification_session' => 'Face verification failed. Please try again with clear lighting.',
                ]);
            }
        } elseif ($session->purpose === FaceVerificationPurpose::Registration) {
            $reference = $session->reference_image_path;
            if (! $reference) {
                return $this->failAndThrow($session, 'No selfie reference on file.', 'Please capture your selfie again.');
            }

            $score = $this->faceVerification->matchScore($reference, $faceImage);
            $faceMatchPassed = $score['passed'];
            $faceMatchConfidence = $score['confidence'];
            $faceMatchDistance = $score['difference_distance'];

            if (! $faceMatchPassed) {
                $session->update([
                    'status' => FaceVerificationSessionStatus::Failed,
                    'capture_image_path' => $path,
                    'liveness_passed' => true,
                    'liveness_confidence' => $livenessConfidence,
                    'liveness_metrics' => $metrics,
                    'face_match_passed' => false,
                    'face_match_confidence' => $faceMatchConfidence,
                    'face_match_distance' => $faceMatchDistance,
                    'failure_reason' => 'Live face does not match selfie.',
                    'completed_at' => now(),
                ]);

                throw ValidationException::withMessages([
                    'face_verification_session' => 'Your live face does not match your selfie. Please try again with clear lighting.',
                ]);
            }
        }

        $session->update([
            'status' => FaceVerificationSessionStatus::Passed,
            'capture_image_path' => $path,
            'liveness_passed' => true,
            'liveness_confidence' => $livenessConfidence,
            'liveness_metrics' => $metrics,
            'face_match_passed' => $faceMatchPassed,
            'face_match_confidence' => $faceMatchConfidence,
            'face_match_distance' => $faceMatchDistance,
            'completed_at' => now(),
        ]);

        return $session->fresh();
    }

    public function consume(string $token, FaceVerificationPurpose $purpose, ?User $user = null): FaceVerificationSession
    {
        $session = FaceVerificationSession::query()
            ->where('session_token', $token)
            ->first();

        if (! $session) {
            throw ValidationException::withMessages([
                'face_verification_session' => 'Invalid verification session.',
            ]);
        }

        if ($session->status === FaceVerificationSessionStatus::Consumed) {
            throw ValidationException::withMessages([
                'face_verification_session' => 'Verification session already used.',
            ]);
        }

        if ($session->isExpired()) {
            $session->update(['status' => FaceVerificationSessionStatus::Expired]);

            throw ValidationException::withMessages([
                'face_verification_session' => 'Verification session expired. Please verify again.',
            ]);
        }

        if ($session->status !== FaceVerificationSessionStatus::Passed) {
            throw ValidationException::withMessages([
                'face_verification_session' => 'Face verification not completed successfully.',
            ]);
        }

        if ($session->purpose !== $purpose) {
            throw ValidationException::withMessages([
                'face_verification_session' => 'Invalid verification session purpose.',
            ]);
        }

        if ($purpose === FaceVerificationPurpose::Withdrawal) {
            if (! $user || $session->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'face_verification_session' => 'Verification session does not belong to this account.',
                ]);
            }

            if (! $session->face_match_passed) {
                throw ValidationException::withMessages([
                    'face_verification_session' => 'Face match verification failed.',
                ]);
            }
        }

        if ($purpose === FaceVerificationPurpose::Registration && ! $session->face_match_passed) {
            throw ValidationException::withMessages([
                'face_verification_session' => 'Your face must match your ID document before registering.',
            ]);
        }

        $session->update([
            'status' => FaceVerificationSessionStatus::Consumed,
            'consumed_at' => now(),
            'user_id' => $user?->id ?? $session->user_id,
        ]);

        return $session->fresh();
    }

    private function findActiveSession(string $token): FaceVerificationSession
    {
        $session = FaceVerificationSession::query()
            ->where('session_token', $token)
            ->first();

        if (! $session) {
            throw ValidationException::withMessages([
                'session_token' => 'Invalid verification session.',
            ]);
        }

        if ($session->status !== FaceVerificationSessionStatus::Pending) {
            throw ValidationException::withMessages([
                'session_token' => 'Verification session is no longer active.',
            ]);
        }

        if ($session->isExpired()) {
            $session->update(['status' => FaceVerificationSessionStatus::Expired]);

            throw ValidationException::withMessages([
                'session_token' => 'Verification session expired. Please start again.',
            ]);
        }

        return $session;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function validateLivenessMetrics(FaceVerificationSession $session, array $metrics): bool
    {
        $completedKeys = array_column($session->challenges_completed ?? [], 'key');
        $assigned = $session->challenges ?? [];

        if (count($completedKeys) !== count($assigned)) {
            return false;
        }

        foreach ($assigned as $index => $key) {
            if (($completedKeys[$index] ?? null) !== $key) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function calculateLivenessConfidence(array $metrics): float
    {
        $duration = (float) ($metrics['duration_seconds'] ?? 0);
        $minSeconds = (float) config('face_verification.min_liveness_seconds', 4);
        $durationScore = min(100, ($duration / max(1, $minSeconds * 2)) * 100);

        return round($durationScore, 2);
    }

    private function failAndThrow(
        FaceVerificationSession $session,
        string $reason,
        string $message,
    ): never {
        $session->update([
            'status' => FaceVerificationSessionStatus::Failed,
            'liveness_passed' => false,
            'failure_reason' => $reason,
            'completed_at' => now(),
        ]);

        throw ValidationException::withMessages([
            'face_verification_session' => $message,
        ]);
    }
}

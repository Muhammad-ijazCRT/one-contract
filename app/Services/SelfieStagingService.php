<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SelfieStagingService
{
    private const TTL_MINUTES = 30;

    public function __construct(
        private readonly FaceVerificationService $faceVerification,
        private readonly IdCardStagingService $idCardStaging,
        private readonly DojahKycService $dojah,
    ) {}

    /**
     * @return array{selfie_staging_token: string, expires_at: string, liveness_passed: bool, liveness_probability: float|null}
     */
    public function stage(string $dataUrl, string $idStagingToken): array
    {
        $idPath = $this->idCardStaging->resolve($idStagingToken);

        if (! $idPath) {
            throw ValidationException::withMessages([
                'id_staging_token' => 'ID document session expired. Please capture your ID again.',
            ]);
        }

        $presence = $this->faceVerification->looksLikeFace($dataUrl);

        if (! $presence['passed']) {
            throw ValidationException::withMessages([
                'selfie_image' => 'No close-up face detected. Fill the circle with your face — animals, cars, landscapes, and group photos are not allowed.',
            ]);
        }

        try {
            $path = $this->faceVerification->storeFaceImage($dataUrl);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'selfie_image' => 'Invalid selfie image. Please capture again.',
            ]);
        }

        $score = $this->faceVerification->matchFaceToIdCard($idPath, $dataUrl);

        if (! $score['passed']) {
            throw ValidationException::withMessages([
                'selfie_image' => 'Your selfie does not match the photo on your ID. Use good lighting and face the camera directly.',
            ]);
        }

        $token = Str::random(64);
        $livenessPassed = false;
        $livenessProbability = null;

        if (DojahSettings::isLivenessReady()) {
            $liveness = $this->dojah->checkLiveness($dataUrl, 'registration', null, $token);
            $livenessPassed = $liveness['passed'];
            $livenessProbability = $liveness['probability'];
        }

        Cache::put($this->cacheKey($token), $path, now()->addMinutes(self::TTL_MINUTES));

        return [
            'selfie_staging_token' => $token,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->toIso8601String(),
            'liveness_passed' => $livenessPassed,
            'liveness_probability' => $livenessProbability,
        ];
    }

    public function resolve(string $token): ?string
    {
        if (strlen($token) !== 64) {
            return null;
        }

        $path = Cache::get($this->cacheKey($token));

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function consume(string $token): string
    {
        $path = $this->resolve($token);

        if (! $path) {
            throw ValidationException::withMessages([
                'selfie_staging_token' => 'Selfie session expired. Please capture your selfie again.',
            ]);
        }

        Cache::forget($this->cacheKey($token));

        return $path;
    }

    private function cacheKey(string $token): string
    {
        return 'selfie_staging:'.$token;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IdCardStagingService
{
    private const TTL_MINUTES = 30;

    public function __construct(
        private readonly IdCardVerificationService $idCards,
    ) {}

    /**
     * @param  array<string, string|null>  $extras
     * @return array{id_staging_token: string, expires_at: string}
     */
    public function stage(string $frontUrl, ?string $backUrl = null, ?string $documentType = null, array $extras = []): array
    {
        $type = in_array($documentType, ['national_id', 'passport', 'license'], true)
            ? $documentType
            : null;

        $analysis = $this->idCards->validateTechnicalDocument($frontUrl);

        if (! $analysis['passed']) {
            throw ValidationException::withMessages([
                'id_card_image' => $analysis['reason'],
            ]);
        }

        $backPath = null;
        if ($backUrl) {
            $backAnalysis = $this->idCards->validateTechnicalDocument($backUrl);
            if (! $backAnalysis['passed']) {
                throw ValidationException::withMessages([
                    'id_card_back_image' => $backAnalysis['reason'],
                ]);
            }
        }

        try {
            $frontPath = $this->idCards->storeIdCardImage($frontUrl);
            if ($backUrl) {
                $backPath = $this->idCards->storeIdCardImage($backUrl);
            }
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'id_card_image' => 'Invalid ID card image. Please capture again.',
            ]);
        }

        $stored = [
            'front' => $frontPath,
            'back' => $backPath,
            'id_document_type' => $type,
            'national_id_front' => null,
            'national_id_back' => null,
            'passport_front' => null,
            'license_front' => null,
            'license_back' => null,
        ];

        $this->storeExtra($stored, $extras, 'national_id_front', 'national_id', true);
        $this->storeExtra($stored, $extras, 'national_id_back', 'national_id', false);
        $this->storeExtra($stored, $extras, 'passport_front', 'passport', true);
        $this->storeExtra($stored, $extras, 'license_front', 'license', true);
        $this->storeExtra($stored, $extras, 'license_back', 'license', false);

        if ($type === 'national_id') {
            $stored['national_id_front'] ??= $frontPath;
            $stored['national_id_back'] ??= $backPath;
        } elseif ($type === 'passport') {
            $stored['passport_front'] ??= $frontPath;
        } elseif ($type === 'license') {
            $stored['license_front'] ??= $frontPath;
            $stored['license_back'] ??= $backPath;
        }

        $token = Str::random(64);
        Cache::put($this->cacheKey($token), $stored, now()->addMinutes(self::TTL_MINUTES));

        return [
            'id_staging_token' => $token,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $stored
     * @param  array<string, string|null>  $extras
     */
    private function storeExtra(array &$stored, array $extras, string $key, string $type, bool $isFront): void
    {
        $dataUrl = $extras[$key] ?? null;
        if (! is_string($dataUrl) || $dataUrl === '') {
            return;
        }

        $analysis = $this->idCards->validateTechnicalDocument($dataUrl);

        if (! $analysis['passed']) {
            throw ValidationException::withMessages([
                $key => $analysis['reason'],
            ]);
        }

        try {
            $stored[$key] = $this->idCards->storeIdCardImage($dataUrl);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                $key => 'Invalid document image. Please upload again.',
            ]);
        }
    }

    public function resolve(string $token): ?string
    {
        if (strlen($token) !== 64) {
            return null;
        }

        $stored = Cache::get($this->cacheKey($token));

        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        if (is_array($stored) && is_string($stored['front'] ?? null) && $stored['front'] !== '') {
            return $stored['front'];
        }

        return null;
    }

    /**
     * @return array{
     *     front: string,
     *     back: string|null,
     *     id_document_type: string|null,
     *     national_id_front: string|null,
     *     national_id_back: string|null,
     *     passport_front: string|null,
     *     license_front: string|null,
     *     license_back: string|null
     * }
     */
    public function consumePair(string $token): array
    {
        if (strlen($token) !== 64) {
            throw ValidationException::withMessages([
                'id_staging_token' => 'ID document session expired. Please capture your ID again.',
            ]);
        }

        $stored = Cache::get($this->cacheKey($token));
        Cache::forget($this->cacheKey($token));

        if (is_string($stored) && $stored !== '') {
            return $this->emptyBundle(['front' => $stored]);
        }

        if (is_array($stored) && is_string($stored['front'] ?? null) && $stored['front'] !== '') {
            return $this->emptyBundle([
                'front' => $stored['front'],
                'back' => is_string($stored['back'] ?? null) && $stored['back'] !== '' ? $stored['back'] : null,
                'id_document_type' => is_string($stored['id_document_type'] ?? null) ? $stored['id_document_type'] : null,
                'national_id_front' => $stored['national_id_front'] ?? null,
                'national_id_back' => $stored['national_id_back'] ?? null,
                'passport_front' => $stored['passport_front'] ?? null,
                'license_front' => $stored['license_front'] ?? null,
                'license_back' => $stored['license_back'] ?? null,
            ]);
        }

        throw ValidationException::withMessages([
            'id_staging_token' => 'ID document session expired. Please capture your ID again.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{
     *     front: string,
     *     back: string|null,
     *     id_document_type: string|null,
     *     national_id_front: string|null,
     *     national_id_back: string|null,
     *     passport_front: string|null,
     *     license_front: string|null,
     *     license_back: string|null
     * }
     */
    private function emptyBundle(array $values): array
    {
        $path = static fn (mixed $value): ?string => is_string($value) && $value !== '' ? $value : null;

        return [
            'front' => (string) $values['front'],
            'back' => $path($values['back'] ?? null),
            'id_document_type' => $path($values['id_document_type'] ?? null),
            'national_id_front' => $path($values['national_id_front'] ?? null),
            'national_id_back' => $path($values['national_id_back'] ?? null),
            'passport_front' => $path($values['passport_front'] ?? null),
            'license_front' => $path($values['license_front'] ?? null),
            'license_back' => $path($values['license_back'] ?? null),
        ];
    }

    public function consume(string $token): string
    {
        return $this->consumePair($token)['front'];
    }

    private function cacheKey(string $token): string
    {
        return 'id_staging:'.$token;
    }
}

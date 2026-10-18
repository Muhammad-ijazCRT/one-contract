<?php

namespace App\Services;

use App\Models\DojahCheck;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DojahKycService
{
    public function __construct(
        private readonly DojahClient $client,
    ) {}

    /**
     * @return array{ok: bool, message: string, balance?: string}
     */
    public function ping(): array
    {
        if (! DojahSettings::hasCredentials()) {
            return [
                'ok' => false,
                'message' => 'Add the Dojah secret key and App ID, then try again.',
            ];
        }

        $response = $this->client->get('/api/v1/balance');

        if (! ($response['_ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $this->errorMessage($response, 'Could not connect to Dojah. Check App ID, secret key, and sandbox/live mode.'),
            ];
        }

        $balance = (string) data_get($response, 'entity.wallet_balance', '');

        return [
            'ok' => true,
            'message' => $balance !== ''
                ? 'Connected to Dojah. Wallet balance: '.$balance
                : 'Connected to Dojah successfully.',
            'balance' => $balance,
        ];
    }

    /**
     * @return array{passed: bool, probability: float, message: string, check: DojahCheck}
     */
    public function checkLiveness(string $imageDataUrl, string $purpose, ?User $user = null, ?string $stagingToken = null): array
    {
        if (! DojahSettings::isLivenessReady()) {
            throw ValidationException::withMessages([
                'selfie_image' => 'Live verification is not configured yet. Add Dojah keys in Admin → KYC Settings.',
            ]);
        }

        $base64 = $this->toBase64($imageDataUrl);

        if ($base64 === '') {
            throw ValidationException::withMessages([
                'selfie_image' => 'Invalid selfie image. Please capture again.',
            ]);
        }

        $response = $this->client->post('/api/v1/ml/liveness', [
            'image' => $base64,
        ], 45);

        $liveness = data_get($response, 'entity.liveness', []);
        $face = data_get($response, 'entity.face', []);
        $rawPassed = $liveness['liveness_check'] ?? false;
        $passed = filter_var($rawPassed, FILTER_VALIDATE_BOOLEAN);
        $probability = (float) ($liveness['liveness_probability'] ?? 0);
        $min = DojahSettings::minLivenessProbability();

        if (! ($response['_ok'] ?? false)) {
            $passed = false;
            $message = $this->errorMessage($response, 'Live verification failed. Please try again with good lighting.');
        } elseif (! ($face['face_detected'] ?? true)) {
            $passed = false;
            $message = 'No face was detected. Face the camera and try again.';
        } elseif (! $passed) {
            $message = 'Live verification failed. Use a live camera selfie, not a photo of a photo.';
        } elseif ($probability < $min) {
            $passed = false;
            $message = 'Live verification confidence was too low. Please retry in brighter light.';
        } else {
            $message = 'Live person check passed.';
        }

        $check = DojahCheck::query()->create([
            'user_id' => $user?->id,
            'type' => DojahCheck::TYPE_LIVENESS,
            'purpose' => $purpose,
            'reference' => Str::uuid()->toString(),
            'staging_token' => $stagingToken,
            'passed' => $passed,
            'probability' => $probability,
            'message' => $message,
            'request_meta' => [
                'purpose' => $purpose,
                'mode' => DojahSettings::mode(),
                'min_probability' => $min,
            ],
            'response' => $this->sanitizeResponse($response),
        ]);

        if (! $passed) {
            throw ValidationException::withMessages([
                'selfie_image' => $message,
                'face_verification_session' => $message,
            ]);
        }

        return [
            'passed' => true,
            'probability' => $probability,
            'message' => $message,
            'check' => $check,
        ];
    }

    /**
     * @param  array<string, mixed>  $event
     */
    public function ingestEvent(string $type, array $event, string $purpose, ?User $user = null): ?DojahCheck
    {
        if (! DojahSettings::isMonitoringReady()) {
            return null;
        }

        $payload = [
            'key' => DojahSettings::ingestKey(),
            'type' => $type,
            'event' => $event,
        ];

        try {
            $response = $this->client->ingest($payload);
        } catch (\Throwable $e) {
            report($e);
            Log::warning('Dojah transaction monitoring failed', ['purpose' => $purpose]);

            return null;
        }

        $decision = (string) data_get($response, 'decision.action', '');
        $score = data_get($response, 'score');
        $passed = ($response['_ok'] ?? false) && ! in_array(strtolower($decision), ['block', 'deny', 'rejected'], true);

        return DojahCheck::query()->create([
            'user_id' => $user?->id,
            'type' => $type === 'onboarding' ? DojahCheck::TYPE_ONBOARDING : DojahCheck::TYPE_TRANSACTION,
            'purpose' => $purpose,
            'reference' => (string) data_get($event, 'transaction.id', Str::uuid()->toString()),
            'passed' => $passed,
            'decision' => $decision !== '' ? $decision : null,
            'score' => is_numeric($score) ? $score : null,
            'message' => $passed ? 'Monitoring event accepted.' : 'Dojah flagged this event.',
            'request_meta' => [
                'type' => $type,
                'purpose' => $purpose,
                'mode' => DojahSettings::mode(),
            ],
            'response' => $this->sanitizeResponse($response),
        ]);
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    public function monitorUserEvent(User $user, string $type, string $purpose, array $transaction = []): ?DojahCheck
    {
        $event = [
            'user' => [
                'user_id' => (string) $user->id,
                'user_type' => 'individual',
                'registration_time' => optional($user->created_at)?->toIso8601String() ?? now()->toIso8601String(),
                'email' => $user->email,
                'name' => $user->name,
            ],
            'device' => [
                'ip_address' => request()->ip(),
                'os' => Str::limit((string) request()->userAgent(), 80, ''),
            ],
        ];

        if ($transaction !== []) {
            $event['transaction'] = $transaction;
        }

        return $this->ingestEvent($type, $event, $purpose, $user);
    }

    public function assertWithdrawalAllowed(User $user, float $amount, string $currency, string $reference): void
    {
        if (! DojahSettings::isMonitoringReady()) {
            return;
        }

        $check = $this->monitorUserEvent($user, 'banking', 'withdrawal', [
            'id' => $reference,
            'time' => now()->toIso8601String(),
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'ref' => $reference,
            'type' => 'withdrawal',
            'channel' => 'online',
            'purpose' => 'wallet withdrawal',
        ]);

        if (! DojahSettings::blockRiskyWithdrawals() || ! $check) {
            return;
        }

        if (strtolower((string) $check->decision) === 'block') {
            throw ValidationException::withMessages([
                'amount' => 'This withdrawal was flagged by fraud monitoring. Please contact support.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function errorMessage(array $response, string $fallback): string
    {
        $status = (int) ($response['_http_status'] ?? 0);

        if ($status === 401) {
            return 'Dojah rejected the API keys. Add the App ID from Developers → Configuration and confirm sandbox/live mode.';
        }

        if ($status === 402) {
            return 'Dojah wallet has no balance. Fund the sandbox/live wallet and retry.';
        }

        $message = $response['error'] ?? $response['message'] ?? data_get($response, 'entity.message');

        return is_string($message) && $message !== '' ? $message : $fallback;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function sanitizeResponse(array $response): array
    {
        unset($response['image']);

        return $response;
    }

    private function toBase64(string $image): string
    {
        if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,(.+)$/s', $image, $matches)) {
            return $matches[1];
        }

        return $image;
    }
}

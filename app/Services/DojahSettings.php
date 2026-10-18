<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class DojahSettings
{
    public const MODE_SANDBOX = 'sandbox';

    public const MODE_LIVE = 'live';

    public const GROUP = 'dojah';

    public static function mode(): string
    {
        $mode = strtolower((string) self::get('dojah_mode', self::MODE_SANDBOX));

        return $mode === self::MODE_LIVE ? self::MODE_LIVE : self::MODE_SANDBOX;
    }

    public static function isSandbox(): bool
    {
        return self::mode() === self::MODE_SANDBOX;
    }

    public static function isEnabled(): bool
    {
        return self::truthy(self::get('dojah_enabled', '0'));
    }

    public static function livenessEnabled(): bool
    {
        return self::isEnabled() && self::truthy(self::get('dojah_liveness_enabled', '1'));
    }

    public static function transactionMonitoringEnabled(): bool
    {
        return self::isEnabled() && self::truthy(self::get('dojah_transaction_monitoring_enabled', '1'));
    }

    public static function blockRiskyWithdrawals(): bool
    {
        return self::transactionMonitoringEnabled() && self::truthy(self::get('dojah_block_risky_withdrawals', '0'));
    }

    public static function minLivenessProbability(): float
    {
        $value = (float) self::get('dojah_min_liveness_probability', '70');

        return max(0, min(100, $value));
    }

    public static function publicKey(): string
    {
        $key = self::isSandbox()
            ? self::get('dojah_sandbox_public_key', '')
            : self::get('dojah_live_public_key', '');

        return (string) ($key ?: config('services.dojah.public_key', ''));
    }

    public static function secretKey(): string
    {
        $secret = self::isSandbox()
            ? self::get('dojah_sandbox_secret_key', '')
            : self::get('dojah_live_secret_key', '');

        return (string) ($secret ?: config('services.dojah.secret_key', ''));
    }

    public static function appId(): string
    {
        $id = self::isSandbox()
            ? self::get('dojah_sandbox_app_id', '')
            : self::get('dojah_live_app_id', '');

        $id = (string) ($id ?: config('services.dojah.app_id', ''));

        return $id;
    }

    public static function ingestKey(): string
    {
        $key = self::isSandbox()
            ? self::get('dojah_sandbox_ingest_key', '')
            : self::get('dojah_live_ingest_key', '');

        return (string) ($key ?: config('services.dojah.ingest_key', ''));
    }

    public static function baseUrl(): string
    {
        if (self::isSandbox()) {
            $url = self::get('dojah_sandbox_base_url', 'https://sandbox.dojah.io');

            return rtrim($url !== '' ? $url : 'https://sandbox.dojah.io', '/');
        }

        $url = self::get('dojah_live_base_url', 'https://api.dojah.io');

        return rtrim($url !== '' ? $url : 'https://api.dojah.io', '/');
    }

    public static function ingestUrl(): string
    {
        return 'https://ingest.dojah.io/api/ingest';
    }

    public static function webhookUrl(): string
    {
        $url = self::get('dojah_webhook_url', '');

        if ($url !== '') {
            return $url;
        }

        try {
            return route('dojah.webhook', absolute: true);
        } catch (\Throwable) {
            return rtrim((string) config('app.url'), '/').'/dojah/webhook';
        }
    }

    public static function hasCredentials(): bool
    {
        return self::secretKey() !== '' && self::appId() !== '';
    }

    public static function isLivenessReady(): bool
    {
        return self::livenessEnabled() && self::hasCredentials();
    }

    public static function isMonitoringReady(): bool
    {
        return self::transactionMonitoringEnabled() && self::secretKey() !== '' && self::ingestKey() !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public static function allForAdmin(): array
    {
        return [
            'mode' => self::mode(),
            'enabled' => self::isEnabled(),
            'liveness_enabled' => self::truthy(self::get('dojah_liveness_enabled', '1')),
            'transaction_monitoring_enabled' => self::truthy(self::get('dojah_transaction_monitoring_enabled', '1')),
            'block_risky_withdrawals' => self::truthy(self::get('dojah_block_risky_withdrawals', '0')),
            'min_liveness_probability' => (string) self::minLivenessProbability(),
            'sandbox_public_key' => (string) self::get('dojah_sandbox_public_key', ''),
            'live_public_key' => (string) self::get('dojah_live_public_key', ''),
            'sandbox_secret_key' => (string) self::get('dojah_sandbox_secret_key', ''),
            'live_secret_key' => (string) self::get('dojah_live_secret_key', ''),
            'sandbox_secret_key_set' => self::get('dojah_sandbox_secret_key', '') !== '',
            'live_secret_key_set' => self::get('dojah_live_secret_key', '') !== '',
            'sandbox_app_id' => (string) self::get('dojah_sandbox_app_id', ''),
            'live_app_id' => (string) self::get('dojah_live_app_id', ''),
            'sandbox_ingest_key' => (string) self::get('dojah_sandbox_ingest_key', ''),
            'live_ingest_key' => (string) self::get('dojah_live_ingest_key', ''),
            'sandbox_ingest_key_set' => self::get('dojah_sandbox_ingest_key', '') !== '',
            'live_ingest_key_set' => self::get('dojah_live_ingest_key', '') !== '',
            'sandbox_base_url' => (string) self::get('dojah_sandbox_base_url', 'https://sandbox.dojah.io'),
            'live_base_url' => (string) self::get('dojah_live_base_url', 'https://api.dojah.io'),
            'webhook_url' => self::webhookUrl(),
            'active_base_url' => self::baseUrl(),
            'active_public_key_set' => self::publicKey() !== '',
            'active_secret_set' => self::secretKey() !== '',
            'active_app_id_set' => self::appId() !== '',
            'credentials_ready' => self::hasCredentials(),
            'liveness_ready' => self::isLivenessReady(),
            'monitoring_ready' => self::isMonitoringReady(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function update(array $data): void
    {
        $map = [
            'dojah_mode' => ($data['mode'] ?? self::MODE_SANDBOX) === self::MODE_LIVE ? self::MODE_LIVE : self::MODE_SANDBOX,
            'dojah_enabled' => ! empty($data['enabled']) ? '1' : '0',
            'dojah_liveness_enabled' => ! empty($data['liveness_enabled']) ? '1' : '0',
            'dojah_transaction_monitoring_enabled' => ! empty($data['transaction_monitoring_enabled']) ? '1' : '0',
            'dojah_block_risky_withdrawals' => ! empty($data['block_risky_withdrawals']) ? '1' : '0',
            'dojah_min_liveness_probability' => (string) max(0, min(100, (float) ($data['min_liveness_probability'] ?? 70))),
            'dojah_sandbox_base_url' => rtrim((string) ($data['sandbox_base_url'] ?? 'https://sandbox.dojah.io'), '/'),
            'dojah_live_base_url' => rtrim((string) ($data['live_base_url'] ?? 'https://api.dojah.io'), '/'),
            'dojah_webhook_url' => (string) ($data['webhook_url'] ?? ''),
        ];

        foreach ([
            'sandbox_public_key' => 'dojah_sandbox_public_key',
            'live_public_key' => 'dojah_live_public_key',
            'sandbox_app_id' => 'dojah_sandbox_app_id',
            'live_app_id' => 'dojah_live_app_id',
        ] as $input => $key) {
            if (array_key_exists($input, $data)) {
                $map[$key] = trim((string) $data[$input]);
            }
        }

        foreach ([
            'sandbox_secret_key' => 'dojah_sandbox_secret_key',
            'live_secret_key' => 'dojah_live_secret_key',
            'sandbox_ingest_key' => 'dojah_sandbox_ingest_key',
            'live_ingest_key' => 'dojah_live_ingest_key',
        ] as $input => $key) {
            if (filled($data[$input] ?? null)) {
                $map[$key] = trim((string) $data[$input]);
            }
        }

        foreach ($map as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => self::GROUP],
            );
            Cache::forget("setting.{$key}");
        }
    }

    private static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("setting.{$key}", 300, function () use ($key, $default) {
            return Setting::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    private static function truthy(?string $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}

<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class MonnifySettings
{
    public const MODE_SANDBOX = 'sandbox';

    public const MODE_LIVE = 'live';

    public const GROUP = 'monnify';

    public static function mode(): string
    {
        $mode = strtolower((string) self::get('monnify_mode', self::MODE_SANDBOX));

        return $mode === self::MODE_LIVE ? self::MODE_LIVE : self::MODE_SANDBOX;
    }

    public static function isSandbox(): bool
    {
        return self::mode() === self::MODE_SANDBOX;
    }

    public static function isEnabled(): bool
    {
        return self::truthy(self::get('monnify_enabled', '0'));
    }

    public static function depositsEnabled(): bool
    {
        return self::isEnabled() && self::truthy(self::get('monnify_deposits_enabled', '1'));
    }

    public static function withdrawalsEnabled(): bool
    {
        return self::isEnabled() && self::truthy(self::get('monnify_withdrawals_enabled', '1'));
    }

    public static function apiKey(): string
    {
        $key = self::isSandbox()
            ? self::get('monnify_sandbox_api_key', '')
            : self::get('monnify_live_api_key', '');

        return (string) ($key ?: config('services.monnify.api_key', ''));
    }

    public static function secretKey(): string
    {
        $secret = self::isSandbox()
            ? self::get('monnify_sandbox_secret_key', '')
            : self::get('monnify_live_secret_key', '');

        return (string) ($secret ?: config('services.monnify.secret_key', ''));
    }

    public static function baseUrl(): string
    {
        if (self::isSandbox()) {
            $url = self::get('monnify_sandbox_base_url', 'https://sandbox.monnify.com');

            return rtrim($url !== '' ? $url : 'https://sandbox.monnify.com', '/');
        }

        $url = self::get('monnify_live_base_url', 'https://api.monnify.com');

        return rtrim($url !== '' ? $url : 'https://api.monnify.com', '/');
    }

    public static function contractCode(): string
    {
        $code = self::isSandbox()
            ? self::get('monnify_sandbox_contract_code', '')
            : self::get('monnify_live_contract_code', '');

        return (string) ($code ?: config('services.monnify.contract_code', ''));
    }

    public static function walletAccountNumber(): string
    {
        $wallet = self::isSandbox()
            ? self::get('monnify_sandbox_wallet_account', '')
            : self::get('monnify_live_wallet_account', '');

        return (string) ($wallet ?: config('services.monnify.wallet_account', ''));
    }

    public static function redirectUrl(): string
    {
        $url = self::get('monnify_redirect_url', '');

        if ($url !== '') {
            return $url;
        }

        return route('monnify.callback', absolute: true);
    }

    public static function webhookUrl(): string
    {
        $url = self::get('monnify_webhook_url', '');

        if ($url !== '') {
            return $url;
        }

        return route('monnify.webhook', absolute: true);
    }

    public static function hasCredentials(): bool
    {
        return self::apiKey() !== '' && self::secretKey() !== '';
    }

    public static function isConfigured(): bool
    {
        return self::hasCredentials() && self::contractCode() !== '';
    }

    public static function isDepositReady(): bool
    {
        return self::depositsEnabled() && self::isConfigured();
    }

    public static function isWithdrawReady(): bool
    {
        return self::withdrawalsEnabled()
            && self::hasCredentials()
            && self::contractCode() !== ''
            && self::walletAccountNumber() !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public static function allForAdmin(): array
    {
        return [
            'mode' => self::mode(),
            'enabled' => self::isEnabled(),
            'deposits_enabled' => self::depositsEnabled(),
            'withdrawals_enabled' => self::withdrawalsEnabled(),
            'sandbox_api_key' => (string) self::get('monnify_sandbox_api_key', ''),
            'live_api_key' => (string) self::get('monnify_live_api_key', ''),
            'sandbox_secret_key' => (string) self::get('monnify_sandbox_secret_key', ''),
            'live_secret_key' => (string) self::get('monnify_live_secret_key', ''),
            'sandbox_secret_key_set' => self::get('monnify_sandbox_secret_key', '') !== '',
            'live_secret_key_set' => self::get('monnify_live_secret_key', '') !== '',
            'sandbox_base_url' => (string) self::get('monnify_sandbox_base_url', 'https://sandbox.monnify.com'),
            'live_base_url' => (string) self::get('monnify_live_base_url', 'https://api.monnify.com'),
            'sandbox_contract_code' => (string) self::get('monnify_sandbox_contract_code', ''),
            'live_contract_code' => (string) self::get('monnify_live_contract_code', ''),
            'sandbox_wallet_account' => (string) self::get('monnify_sandbox_wallet_account', ''),
            'live_wallet_account' => (string) self::get('monnify_live_wallet_account', ''),
            'redirect_url' => self::redirectUrl(),
            'webhook_url' => self::webhookUrl(),
            'active_base_url' => self::baseUrl(),
            'active_api_key_set' => self::apiKey() !== '',
            'active_secret_set' => self::secretKey() !== '',
            'active_contract_set' => self::contractCode() !== '',
            'active_wallet_set' => self::walletAccountNumber() !== '',
            'credentials_ready' => self::hasCredentials(),
            'is_configured' => self::isConfigured(),
            'is_deposit_ready' => self::isDepositReady(),
            'is_withdraw_ready' => self::isWithdrawReady(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function update(array $data): void
    {
        $map = [
            'monnify_mode' => ($data['mode'] ?? self::MODE_SANDBOX) === self::MODE_LIVE ? self::MODE_LIVE : self::MODE_SANDBOX,
            'monnify_enabled' => ! empty($data['enabled']) ? '1' : '0',
            'monnify_deposits_enabled' => ! empty($data['deposits_enabled']) ? '1' : '0',
            'monnify_withdrawals_enabled' => ! empty($data['withdrawals_enabled']) ? '1' : '0',
            'monnify_sandbox_base_url' => rtrim((string) ($data['sandbox_base_url'] ?? 'https://sandbox.monnify.com'), '/'),
            'monnify_live_base_url' => rtrim((string) ($data['live_base_url'] ?? 'https://api.monnify.com'), '/'),
            'monnify_sandbox_contract_code' => (string) ($data['sandbox_contract_code'] ?? ''),
            'monnify_live_contract_code' => (string) ($data['live_contract_code'] ?? ''),
            'monnify_sandbox_wallet_account' => (string) ($data['sandbox_wallet_account'] ?? ''),
            'monnify_live_wallet_account' => (string) ($data['live_wallet_account'] ?? ''),
            'monnify_redirect_url' => (string) ($data['redirect_url'] ?? ''),
            'monnify_webhook_url' => (string) ($data['webhook_url'] ?? ''),
        ];

        if (filled($data['sandbox_api_key'] ?? null)) {
            $map['monnify_sandbox_api_key'] = (string) $data['sandbox_api_key'];
        }

        if (filled($data['live_api_key'] ?? null)) {
            $map['monnify_live_api_key'] = (string) $data['live_api_key'];
        }

        if (filled($data['sandbox_secret_key'] ?? null)) {
            $map['monnify_sandbox_secret_key'] = (string) $data['sandbox_secret_key'];
        }

        if (filled($data['live_secret_key'] ?? null)) {
            $map['monnify_live_secret_key'] = (string) $data['live_secret_key'];
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

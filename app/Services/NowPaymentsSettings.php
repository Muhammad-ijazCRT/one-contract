<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class NowPaymentsSettings
{
    public const MODE_SANDBOX = 'sandbox';

    public const MODE_LIVE = 'live';

    public static function mode(): string
    {
        $mode = strtolower((string) self::get('nowpayments_mode', self::envSandbox() ? self::MODE_SANDBOX : self::MODE_LIVE));

        return $mode === self::MODE_LIVE ? self::MODE_LIVE : self::MODE_SANDBOX;
    }

    public static function isSandbox(): bool
    {
        return self::mode() === self::MODE_SANDBOX;
    }

    public static function apiKey(): string
    {
        $key = self::isSandbox()
            ? self::get('nowpayments_sandbox_api_key', '')
            : self::get('nowpayments_live_api_key', '');

        if ($key !== '') {
            return $key;
        }

        return (string) config('services.nowpayments.api_key', '');
    }

    public static function publicKey(): string
    {
        $key = self::isSandbox()
            ? self::get('nowpayments_sandbox_public_key', '')
            : self::get('nowpayments_live_public_key', '');

        return (string) ($key ?? '');
    }

    public static function ipnSecret(): string
    {
        $secret = self::isSandbox()
            ? self::get('nowpayments_sandbox_ipn_secret', '')
            : self::get('nowpayments_live_ipn_secret', '');

        if ($secret !== '') {
            return $secret;
        }

        return (string) config('services.nowpayments.ipn_secret', '');
    }

    public static function baseUrl(): string
    {
        if (self::isSandbox()) {
            $url = self::get('nowpayments_sandbox_url', 'https://api-sandbox.nowpayments.io');

            return rtrim($url !== '' ? $url : 'https://api-sandbox.nowpayments.io', '/');
        }

        $url = self::get('nowpayments_live_url', 'https://api.nowpayments.io');

        return rtrim($url !== '' ? $url : 'https://api.nowpayments.io', '/');
    }

    public static function checkoutEndpoint(): string
    {
        $endpoint = self::get('nowpayments_checkout_endpoint', '/v1/invoice');

        if ($endpoint === '') {
            return '/v1/invoice';
        }

        return str_starts_with($endpoint, '/') ? $endpoint : '/'.$endpoint;
    }

    public static function ipnCallbackUrl(): string
    {
        $url = self::get('nowpayments_ipn_callback_url', '');

        if ($url !== '') {
            return $url;
        }

        return (string) (config('services.nowpayments.ipn_callback_url')
            ?: rtrim((string) config('app.url'), '/').'/nowpayments/ipn');
    }

    public static function successUrl(): ?string
    {
        $url = self::get('nowpayments_success_url', '');

        return $url !== '' ? $url : (config('services.nowpayments.success_url') ?: null);
    }

    public static function cancelUrl(): ?string
    {
        $url = self::get('nowpayments_cancel_url', '');

        return $url !== '' ? $url : (config('services.nowpayments.cancel_url') ?: null);
    }

    public static function custodyEmail(): string
    {
        $email = self::isSandbox()
            ? self::get('nowpayments_sandbox_custody_email', '')
            : self::get('nowpayments_live_custody_email', '');

        if ($email !== '') {
            return $email;
        }

        return (string) config('services.nowpayments.custody_email', '');
    }

    public static function custodyPassword(): string
    {
        $password = self::isSandbox()
            ? self::get('nowpayments_sandbox_custody_password', '')
            : self::get('nowpayments_live_custody_password', '');

        if ($password !== '') {
            return $password;
        }

        return (string) config('services.nowpayments.custody_password', '');
    }

    /**
     * @return array<string, string>
     */
    public static function allForAdmin(): array
    {
        return [
            'mode' => self::mode(),
            'sandbox_api_key' => (string) self::get('nowpayments_sandbox_api_key', ''),
            'live_api_key' => (string) self::get('nowpayments_live_api_key', ''),
            'sandbox_public_key' => (string) self::get('nowpayments_sandbox_public_key', ''),
            'live_public_key' => (string) self::get('nowpayments_live_public_key', ''),
            'sandbox_ipn_secret' => (string) self::get('nowpayments_sandbox_ipn_secret', ''),
            'live_ipn_secret' => (string) self::get('nowpayments_live_ipn_secret', ''),
            'sandbox_url' => (string) self::get('nowpayments_sandbox_url', 'https://api-sandbox.nowpayments.io'),
            'live_url' => (string) self::get('nowpayments_live_url', 'https://api.nowpayments.io'),
            'checkout_endpoint' => self::checkoutEndpoint(),
            'ipn_callback_url' => self::ipnCallbackUrl(),
            'success_url' => (string) (self::successUrl() ?? ''),
            'cancel_url' => (string) (self::cancelUrl() ?? ''),
            'sandbox_custody_email' => (string) self::get('nowpayments_sandbox_custody_email', ''),
            'live_custody_email' => (string) self::get('nowpayments_live_custody_email', ''),
            'sandbox_custody_password_set' => self::get('nowpayments_sandbox_custody_password', '') !== '',
            'live_custody_password_set' => self::get('nowpayments_live_custody_password', '') !== '',
            'active_custody_email_set' => self::custodyEmail() !== '',
            'active_custody_password_set' => self::custodyPassword() !== '',
            'active_base_url' => self::baseUrl(),
            'active_api_key_set' => self::apiKey() !== '',
            'active_public_key_set' => self::publicKey() !== '',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function update(array $data): void
    {
        $map = [
            'nowpayments_mode' => ($data['mode'] ?? self::MODE_SANDBOX) === self::MODE_LIVE ? self::MODE_LIVE : self::MODE_SANDBOX,
            'nowpayments_sandbox_api_key' => (string) ($data['sandbox_api_key'] ?? ''),
            'nowpayments_live_api_key' => (string) ($data['live_api_key'] ?? ''),
            'nowpayments_sandbox_public_key' => (string) ($data['sandbox_public_key'] ?? ''),
            'nowpayments_live_public_key' => (string) ($data['live_public_key'] ?? ''),
            'nowpayments_sandbox_ipn_secret' => (string) ($data['sandbox_ipn_secret'] ?? ''),
            'nowpayments_live_ipn_secret' => (string) ($data['live_ipn_secret'] ?? ''),
            'nowpayments_sandbox_url' => rtrim((string) ($data['sandbox_url'] ?? 'https://api-sandbox.nowpayments.io'), '/'),
            'nowpayments_live_url' => rtrim((string) ($data['live_url'] ?? 'https://api.nowpayments.io'), '/'),
            'nowpayments_checkout_endpoint' => (string) ($data['checkout_endpoint'] ?? '/v1/invoice'),
            'nowpayments_ipn_callback_url' => (string) ($data['ipn_callback_url'] ?? ''),
            'nowpayments_success_url' => (string) ($data['success_url'] ?? ''),
            'nowpayments_cancel_url' => (string) ($data['cancel_url'] ?? ''),
            'nowpayments_sandbox_custody_email' => (string) ($data['sandbox_custody_email'] ?? ''),
            'nowpayments_live_custody_email' => (string) ($data['live_custody_email'] ?? ''),
        ];

        if (filled($data['sandbox_custody_password'] ?? null)) {
            $map['nowpayments_sandbox_custody_password'] = (string) $data['sandbox_custody_password'];
        }

        if (filled($data['live_custody_password'] ?? null)) {
            $map['nowpayments_live_custody_password'] = (string) $data['live_custody_password'];
        }

        foreach ($map as $key => $value) {
            $existing = Setting::query()->where('key', $key)->first();

            if ($existing) {
                $existing->update(['value' => $value, 'group' => 'nowpayments']);
            } else {
                Setting::query()->create([
                    'key' => $key,
                    'value' => $value,
                    'group' => 'nowpayments',
                ]);
            }

            Cache::forget("setting.{$key}");
        }
    }

    private static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("setting.{$key}", 300, function () use ($key, $default) {
            return Setting::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    private static function envSandbox(): bool
    {
        return (bool) config('services.nowpayments.sandbox', false);
    }
}

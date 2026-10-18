<?php

namespace App\Support;

use App\Models\ContactSetting;

class MailPublicUrl
{
    public static function base(): ?string
    {
        $candidates = array_filter([
            config('mail.public_url'),
            config('mail.image_base_url'),
            self::contactWebsiteUrl(),
            config('app.url'),
            'https://one-contract.net',
        ]);

        foreach ($candidates as $base) {
            $normalized = self::normalize((string) $base);
            if ($normalized !== null && ! self::isLocalHost($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * Root used inside signed email links. Falls back to APP_URL for local-only setups.
     */
    public static function linkRoot(): string
    {
        return self::base() ?? rtrim((string) config('app.url'), '/');
    }

    public static function page(string $path): ?string
    {
        $base = self::base();
        if ($base === null) {
            return null;
        }

        return $base.'/'.ltrim($path, '/');
    }

    public static function isLocalHost(?string $url = null): bool
    {
        $url ??= (string) config('app.url');
        $host = parse_url((string) $url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return true;
        }

        $host = strtolower($host);

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.test');
    }

    public static function fromDomain(): string
    {
        $from = (string) config('mail.from.address', '');
        $at = strrchr($from, '@');
        $domain = $at !== false ? strtolower(ltrim($at, '@')) : '';

        if ($domain !== '' && ! in_array($domain, ['localhost', 'example.com', 'localhost.localdomain'], true)) {
            return $domain;
        }

        $host = parse_url(self::linkRoot(), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : 'one-contract.net';
    }

    public static function normalize(string $base): ?string
    {
        $base = trim($base);
        if ($base === '') {
            return null;
        }

        if (! str_starts_with($base, 'http://') && ! str_starts_with($base, 'https://')) {
            $base = 'https://'.$base;
        }

        $parts = parse_url($base);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if (! self::isLocalHost($base)) {
            $scheme = 'https';
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';

        return $scheme.'://'.$host.$port.$path;
    }

    private static function contactWebsiteUrl(): ?string
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('contact_settings')) {
                return null;
            }

            if (! \Illuminate\Support\Facades\Schema::hasColumn('contact_settings', 'website_url')) {
                return null;
            }

            $url = ContactSetting::query()->value('website_url');

            return is_string($url) && $url !== '' ? $url : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

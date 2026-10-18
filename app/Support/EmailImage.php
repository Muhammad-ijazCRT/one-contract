<?php

namespace App\Support;

class EmailImage
{
    /**
     * Public HTTPS logo URL for HTML email.
     * Never uses CID/embed — Hostinger SMTP turns inline images into Gmail attachments.
     */
    public static function logoUrl(): string
    {
        $base = rtrim((string) (self::publicBaseUrl() ?: 'https://one-contract.net'), '/');

        return $base.'/email-assets/logo.jpg?v=3';
    }

    /**
     * Public HTTPS image URL for email templates.
     */
    public static function url(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $absolute = public_path($relativePath);

        if (! is_readable($absolute)) {
            return null;
        }

        return self::publicUrl($relativePath);
    }

    public static function publicUrl(string $relativePath): ?string
    {
        $base = self::publicBaseUrl();
        if ($base === null) {
            return null;
        }

        return $base.'/'.ltrim($relativePath, '/');
    }

    public static function publicBaseUrl(): ?string
    {
        return MailPublicUrl::base();
    }
}

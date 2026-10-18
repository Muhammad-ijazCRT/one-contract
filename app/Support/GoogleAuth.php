<?php

namespace App\Support;

class GoogleAuth
{
    public static function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    public static function showSetupHint(): bool
    {
        return app()->environment('local') && ! self::isConfigured();
    }
}

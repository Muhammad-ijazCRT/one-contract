<?php

namespace App\Http\Controllers;

use App\Support\EmailLogo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmailLogoController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $path = EmailLogo::ensureJpeg();

        return response()->file($path, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
            'Access-Control-Allow-Origin' => '*',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

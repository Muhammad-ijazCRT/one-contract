<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$html = (new App\Mail\VerifyEmailMail('Test User', 'https://example.com/verify'))->render();

if (preg_match_all('/<img[^>]+src="([^"]+)"[^>]*>/', $html, $matches)) {
    foreach ($matches[1] as $src) {
        echo $src.PHP_EOL;
    }
} else {
    echo "No images found\n";
}

file_put_contents(__DIR__.'/../storage/app/verify-email-preview.html', $html);
echo "Preview written to storage/app/verify-email-preview.html\n";

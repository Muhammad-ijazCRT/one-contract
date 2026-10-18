<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = app(App\Services\NowPaymentsClient::class);

if (! $client->isConfigured()) {
    echo "NO_API_KEY\n";
    exit(1);
}

foreach (['usdttrc20', 'usdt', 'usdtomni'] as $slug) {
    try {
        $r = $client->estimate(100, 'usd', $slug);
        echo $slug.' => '.($r['estimated_amount'] ?? 'n/a')."\n";
    } catch (Throwable $e) {
        echo $slug.' => ERROR: '.$e->getMessage()."\n";
    }
}

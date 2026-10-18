<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = app(App\Services\NowPaymentsClient::class);

if (! $client->isConfigured()) {
    echo "NO_API_KEY\n";
    exit(1);
}

try {
    $r = $client->createPayment([
        'price_amount' => 10,
        'price_currency' => 'usd',
        'pay_currency' => 'usdttrc20',
        'order_id' => 'test-'.time(),
        'order_description' => 'Test deposit',
        'ipn_callback_url' => config('services.nowpayments.ipn_callback_url'),
        'is_fixed_rate' => true,
    ]);
    echo json_encode([
        'payment_id' => $r['payment_id'] ?? null,
        'pay_currency' => $r['pay_currency'] ?? null,
        'pay_amount' => $r['pay_amount'] ?? null,
        'pay_address' => isset($r['pay_address']) ? substr((string) $r['pay_address'], 0, 12).'...' : null,
    ], JSON_PRETTY_PRINT)."\n";
} catch (Throwable $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
}

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
    $r = $client->createInvoice([
        'price_amount' => 100,
        'price_currency' => 'usd',
        'pay_currency' => 'usdttrc20',
        'order_id' => 'test-invoice-'.time(),
        'order_description' => 'Test invoice',
        'ipn_callback_url' => config('services.nowpayments.ipn_callback_url'),
        'is_fixed_rate' => true,
    ]);
    echo json_encode([
        'id' => $r['id'] ?? null,
        'invoice_url' => $r['invoice_url'] ?? null,
        'pay_currency' => $r['pay_currency'] ?? null,
    ], JSON_PRETTY_PRINT)."\n";
} catch (Throwable $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
}

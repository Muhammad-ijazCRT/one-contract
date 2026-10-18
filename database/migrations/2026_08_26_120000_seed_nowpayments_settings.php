<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sandbox = filter_var(env('NOWPAYMENTS_SANDBOX', false), FILTER_VALIDATE_BOOLEAN);
        $apiKey = (string) env('NOWPAYMENTS_API_KEY', '');
        $ipnSecret = (string) env('NOWPAYMENTS_IPN_SECRET', '');
        $sandboxUrl = (string) env('NOWPAYMENTS_SANDBOX_URL', 'https://api-sandbox.nowpayments.io');
        $liveUrl = (string) env('NOWPAYMENTS_BASE_URL', 'https://api.nowpayments.io');
        // If BASE_URL was pointing at sandbox API, keep live default as production.
        if (str_contains($liveUrl, 'api-sandbox.nowpayments.io')) {
            $liveUrl = 'https://api.nowpayments.io';
        }
        $checkout = (string) env('NOWPAYMENTS_CHECKOUT_ENDPOINT', '/v1/invoice');
        $callback = (string) (env('NOWPAYMENTS_IPN_CALLBACK_URL')
            ?: rtrim((string) env('APP_URL', 'http://localhost'), '/').'/nowpayments/ipn');
        $success = (string) env('NOWPAYMENTS_SUCCESS_URL', '');
        $cancel = (string) env('NOWPAYMENTS_CANCEL_URL', '');

        $rows = [
            ['key' => 'nowpayments_mode', 'value' => $sandbox ? 'sandbox' : 'live', 'group' => 'nowpayments'],
            ['key' => 'nowpayments_sandbox_api_key', 'value' => $sandbox ? $apiKey : '', 'group' => 'nowpayments'],
            ['key' => 'nowpayments_live_api_key', 'value' => $sandbox ? '' : $apiKey, 'group' => 'nowpayments'],
            ['key' => 'nowpayments_sandbox_ipn_secret', 'value' => $sandbox ? $ipnSecret : '', 'group' => 'nowpayments'],
            ['key' => 'nowpayments_live_ipn_secret', 'value' => $sandbox ? '' : $ipnSecret, 'group' => 'nowpayments'],
            ['key' => 'nowpayments_sandbox_url', 'value' => $sandboxUrl !== '' ? $sandboxUrl : 'https://api-sandbox.nowpayments.io', 'group' => 'nowpayments'],
            ['key' => 'nowpayments_live_url', 'value' => $liveUrl !== '' ? $liveUrl : 'https://api.nowpayments.io', 'group' => 'nowpayments'],
            ['key' => 'nowpayments_checkout_endpoint', 'value' => $checkout !== '' ? $checkout : '/v1/invoice', 'group' => 'nowpayments'],
            ['key' => 'nowpayments_ipn_callback_url', 'value' => $callback, 'group' => 'nowpayments'],
            ['key' => 'nowpayments_success_url', 'value' => $success, 'group' => 'nowpayments'],
            ['key' => 'nowpayments_cancel_url', 'value' => $cancel, 'group' => 'nowpayments'],
        ];

        $now = now();

        foreach ($rows as $row) {
            if (DB::table('settings')->where('key', $row['key'])->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $row['key'],
                'value' => $row['value'],
                'group' => $row['group'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'nowpayments')->delete();
    }
};

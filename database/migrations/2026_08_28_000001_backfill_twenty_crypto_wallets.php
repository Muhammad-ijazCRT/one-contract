<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Enums\WalletCurrency;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $userIds = DB::table('users')->pluck('id');

        foreach ($userIds as $userId) {
            foreach (WalletCurrency::all() as $currency) {
                DB::table('user_wallets')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'currency' => $currency->value,
                    ],
                    [
                        'balance' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $legacy = ['BTC', 'USDT', 'TRX', 'NGN'];
        $newCodes = array_values(array_filter(
            array_map(fn (WalletCurrency $c) => $c->value, WalletCurrency::all()),
            fn (string $code) => ! in_array($code, $legacy, true)
        ));

        if ($newCodes !== []) {
            DB::table('user_wallets')->whereIn('currency', $newCodes)->delete();
        }
    }
};

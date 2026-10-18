<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            ['key' => 'min_deposit', 'value' => '3', 'group' => 'deposits'],
            ['key' => 'min_reward_withdrawal', 'value' => '10', 'group' => 'withdrawals'],
            ['key' => 'platform_wallet_address', 'value' => 'TXyz9kLmN8pQrS4tUvW2xY5zAbCdEfGhJk', 'group' => 'deposits'],
            ['key' => 'auto_approve_deposits', 'value' => '0', 'group' => 'deposits'],
        ];

        foreach ($settings as $setting) {
            if (! DB::table('settings')->where('key', $setting['key'])->exists()) {
                DB::table('settings')->insert([
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'min_deposit',
            'min_reward_withdrawal',
            'platform_wallet_address',
            'auto_approve_deposits',
        ])->delete();
    }
};

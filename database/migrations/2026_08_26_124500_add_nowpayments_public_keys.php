<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $rows = [
            ['key' => 'nowpayments_sandbox_public_key', 'value' => '', 'group' => 'nowpayments'],
            ['key' => 'nowpayments_live_public_key', 'value' => 'cee40cd2-97c7-40d1-b240-b4b779f77f66', 'group' => 'nowpayments'],
            ['key' => 'nowpayments_live_api_key', 'value' => 'NV3DB68-4PQ44HA-KXGT4J5-5CZ8X4G', 'group' => 'nowpayments'],
            ['key' => 'nowpayments_live_ipn_secret', 'value' => 'yjZOCfrNDWz+FEKMGRey448P7yOjMoKn', 'group' => 'nowpayments'],
        ];

        foreach ($rows as $row) {
            $existing = DB::table('settings')->where('key', $row['key'])->first();

            if ($existing) {
                // Only force-seed live credential keys that were empty or newly added public keys.
                if (in_array($row['key'], [
                    'nowpayments_live_api_key',
                    'nowpayments_live_ipn_secret',
                    'nowpayments_live_public_key',
                ], true) && ($existing->value === null || $existing->value === '')) {
                    DB::table('settings')->where('key', $row['key'])->update([
                        'value' => $row['value'],
                        'group' => 'nowpayments',
                        'updated_at' => $now,
                    ]);
                } elseif ($row['key'] === 'nowpayments_sandbox_public_key' && ($existing->value === null || $existing->value === '')) {
                    // keep empty
                } elseif (in_array($row['key'], [
                    'nowpayments_live_api_key',
                    'nowpayments_live_ipn_secret',
                    'nowpayments_live_public_key',
                ], true)) {
                    // Always apply provided live credentials from this setup migration.
                    DB::table('settings')->where('key', $row['key'])->update([
                        'value' => $row['value'],
                        'group' => 'nowpayments',
                        'updated_at' => $now,
                    ]);
                }

                Cache::forget('setting.'.$row['key']);

                continue;
            }

            DB::table('settings')->insert([
                'key' => $row['key'],
                'value' => $row['value'],
                'group' => $row['group'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            Cache::forget('setting.'.$row['key']);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'nowpayments_sandbox_public_key',
            'nowpayments_live_public_key',
        ])->delete();
    }
};

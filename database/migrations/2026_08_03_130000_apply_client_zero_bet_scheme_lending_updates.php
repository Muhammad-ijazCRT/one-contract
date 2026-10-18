<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Investment schemes: 0.1% daily → 3.5% payout every 35 days
        DB::table('investment_clubs')->update([
            'reward_percent' => 0.1,
            'cycle_days' => 35,
            'updated_at' => now(),
        ]);

        $settings = [
            'prediction_min_bet' => ['0', 'prediction'],
            'azubogu_min_bet' => ['0', 'azubogu'],
            'premium_lending_daily_rate_percent' => ['50', 'lending'],
        ];

        foreach ($settings as $key => [$value, $group]) {
            $existing = DB::table('settings')->where('key', $key)->first();
            if ($existing) {
                DB::table('settings')->where('key', $key)->update([
                    'value' => $value,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'group' => $group,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Cache::forget("setting.{$key}");
        }
    }

    public function down(): void
    {
        DB::table('investment_clubs')->update([
            'reward_percent' => 0.03,
            'cycle_days' => 7,
            'updated_at' => now(),
        ]);

        $revert = [
            'prediction_min_bet' => '0.02',
            'azubogu_min_bet' => '0.10',
            'premium_lending_daily_rate_percent' => '60',
        ];

        foreach ($revert as $key => $value) {
            DB::table('settings')->where('key', $key)->update([
                'value' => $value,
                'updated_at' => now(),
            ]);
            Cache::forget("setting.{$key}");
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('settings')->where('key', 'prediction_betting_seconds')->first();
        if ($existing) {
            DB::table('settings')->where('key', 'prediction_betting_seconds')->update([
                'value' => '10',
                'updated_at' => now(),
            ]);
        } else {
            DB::table('settings')->insert([
                'key' => 'prediction_betting_seconds',
                'value' => '10',
                'group' => 'prediction',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::forget('setting.prediction_betting_seconds');
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'prediction_betting_seconds')->update([
            'value' => '6',
            'updated_at' => now(),
        ]);
        Cache::forget('setting.prediction_betting_seconds');
    }
};

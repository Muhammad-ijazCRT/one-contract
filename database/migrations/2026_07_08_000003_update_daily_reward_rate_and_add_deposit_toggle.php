<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('settings')
            ->where('key', 'daily_reward_rate')
            ->update(['value' => '0.01', 'updated_at' => $now]);

        if (! DB::table('settings')->where('key', 'deposits_enabled')->exists()) {
            DB::table('settings')->insert([
                'key' => 'deposits_enabled',
                'value' => '1',
                'group' => 'deposits',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (DB::table('contact_settings')->count() > 0) {
            DB::table('contact_settings')->update([
                'social_telegram' => 'https://t.me/',
                'social_twitter' => null,
                'social_facebook' => null,
                'social_youtube' => null,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'daily_reward_rate')
            ->update(['value' => '0.005', 'updated_at' => now()]);

        DB::table('settings')->where('key', 'deposits_enabled')->delete();
    }
};

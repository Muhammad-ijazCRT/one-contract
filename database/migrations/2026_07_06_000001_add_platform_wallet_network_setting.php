<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('settings')->where('key', 'platform_wallet_network')->exists()) {
            DB::table('settings')->insert([
                'key' => 'platform_wallet_network',
                'value' => 'TRC20',
                'group' => 'deposits',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'platform_wallet_network')->delete();
    }
};

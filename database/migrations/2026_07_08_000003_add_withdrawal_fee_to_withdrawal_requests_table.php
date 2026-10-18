<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->decimal('fee', 16, 8)->default(0)->after('amount');
        });

        $now = now();

        if (! DB::table('settings')->where('key', 'withdrawal_fee_rate')->exists()) {
            DB::table('settings')->insert([
                'key' => 'withdrawal_fee_rate',
                'value' => '0.005',
                'group' => 'withdrawals',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropColumn('fee');
        });

        DB::table('settings')->where('key', 'withdrawal_fee_rate')->delete();
    }
};

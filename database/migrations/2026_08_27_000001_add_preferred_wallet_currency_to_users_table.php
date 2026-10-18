<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'preferred_wallet_currency')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'ngn_wallet_balance')) {
                    $table->string('preferred_wallet_currency', 10)->default('USDT')->after('ngn_wallet_balance');
                } else {
                    $table->string('preferred_wallet_currency', 10)->default('USDT');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'preferred_wallet_currency')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('preferred_wallet_currency');
            });
        }
    }
};

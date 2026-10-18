<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prediction_bets')) {
            return;
        }

        if (! Schema::hasColumn('prediction_bets', 'currency')) {
            Schema::table('prediction_bets', function (Blueprint $table) {
                $table->string('currency', 16)->default('USDT')->after('amount');
                $table->index('currency');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('prediction_bets') && Schema::hasColumn('prediction_bets', 'currency')) {
            Schema::table('prediction_bets', function (Blueprint $table) {
                $table->dropIndex(['currency']);
                $table->dropColumn('currency');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('azubogu_bets')) {
            return;
        }

        if (! Schema::hasColumn('azubogu_bets', 'currency')) {
            Schema::table('azubogu_bets', function (Blueprint $table) {
                $table->string('currency', 16)->default('USDT')->after('amount');
                $table->index('currency');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('azubogu_bets') && Schema::hasColumn('azubogu_bets', 'currency')) {
            Schema::table('azubogu_bets', function (Blueprint $table) {
                $table->dropIndex(['currency']);
                $table->dropColumn('currency');
            });
        }
    }
};

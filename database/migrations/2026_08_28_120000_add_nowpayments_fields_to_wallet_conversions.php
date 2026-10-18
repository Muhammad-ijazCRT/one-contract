<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_conversions', function (Blueprint $table) {
            if (! Schema::hasColumn('wallet_conversions', 'nowpayments_conversion_id')) {
                $table->string('nowpayments_conversion_id', 64)->nullable()->after('status');
            }
            if (! Schema::hasColumn('wallet_conversions', 'provider_fee_percent')) {
                $table->decimal('provider_fee_percent', 8, 4)->default(0)->after('fee_percent');
            }
            if (! Schema::hasColumn('wallet_conversions', 'provider_fee_amount')) {
                $table->decimal('provider_fee_amount', 24, 8)->default(0)->after('provider_fee_percent');
            }
            if (! Schema::hasColumn('wallet_conversions', 'platform_fee_percent')) {
                $table->decimal('platform_fee_percent', 8, 4)->default(1)->after('provider_fee_amount');
            }
            if (! Schema::hasColumn('wallet_conversions', 'platform_fee_amount')) {
                $table->decimal('platform_fee_amount', 24, 8)->default(0)->after('platform_fee_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wallet_conversions', function (Blueprint $table) {
            $columns = [
                'nowpayments_conversion_id',
                'provider_fee_percent',
                'provider_fee_amount',
                'platform_fee_percent',
                'platform_fee_amount',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('wallet_conversions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

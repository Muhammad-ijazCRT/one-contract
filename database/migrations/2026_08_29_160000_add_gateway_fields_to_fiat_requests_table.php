<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiat_requests', function (Blueprint $table) {
            $table->string('gateway', 40)->nullable()->after('status');
            $table->string('gateway_reference')->nullable()->after('gateway');
            $table->string('checkout_url', 500)->nullable()->after('gateway_reference');
            $table->string('gateway_status', 60)->nullable()->after('checkout_url');
            $table->string('bank_code', 20)->nullable()->after('bank_name');
            $table->json('gateway_payload')->nullable()->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('fiat_requests', function (Blueprint $table) {
            $table->dropColumn([
                'gateway',
                'gateway_reference',
                'checkout_url',
                'gateway_status',
                'bank_code',
                'gateway_payload',
            ]);
        });
    }
};

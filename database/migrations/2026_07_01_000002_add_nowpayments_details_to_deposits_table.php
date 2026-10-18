<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('pay_address')->nullable()->after('payment_url');
            $table->string('purchase_id')->nullable()->after('pay_address');
            $table->string('network')->nullable()->after('purchase_id');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn(['pay_address', 'purchase_id', 'network']);
        });
    }
};

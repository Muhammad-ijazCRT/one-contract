<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('payment_id')->nullable()->after('transaction_hash');
            $table->string('payment_url')->nullable()->after('payment_id');
            $table->string('payment_status')->nullable()->after('payment_url');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn(['payment_id', 'payment_url', 'payment_status']);
        });
    }
};

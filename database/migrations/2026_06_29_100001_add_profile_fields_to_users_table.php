<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('password');
            $table->string('member_id')->unique()->nullable()->after('role');
            $table->string('referral_code')->unique()->nullable()->after('member_id');
            $table->foreignId('referred_by')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            $table->string('phone')->nullable()->after('referred_by');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('status')->default('active')->after('avatar');
            $table->text('two_factor_secret')->nullable()->after('status');
            $table->boolean('two_factor_enabled')->default(false)->after('two_factor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn([
                'role',
                'member_id',
                'referral_code',
                'referred_by',
                'phone',
                'avatar',
                'status',
                'two_factor_secret',
                'two_factor_enabled',
            ]);
        });
    }
};

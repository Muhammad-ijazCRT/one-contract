<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deposit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->decimal('amount', 20, 8);
            $table->unsignedTinyInteger('referral_level')->nullable();
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('credited_at');
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};

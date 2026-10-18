<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lucky_dice_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('choice', 8); // high | low
            $table->decimal('amount', 20, 8);
            $table->unsignedTinyInteger('roll')->nullable();
            $table->decimal('payout', 20, 8)->nullable();
            $table->string('status', 20)->default('pending'); // pending | won | lost
            $table->string('server_seed', 64);
            $table->string('server_seed_hash', 64);
            $table->string('client_seed', 64);
            $table->unsignedBigInteger('nonce');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lucky_dice_bets');
    }
};

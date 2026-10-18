<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prediction_rounds')) {
            Schema::create('prediction_rounds', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('round_number')->unique();
                $table->string('status')->default('betting'); // betting|settling|completed
                $table->string('server_seed');
                $table->string('server_seed_hash', 64);
                $table->string('client_seed');
                $table->unsignedBigInteger('nonce')->default(0);
                $table->unsignedTinyInteger('winning_outcome')->nullable(); // 1 or 2
                $table->decimal('total_bets', 20, 8)->default(0);
                $table->decimal('total_payout', 20, 8)->default(0);
                $table->unsignedInteger('total_players')->default(0);
                $table->dateTime('betting_ends_at');
                $table->dateTime('round_ends_at');
                $table->dateTime('started_at');
                $table->dateTime('settled_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'betting_ends_at']);
                $table->index(['status', 'round_ends_at']);
            });
        }

        if (! Schema::hasTable('prediction_bets')) {
            Schema::create('prediction_bets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('prediction_round_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('outcome'); // 1 or 2
                $table->decimal('amount', 20, 8);
                $table->decimal('payout', 20, 8)->nullable();
                $table->string('status')->default('active'); // active|cancelled|won|lost
                $table->timestamps();

                $table->index(['user_id', 'prediction_round_id']);
                $table->index(['prediction_round_id', 'status']);
            });
        }

        $settings = [
            'prediction_min_bet' => '0.10',
            'prediction_max_bet' => '50000',
            'prediction_payout_multiplier' => '1.92',
            'prediction_betting_seconds' => '6',
            'prediction_settle_seconds' => '4',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'prediction']
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_bets');
        Schema::dropIfExists('prediction_rounds');
    }
};

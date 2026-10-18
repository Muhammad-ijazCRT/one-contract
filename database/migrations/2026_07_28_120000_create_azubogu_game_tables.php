<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('azubogu_rounds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('round_number')->unique();
            $table->string('status')->default('betting'); // betting|settling|completed
            $table->string('server_seed');
            $table->string('server_seed_hash', 64);
            $table->string('client_seed');
            $table->unsignedBigInteger('nonce')->default(0);
            $table->json('multipliers')->nullable(); // {"1":1.9,"2":0,...} after settle
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

        Schema::create('azubogu_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('azubogu_round_id')->constrained()->cascadeOnDelete();
            $table->json('numbers'); // [1,5,12] — 1 to 3 picks
            $table->decimal('amount', 20, 8);
            $table->decimal('combined_multiplier', 20, 8)->nullable();
            $table->decimal('payout', 20, 8)->nullable();
            $table->string('status')->default('active'); // active|cancelled|won|lost
            $table->timestamps();

            $table->index(['user_id', 'azubogu_round_id']);
            $table->index(['azubogu_round_id', 'status']);
        });

        $settings = [
            'azubogu_min_bet' => '0.10',
            'azubogu_max_bet' => '50000',
            'azubogu_betting_seconds' => '20',
            'azubogu_settle_seconds' => '10',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'azubogu']
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('azubogu_bets');
        Schema::dropIfExists('azubogu_rounds');
    }
};

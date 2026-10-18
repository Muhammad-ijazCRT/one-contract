<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'ngn_wallet_balance')) {
                $table->decimal('ngn_wallet_balance', 20, 8)->default(0)->after('main_wallet_balance');
            }
        });

        Schema::create('rpsv_matches', function (Blueprint $table) {
            $table->id();
            $table->string('match_code', 16)->unique();
            $table->string('mode', 20); // quick | invite
            $table->string('currency', 8); // USD | NGN
            $table->decimal('stake', 20, 8);
            $table->decimal('prize_pool', 20, 8);
            $table->decimal('platform_fee', 20, 8);
            $table->decimal('winner_payout', 20, 8);
            $table->foreignId('player1_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('player2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('player1_score')->default(0);
            $table->unsignedTinyInteger('player2_score')->default(0);
            $table->unsignedTinyInteger('current_round')->default(1);
            $table->unsignedTinyInteger('max_rounds')->default(4);
            $table->string('status', 24)->default('waiting'); // waiting|queued|active|revealing|result|completed|cancelled|forfeit
            $table->string('phase', 24)->default('select'); // select|reveal|result
            $table->timestamp('phase_ends_at')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('forfeit_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'currency', 'stake']);
            $table->index(['player1_id', 'status']);
            $table->index(['player2_id', 'status']);
        });

        Schema::create('rpsv_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 8);
            $table->decimal('stake', 20, 8);
            $table->foreignId('match_id')->nullable()->constrained('rpsv_matches')->nullOnDelete();
            $table->timestamp('joined_at');
            $table->timestamps();

            $table->unique(['user_id']);
            $table->index(['currency', 'stake', 'joined_at']);
        });

        Schema::create('rpsv_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->string('currency', 8);
            $table->decimal('stake', 20, 8);
            $table->string('status', 20)->default('pending'); // pending|accepted|declined|expired|cancelled
            $table->foreignId('match_id')->nullable()->constrained('rpsv_matches')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['receiver_id', 'status']);
            $table->index(['sender_id', 'status']);
        });

        Schema::create('rpsv_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rpsv_match_id')->constrained('rpsv_matches')->cascadeOnDelete();
            $table->unsignedTinyInteger('round_number');
            $table->string('player1_move', 16)->nullable(); // rock|paper|scissors
            $table->string('player2_move', 16)->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('open'); // open|locked|revealed|done
            $table->timestamp('select_ends_at')->nullable();
            $table->timestamp('reveal_ends_at')->nullable();
            $table->timestamp('result_ends_at')->nullable();
            $table->timestamps();

            $table->unique(['rpsv_match_id', 'round_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rpsv_rounds');
        Schema::dropIfExists('rpsv_invites');
        Schema::dropIfExists('rpsv_queue');
        Schema::dropIfExists('rpsv_matches');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'ngn_wallet_balance')) {
                $table->dropColumn('ngn_wallet_balance');
            }
        });
    }
};

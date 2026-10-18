<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rpsv_matches', function (Blueprint $table) {
            if (! Schema::hasColumn('rpsv_matches', 'player1_ready')) {
                $table->boolean('player1_ready')->default(false)->after('player2_score');
            }
            if (! Schema::hasColumn('rpsv_matches', 'player2_ready')) {
                $table->boolean('player2_ready')->default(false)->after('player1_ready');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rpsv_matches', function (Blueprint $table) {
            if (Schema::hasColumn('rpsv_matches', 'player1_ready')) {
                $table->dropColumn('player1_ready');
            }
            if (Schema::hasColumn('rpsv_matches', 'player2_ready')) {
                $table->dropColumn('player2_ready');
            }
        });
    }
};

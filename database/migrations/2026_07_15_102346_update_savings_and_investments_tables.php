<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('savings_plans', 'name')) {
            Schema::table('savings_plans', function (Blueprint $table) {
                $table->string('name')->after('user_id')->nullable();
            });
        }

        Schema::table('club_investments', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['user_id']);
            
            // Drop the unique constraint
            $table->dropUnique(['user_id', 'investment_club_id']);
            
            // Re-add foreign key
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('savings_plans', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('club_investments', function (Blueprint $table) {
            // Drop the foreign key first
            $table->dropForeign(['user_id']);
            
            // Add the unique index
            $table->unique(['user_id', 'investment_club_id']);
            
            // Re-add foreign key
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};

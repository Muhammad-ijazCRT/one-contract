<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Investment Schemes: names, 0.03% daily, 7-day cycle
        $schemes = [
            'binary' => 'Lion Investment Scheme',
            'crypto' => 'Eagle Investment Scheme',
            'forex' => 'Man Investment Scheme',
            'casino' => 'Ox Investment Scheme',
            'polymarket' => 'Gorilla Investment Scheme',
        ];

        foreach ($schemes as $slug => $name) {
            DB::table('investment_clubs')
                ->where('slug', $slug)
                ->update([
                    'name' => $name,
                    'reward_percent' => 0.03,
                    'cycle_days' => 7,
                    'updated_at' => now(),
                ]);
        }

        $settings = [
            'regular_lending_daily_rate_percent' => '18',
            'regular_lending_duration_days' => '365',
            'regular_lending_min' => '1000',
            'regular_lending_max' => '45000',
            'premium_lending_daily_rate_percent' => '60',
            'premium_lending_duration_days' => '730',
            'premium_lending_min' => '1000',
            'premium_lending_max' => '45000',
            'prediction_betting_seconds' => '6',
            'prediction_settle_seconds' => '6',
        ];

        foreach ($settings as $key => $value) {
            $existing = DB::table('settings')->where('key', $key)->first();
            if ($existing) {
                DB::table('settings')->where('key', $key)->update([
                    'value' => $value,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'group' => str_starts_with($key, 'prediction_') ? 'prediction' : 'lending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::create('prediction_daily_decks', function (Blueprint $table) {
            $table->id();
            $table->date('deck_date')->unique();
            $table->json('outcomes');
            $table->unsignedInteger('cursor')->default(0);
            $table->timestamps();
        });

        if (Schema::hasTable('rewards') && ! Schema::hasColumn('rewards', 'prediction_bet_id')) {
            Schema::table('rewards', function (Blueprint $table) {
                $table->foreignId('prediction_bet_id')
                    ->nullable()
                    ->after('club_investment_id')
                    ->constrained('prediction_bets')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rewards') && Schema::hasColumn('rewards', 'prediction_bet_id')) {
            Schema::table('rewards', function (Blueprint $table) {
                $table->dropConstrainedForeignId('prediction_bet_id');
            });
        }

        Schema::dropIfExists('prediction_daily_decks');
    }
};

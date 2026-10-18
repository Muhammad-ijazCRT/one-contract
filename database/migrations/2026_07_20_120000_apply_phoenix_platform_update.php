<?php

use App\Enums\InvestmentClubSlug;
use App\Models\InvestmentClub;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('investment_clubs', 'max_investment')) {
            Schema::table('investment_clubs', function (Blueprint $table) {
                $table->decimal('max_investment', 20, 8)->nullable()->after('min_investment');
            });
        }

        if (! Schema::hasColumn('rewards', 'claimed_at')) {
            Schema::table('rewards', function (Blueprint $table) {
                $table->timestamp('claimed_at')->nullable()->after('credited_at');
                $table->decimal('platform_fee_paid', 20, 8)->nullable()->after('claimed_at');
            });
        }

        // Existing rewards were already credited to Main Wallet.
        DB::table('rewards')->whereNull('claimed_at')->update([
            'claimed_at' => DB::raw('credited_at'),
            'platform_fee_paid' => 0,
        ]);

        if (! Schema::hasColumn('savings_plans', 'principal')) {
            Schema::table('savings_plans', function (Blueprint $table) {
                $table->decimal('principal', 20, 8)->default(0)->after('name');
                $table->decimal('accrued_interest', 20, 8)->default(0)->after('total_saved');
                $table->date('last_interest_date')->nullable()->after('accrued_interest');
                $table->timestamp('matured_at')->nullable()->after('completed_at');
            });
        }

        if (! Schema::hasTable('lending_plans')) {
            Schema::create('lending_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type'); // regular | premium
                $table->decimal('principal', 20, 8);
                $table->decimal('daily_rate_percent', 8, 4);
                $table->unsignedSmallInteger('duration_days');
                $table->decimal('accrued_interest', 20, 8)->default(0);
                $table->string('status')->default('active');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('matures_at')->nullable();
                $table->timestamp('matured_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->date('last_interest_date')->nullable();
                $table->timestamp('early_withdrawal_requested_at')->nullable();
                $table->timestamp('early_withdrawal_available_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['status', 'matures_at']);
            });
        }

        $clubSpecs = [
            InvestmentClubSlug::Binary->value => ['name' => 'Binary Trade Club', 'reward_percent' => 0.34, 'cycle_days' => 1, 'min_investment' => 1, 'max_investment' => 450, 'sort_order' => 1],
            InvestmentClubSlug::Crypto->value => ['name' => 'Crypto Trade Club', 'reward_percent' => 0.40, 'cycle_days' => 1, 'min_investment' => 1, 'max_investment' => 450, 'sort_order' => 2],
            InvestmentClubSlug::Forex->value => ['name' => 'Forex Trade Club', 'reward_percent' => 0.34, 'cycle_days' => 1, 'min_investment' => 1, 'max_investment' => 450, 'sort_order' => 3],
            InvestmentClubSlug::Casino->value => ['name' => 'Casino Club', 'reward_percent' => 0.34, 'cycle_days' => 1, 'min_investment' => 1, 'max_investment' => 450, 'sort_order' => 4],
            InvestmentClubSlug::Polymarket->value => ['name' => 'Poly Market Club', 'reward_percent' => 0.34, 'cycle_days' => 1, 'min_investment' => 1, 'max_investment' => 450, 'sort_order' => 5],
        ];

        foreach ($clubSpecs as $slug => $spec) {
            InvestmentClub::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $spec['name'],
                    'reward_percent' => $spec['reward_percent'],
                    'cycle_days' => $spec['cycle_days'],
                    'min_investment' => $spec['min_investment'],
                    'max_investment' => $spec['max_investment'],
                    'is_active' => true,
                    'sort_order' => $spec['sort_order'],
                ]
            );
        }

        $settings = [
            'platform_fee_rate' => ['0.10', 'fees'],
            'savings_daily_rate_percent' => ['0.139', 'dual_wallet'],
            'savings_min_principal' => ['1', 'dual_wallet'],
            'savings_max_principal' => ['45000', 'dual_wallet'],
            'savings_target_days' => ['100', 'dual_wallet'],
            'regular_lending_daily_rate_percent' => ['0.167', 'lending'],
            'regular_lending_duration_days' => ['100', 'lending'],
            'regular_lending_min' => ['1000', 'lending'],
            'regular_lending_max' => ['45000', 'lending'],
            'premium_lending_daily_rate_percent' => ['0.20', 'lending'],
            'premium_lending_duration_days' => ['365', 'lending'],
            'premium_lending_min' => ['5000', 'lending'],
            'premium_lending_max' => ['45000', 'lending'],
            'lending_early_return_hours' => ['72', 'lending'],
        ];

        foreach ($settings as $key => [$value, $group]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lending_plans');

        if (Schema::hasColumn('savings_plans', 'principal')) {
            Schema::table('savings_plans', function (Blueprint $table) {
                $table->dropColumn(['principal', 'accrued_interest', 'last_interest_date', 'matured_at']);
            });
        }

        if (Schema::hasColumn('rewards', 'claimed_at')) {
            Schema::table('rewards', function (Blueprint $table) {
                $table->dropColumn(['claimed_at', 'platform_fee_paid']);
            });
        }

        if (Schema::hasColumn('investment_clubs', 'max_investment')) {
            Schema::table('investment_clubs', function (Blueprint $table) {
                $table->dropColumn('max_investment');
            });
        }
    }
};

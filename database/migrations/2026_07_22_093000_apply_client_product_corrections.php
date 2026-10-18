<?php

use App\Enums\InvestmentClubSlug;
use App\Models\InvestmentClub;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'kyc_status')) {
                $table->string('kyc_status', 20)->default('pending')->after('id_card_verified_at');
            }
            if (! Schema::hasColumn('users', 'kyc_reviewed_at')) {
                $table->timestamp('kyc_reviewed_at')->nullable()->after('kyc_status');
            }
            if (! Schema::hasColumn('users', 'kyc_review_note')) {
                $table->string('kyc_review_note', 500)->nullable()->after('kyc_reviewed_at');
            }
        });

        // Existing users with captured docs: treat already-verified as approved
        User::query()
            ->whereNotNull('face_image_path')
            ->whereNotNull('id_card_image_path')
            ->whereNotNull('face_verified_at')
            ->whereNotNull('id_card_verified_at')
            ->where(function ($q) {
                $q->whereNull('kyc_status')->orWhere('kyc_status', 'pending');
            })
            ->update(['kyc_status' => 'approved', 'kyc_reviewed_at' => now()]);

        $clubs = [
            InvestmentClubSlug::Binary->value => ['reward_percent' => 0.34, 'cycle_days' => 7],
            InvestmentClubSlug::Crypto->value => ['reward_percent' => 0.40, 'cycle_days' => 14],
            InvestmentClubSlug::Forex->value => ['reward_percent' => 0.34, 'cycle_days' => 7],
            InvestmentClubSlug::Casino->value => ['reward_percent' => 0.40, 'cycle_days' => 14],
            InvestmentClubSlug::Polymarket->value => ['reward_percent' => 0.34, 'cycle_days' => 7],
        ];

        foreach ($clubs as $slug => $data) {
            InvestmentClub::query()->where('slug', $slug)->update($data);
        }

        $settings = [
            'savings_min_daily' => ['0.10', 'dual_wallet'],
            'savings_max_daily' => ['450', 'dual_wallet'],
            'savings_reward_percent' => ['12.5', 'dual_wallet'],
            'savings_target_days' => ['100', 'dual_wallet'],
            'savings_early_return_hours' => ['72', 'dual_wallet'],
            'regular_lending_daily_rate_percent' => ['15', 'lending'],
            'premium_lending_daily_rate_percent' => ['73', 'lending'],
            'club_capital_return_hours' => ['72', 'dual_wallet'],
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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'kyc_review_note')) {
                $table->dropColumn('kyc_review_note');
            }
            if (Schema::hasColumn('users', 'kyc_reviewed_at')) {
                $table->dropColumn('kyc_reviewed_at');
            }
            if (Schema::hasColumn('users', 'kyc_status')) {
                $table->dropColumn('kyc_status');
            }
        });
    }
};

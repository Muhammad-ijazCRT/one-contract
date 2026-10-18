<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Split legacy referral_commission into gaming vs investment scheme referrals.
        DB::table('rewards')
            ->where('type', 'referral_commission')
            ->whereNotNull('club_investment_id')
            ->update([
                'type' => 'investment_scheme_referral',
                'updated_at' => now(),
            ]);

        DB::table('rewards')
            ->where('type', 'referral_commission')
            ->whereNotNull('prediction_bet_id')
            ->update([
                'type' => 'gaming_referral',
                'updated_at' => now(),
            ]);

        // Remaining referrals (e.g. Azubogu with no FK) → gaming.
        DB::table('rewards')
            ->where('type', 'referral_commission')
            ->update([
                'type' => 'gaming_referral',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('rewards')
            ->whereIn('type', ['gaming_referral', 'investment_scheme_referral'])
            ->update([
                'type' => 'referral_commission',
                'updated_at' => now(),
            ]);
    }
};

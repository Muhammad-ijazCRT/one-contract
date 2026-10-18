<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('investment_clubs', function (Blueprint $table) {
            $table->decimal('min_investment', 20, 8)->default(1.00)->change();
        });

        // Update rates and min amounts for existing clubs
        $rates = [
            'binary' => 1.0,
            'crypto' => 1.5,
            'forex' => 1.0,
            'casino' => 1.5,
            'polymarket' => 1.0,
        ];

        foreach ($rates as $slug => $rate) {
            DB::table('investment_clubs')
                ->where('slug', $slug)
                ->update([
                    'reward_percent' => $rate,
                    'min_investment' => 1.00
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

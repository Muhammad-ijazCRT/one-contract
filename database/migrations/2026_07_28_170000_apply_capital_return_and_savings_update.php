<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'savings_min_daily' => ['0.10', 'dual_wallet'],
            'savings_max_daily' => ['125', 'dual_wallet'],
            'savings_reward_percent' => ['18', 'dual_wallet'],
            'savings_target_days' => ['360', 'dual_wallet'],
            'savings_early_return_hours' => ['72', 'dual_wallet'],
            'club_capital_return_hours' => ['72', 'dual_wallet'],
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
        $settings = [
            'savings_max_daily' => ['450', 'dual_wallet'],
            'savings_reward_percent' => ['12.5', 'dual_wallet'],
            'savings_target_days' => ['100', 'dual_wallet'],
        ];

        foreach ($settings as $key => [$value, $group]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }
    }
};

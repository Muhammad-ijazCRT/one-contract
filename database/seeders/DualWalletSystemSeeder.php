<?php

namespace Database\Seeders;

use App\Enums\InvestmentClubSlug;
use App\Models\InvestmentClub;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class DualWalletSystemSeeder extends Seeder
{
    public function run(): void
    {
        $clubs = [
            [
                'slug' => InvestmentClubSlug::Binary,
                'name' => 'Lion Investment Scheme',
                'reward_percent' => 0.1,
                'cycle_days' => 35,
                'min_investment' => 1,
                'max_investment' => 450,
                'sort_order' => 1,
            ],
            [
                'slug' => InvestmentClubSlug::Crypto,
                'name' => 'Eagle Investment Scheme',
                'reward_percent' => 0.1,
                'cycle_days' => 35,
                'min_investment' => 1,
                'max_investment' => 450,
                'sort_order' => 2,
            ],
            [
                'slug' => InvestmentClubSlug::Forex,
                'name' => 'Man Investment Scheme',
                'reward_percent' => 0.1,
                'cycle_days' => 35,
                'min_investment' => 1,
                'max_investment' => 450,
                'sort_order' => 3,
            ],
            [
                'slug' => InvestmentClubSlug::Casino,
                'name' => 'Ox Investment Scheme',
                'reward_percent' => 0.1,
                'cycle_days' => 35,
                'min_investment' => 1,
                'max_investment' => 450,
                'sort_order' => 4,
            ],
            [
                'slug' => InvestmentClubSlug::Polymarket,
                'name' => 'Gorilla Investment Scheme',
                'reward_percent' => 0.1,
                'cycle_days' => 35,
                'min_investment' => 1,
                'max_investment' => 450,
                'sort_order' => 5,
            ],
        ];

        foreach ($clubs as $club) {
            InvestmentClub::query()->updateOrCreate(
                ['slug' => $club['slug']->value],
                [
                    'name' => $club['name'],
                    'reward_percent' => $club['reward_percent'],
                    'cycle_days' => $club['cycle_days'],
                    'min_investment' => $club['min_investment'],
                    'max_investment' => $club['max_investment'],
                    'is_active' => true,
                    'sort_order' => $club['sort_order'],
                ]
            );
        }

        $settings = [
            'platform_fee_rate' => ['0.10', 'fees'],
            'savings_min_daily' => ['0.10', 'dual_wallet'],
            'savings_max_daily' => ['125', 'dual_wallet'],
            'savings_reward_percent' => ['18', 'dual_wallet'],
            'savings_target_days' => ['360', 'dual_wallet'],
            'club_capital_return_hours' => ['72', 'dual_wallet'],
            'savings_early_return_hours' => ['72', 'dual_wallet'],
            'regular_lending_daily_rate_percent' => ['18', 'lending'],
            'regular_lending_duration_days' => ['365', 'lending'],
            'regular_lending_min' => ['1000', 'lending'],
            'regular_lending_max' => ['45000', 'lending'],
            'premium_lending_daily_rate_percent' => ['50', 'lending'],
            'premium_lending_duration_days' => ['730', 'lending'],
            'premium_lending_min' => ['1000', 'lending'],
            'premium_lending_max' => ['45000', 'lending'],
            'lending_early_return_hours' => ['72', 'lending'],
            'prediction_betting_seconds' => ['10', 'prediction'],
            'prediction_settle_seconds' => ['6', 'prediction'],
            'prediction_min_bet' => ['0', 'prediction'],
            'prediction_max_bet' => ['1000', 'prediction'],
            'azubogu_min_bet' => ['0', 'azubogu'],
        ];

        foreach ($settings as $key => [$value, $group]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }
    }
}

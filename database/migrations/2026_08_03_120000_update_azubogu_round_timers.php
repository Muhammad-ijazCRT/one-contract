<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'azubogu_betting_seconds'],
            ['value' => '20', 'group' => 'azubogu']
        );

        Setting::query()->updateOrCreate(
            ['key' => 'azubogu_settle_seconds'],
            ['value' => '10', 'group' => 'azubogu']
        );

        Cache::forget('setting.azubogu_betting_seconds');
        Cache::forget('setting.azubogu_settle_seconds');
    }

    public function down(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'azubogu_betting_seconds'],
            ['value' => '12', 'group' => 'azubogu']
        );

        Setting::query()->updateOrCreate(
            ['key' => 'azubogu_settle_seconds'],
            ['value' => '6', 'group' => 'azubogu']
        );

        Cache::forget('setting.azubogu_betting_seconds');
        Cache::forget('setting.azubogu_settle_seconds');
    }
};

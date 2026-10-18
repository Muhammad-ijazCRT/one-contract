<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    /**
     * @return array<string, string>
     */
    public function allGrouped(): array
    {
        return Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group')
            ->map(fn ($items) => $items->pluck('value', 'key')->all())
            ->all();
    }

    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            $existing = Setting::query()->where('key', $key)->first();

            if ($existing) {
                $existing->update(['value' => (string) $value]);
            } else {
                Setting::query()->create([
                    'key' => $key,
                    'value' => (string) $value,
                    'group' => str_starts_with($key, 'savings_') || str_starts_with($key, 'club_')
                        ? 'dual_wallet'
                        : (str_starts_with($key, 'lending_') || str_starts_with($key, 'regular_lending_') || str_starts_with($key, 'premium_lending_')
                            ? 'lending'
                            : (str_starts_with($key, 'platform_fee')
                                ? 'fees'
                                : (str_starts_with($key, 'prediction_') ? 'prediction' : 'general'))),
                ]);
            }

            Cache::forget("setting.{$key}");
        }
    }
}

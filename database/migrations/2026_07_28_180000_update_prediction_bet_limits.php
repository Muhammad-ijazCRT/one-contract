<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'prediction_min_bet' => ['0.02', 'prediction'],
            'prediction_max_bet' => ['1000', 'prediction'],
        ] as $key => [$value, $group]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }
    }

    public function down(): void
    {
        foreach ([
            'prediction_min_bet' => ['0.10', 'prediction'],
            'prediction_max_bet' => ['50000', 'prediction'],
        ] as $key => [$value, $group]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }
    }
};

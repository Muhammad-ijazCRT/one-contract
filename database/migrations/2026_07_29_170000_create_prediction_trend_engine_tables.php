<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prediction_trend_libraries')) {
            Schema::create('prediction_trend_libraries', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('game_mode')->default('prediction_duel')->index();
                $table->string('source_type')->default('txt');
                $table->string('source_name')->nullable();
                $table->string('source_path')->nullable();
                $table->string('source_checksum', 64)->nullable();
                $table->string('difficulty')->nullable();
                $table->string('pattern_type')->nullable();
                $table->boolean('use_weighted_selection')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('trend_count')->default(0);
                $table->unsignedTinyInteger('outcomes_per_trend')->default(20);
                $table->timestamp('starts_at')->nullable()->index();
                $table->timestamp('ends_at')->nullable()->index();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('uploaded_via')->default('system');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('prediction_trends')) {
            Schema::create('prediction_trends', function (Blueprint $table) {
                $table->id();
                $table->foreignId('prediction_trend_library_id')->constrained('prediction_trend_libraries')->cascadeOnDelete();
                $table->string('pattern', 100);
                $table->unsignedTinyInteger('outcome_count')->default(20);
                $table->string('difficulty')->nullable();
                $table->string('pattern_type')->nullable();
                $table->unsignedInteger('weight')->default(1);
                $table->unsignedInteger('ordinal')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['prediction_trend_library_id', 'is_active']);
                $table->index(['prediction_trend_library_id', 'difficulty']);
                $table->index(['prediction_trend_library_id', 'pattern_type']);
            });
        }

        if (! Schema::hasTable('prediction_trend_states')) {
            Schema::create('prediction_trend_states', function (Blueprint $table) {
                $table->id();
                $table->string('game_mode')->unique();
                $table->foreignId('prediction_trend_library_id')->nullable()->constrained('prediction_trend_libraries')->nullOnDelete();
                $table->foreignId('prediction_trend_id')->nullable()->constrained('prediction_trends')->nullOnDelete();
                $table->unsignedTinyInteger('cursor')->default(0);
                $table->unsignedTinyInteger('last_outcome')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        foreach ([
            'prediction_trend_source_path' => [dirname(base_path(), 2).DIRECTORY_SEPARATOR.'1000_diverse_arrangements_10ones10twos.txt', 'prediction'],
        ] as $key => [$value, $group]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_trend_states');
        Schema::dropIfExists('prediction_trends');
        Schema::dropIfExists('prediction_trend_libraries');
    }
};

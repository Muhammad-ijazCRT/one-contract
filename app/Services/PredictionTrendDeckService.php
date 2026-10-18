<?php

namespace App\Services;

use App\Models\PredictionTrend;
use App\Models\PredictionTrendLibrary;
use App\Models\PredictionTrendState;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Supplies prediction outcomes from predefined trend libraries.
 */
class PredictionTrendDeckService
{
    public const DEFAULT_GAME_MODE = 'prediction_duel';

    public function nextOutcome(string $gameMode = self::DEFAULT_GAME_MODE): int
    {
        return (int) DB::transaction(function () use ($gameMode) {
            $library = $this->resolveActiveLibrary($gameMode, true);

            if (! $library) {
                return random_int(1, 2);
            }

            $state = PredictionTrendState::query()
                ->where('game_mode', $gameMode)
                ->lockForUpdate()
                ->first();

            if (! $state) {
                $state = PredictionTrendState::query()->create(['game_mode' => $gameMode]);
                $state = PredictionTrendState::query()->whereKey($state->id)->lockForUpdate()->firstOrFail();
            }

            if ((int) $state->prediction_trend_library_id !== (int) $library->id) {
                $state->fill([
                    'prediction_trend_library_id' => $library->id,
                    'prediction_trend_id' => null,
                    'cursor' => 0,
                    'completed_at' => null,
                ])->save();
            }

            $trend = $state->prediction_trend_id
                ? PredictionTrend::query()
                    ->whereKey($state->prediction_trend_id)
                    ->where('prediction_trend_library_id', $library->id)
                    ->where('is_active', true)
                    ->first()
                : null;

            if (! $trend || $state->cursor >= $trend->outcome_count) {
                $trend = $this->selectTrend($library);

                if (! $trend) {
                    return random_int(1, 2);
                }

                $state->fill([
                    'prediction_trend_library_id' => $library->id,
                    'prediction_trend_id' => $trend->id,
                    'cursor' => 0,
                    'activated_at' => now(),
                    'completed_at' => null,
                ])->save();
            }

            $cursor = (int) $state->cursor;
            $outcome = (int) substr($trend->pattern, $cursor, 1);
            if (! in_array($outcome, [1, 2], true)) {
                $outcome = 1;
            }

            $nextCursor = $cursor + 1;

            $state->fill([
                'cursor' => $nextCursor,
                'last_outcome' => $outcome,
                'completed_at' => $nextCursor >= $trend->outcome_count ? now() : null,
            ])->save();

            return $outcome;
        });
    }

    public function outcomeSourceLabel(string $gameMode = self::DEFAULT_GAME_MODE): string
    {
        $library = $this->resolveActiveLibrary($gameMode, true);

        if (! $library) {
            return 'Random fallback';
        }

        $parts = [
            number_format((int) $library->trend_count).' trends',
            ((int) $library->outcomes_per_trend).' outcomes each',
        ];

        if ($library->difficulty) {
            $parts[] = Str::headline($library->difficulty);
        }

        if ($library->pattern_type) {
            $parts[] = Str::headline($library->pattern_type);
        }

        if ($library->use_weighted_selection) {
            $parts[] = 'weighted';
        }

        return implode(' | ', $parts);
    }

    public function activeLibrarySummary(string $gameMode = self::DEFAULT_GAME_MODE): ?array
    {
        $library = $this->resolveActiveLibrary($gameMode, true);

        if (! $library) {
            return null;
        }

        return [
            'id' => $library->id,
            'name' => $library->name,
            'game_mode' => $library->game_mode,
            'trend_count' => (int) $library->trend_count,
            'outcomes_per_trend' => (int) $library->outcomes_per_trend,
            'difficulty' => $library->difficulty,
            'pattern_type' => $library->pattern_type,
            'weighted' => (bool) $library->use_weighted_selection,
            'starts_at' => $library->starts_at?->toIso8601String(),
            'ends_at' => $library->ends_at?->toIso8601String(),
        ];
    }

    public function importUploadedLibrary(UploadedFile $file, array $attributes, ?User $admin = null): PredictionTrendLibrary
    {
        Storage::disk('local')->makeDirectory('prediction-trends');

        $storedPath = $file->storeAs(
            'prediction-trends',
            now()->format('Ymd_His').'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension(),
            'local'
        );

        return $this->importTrendFile(
            Storage::disk('local')->path($storedPath),
            array_merge($attributes, [
                'source_name' => $file->getClientOriginalName(),
                'source_type' => strtolower($file->getClientOriginalExtension() ?: 'txt'),
                'uploaded_by' => $admin?->id,
                'uploaded_via' => 'admin_upload',
            ])
        );
    }

    public function activateLibrary(PredictionTrendLibrary $library): void
    {
        DB::transaction(function () use ($library) {
            PredictionTrendLibrary::query()
                ->where('game_mode', $library->game_mode)
                ->whereKeyNot($library->id)
                ->update(['is_active' => false]);

            $library->update(['is_active' => true]);

            PredictionTrendState::query()
                ->where('game_mode', $library->game_mode)
                ->update([
                    'prediction_trend_library_id' => $library->id,
                    'prediction_trend_id' => null,
                    'cursor' => 0,
                    'completed_at' => null,
                    'updated_at' => now(),
                ]);
        });
    }

    public function resolveActiveLibrary(string $gameMode = self::DEFAULT_GAME_MODE, bool $bootstrap = false): ?PredictionTrendLibrary
    {
        if ($bootstrap) {
            $this->ensureSystemLibraryLoaded($gameMode);
        }

        $now = now();

        return PredictionTrendLibrary::query()
            ->where('game_mode', $gameMode)
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->first();
    }

    public function ensureSystemLibraryLoaded(string $gameMode = self::DEFAULT_GAME_MODE): ?PredictionTrendLibrary
    {
        $path = PlatformSettings::get('prediction_trend_source_path', $this->defaultSourcePath()) ?: $this->defaultSourcePath();

        if (! is_file($path)) {
            return null;
        }

        $checksum = hash_file('sha256', $path) ?: null;

        $existing = PredictionTrendLibrary::query()
            ->where('game_mode', $gameMode)
            ->where('uploaded_via', 'system')
            ->where('source_path', $path)
            ->first();

        if ($existing && $existing->source_checksum === $checksum && $existing->trend_count > 0) {
            return $existing;
        }

        return $this->importTrendFile($path, [
            'name' => 'Default Duel Library',
            'slug' => 'default-duel-library',
            'game_mode' => $gameMode,
            'source_name' => basename($path),
            'source_type' => pathinfo($path, PATHINFO_EXTENSION) ?: 'txt',
            'difficulty' => 'standard',
            'pattern_type' => 'balanced',
            'use_weighted_selection' => false,
            'is_active' => true,
            'uploaded_via' => 'system',
        ], $existing);
    }

    public function importTrendFile(string $sourcePath, array $attributes, ?PredictionTrendLibrary $library = null): PredictionTrendLibrary
    {
        if (! is_file($sourcePath)) {
            throw ValidationException::withMessages([
                'trend_file' => 'Trend file could not be found.',
            ]);
        }

        $parsed = $this->parseTrendFile(
            $sourcePath,
            $attributes['difficulty'] ?? null,
            $attributes['pattern_type'] ?? null,
            (int) ($attributes['default_weight'] ?? 1)
        );

        return DB::transaction(function () use ($sourcePath, $attributes, $library, $parsed) {
            $gameMode = $attributes['game_mode'] ?? self::DEFAULT_GAME_MODE;
            $activate = (bool) ($attributes['is_active'] ?? true);

            if (! $library) {
                $library = PredictionTrendLibrary::query()->create([
                    'name' => $attributes['name'] ?? 'Prediction Trend Library',
                    'slug' => $this->uniqueSlug($attributes['slug'] ?? Str::slug($attributes['name'] ?? 'prediction-trend-library')),
                    'game_mode' => $gameMode,
                    'source_type' => strtolower((string) ($attributes['source_type'] ?? pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'txt')),
                    'source_name' => $attributes['source_name'] ?? basename($sourcePath),
                    'source_path' => $sourcePath,
                    'source_checksum' => hash_file('sha256', $sourcePath) ?: null,
                    'difficulty' => $attributes['difficulty'] ?? null,
                    'pattern_type' => $attributes['pattern_type'] ?? null,
                    'use_weighted_selection' => (bool) ($attributes['use_weighted_selection'] ?? false),
                    'is_active' => $activate,
                    'trend_count' => count($parsed),
                    'outcomes_per_trend' => (int) ($parsed[0]['outcome_count'] ?? 20),
                    'starts_at' => $attributes['starts_at'] ?? null,
                    'ends_at' => $attributes['ends_at'] ?? null,
                    'uploaded_by' => $attributes['uploaded_by'] ?? null,
                    'uploaded_via' => $attributes['uploaded_via'] ?? 'admin_upload',
                ]);
            } else {
                $library->update([
                    'name' => $attributes['name'] ?? $library->name,
                    'slug' => $library->slug,
                    'game_mode' => $gameMode,
                    'source_type' => strtolower((string) ($attributes['source_type'] ?? $library->source_type ?? 'txt')),
                    'source_name' => $attributes['source_name'] ?? basename($sourcePath),
                    'source_path' => $sourcePath,
                    'source_checksum' => hash_file('sha256', $sourcePath) ?: null,
                    'difficulty' => $attributes['difficulty'] ?? null,
                    'pattern_type' => $attributes['pattern_type'] ?? null,
                    'use_weighted_selection' => (bool) ($attributes['use_weighted_selection'] ?? false),
                    'is_active' => $activate,
                    'trend_count' => count($parsed),
                    'outcomes_per_trend' => (int) ($parsed[0]['outcome_count'] ?? 20),
                    'starts_at' => $attributes['starts_at'] ?? null,
                    'ends_at' => $attributes['ends_at'] ?? null,
                    'uploaded_by' => $attributes['uploaded_by'] ?? $library->uploaded_by,
                    'uploaded_via' => $attributes['uploaded_via'] ?? $library->uploaded_via,
                ]);

                $library->trends()->delete();
            }

            if ($activate) {
                PredictionTrendLibrary::query()
                    ->where('game_mode', $gameMode)
                    ->whereKeyNot($library->id)
                    ->update(['is_active' => false]);
            }

            foreach (array_chunk($parsed, 250) as $chunk) {
                PredictionTrend::query()->insert(array_map(function (array $trend) use ($library) {
                    return [
                        'prediction_trend_library_id' => $library->id,
                        'pattern' => $trend['pattern'],
                        'outcome_count' => $trend['outcome_count'],
                        'difficulty' => $trend['difficulty'],
                        'pattern_type' => $trend['pattern_type'],
                        'weight' => $trend['weight'],
                        'ordinal' => $trend['ordinal'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $chunk));
            }

            if ($activate) {
                PredictionTrendState::query()->updateOrCreate(
                    ['game_mode' => $gameMode],
                    [
                        'prediction_trend_library_id' => $library->id,
                        'prediction_trend_id' => null,
                        'cursor' => 0,
                        'completed_at' => null,
                    ]
                );
            }

            return $library->fresh();
        });
    }

    /**
     * @return array<int, array{pattern:string,outcome_count:int,difficulty:?string,pattern_type:?string,weight:int,ordinal:int}>
     */
    private function parseTrendFile(string $sourcePath, ?string $fallbackDifficulty, ?string $fallbackPatternType, int $fallbackWeight): array
    {
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $items = $extension === 'csv'
            ? $this->parseCsvTrendFile($sourcePath, $fallbackDifficulty, $fallbackPatternType, $fallbackWeight)
            : $this->parseTxtTrendFile($sourcePath, $fallbackDifficulty, $fallbackPatternType, $fallbackWeight);

        if ($items === []) {
            throw ValidationException::withMessages([
                'trend_file' => 'Trend file is empty.',
            ]);
        }

        $patterns = array_column($items, 'pattern');
        if (count($patterns) !== count(array_unique($patterns))) {
            throw ValidationException::withMessages([
                'trend_file' => 'Trend file contains duplicate patterns. Each trend must be unique.',
            ]);
        }

        return $items;
    }

    private function parseTxtTrendFile(string $sourcePath, ?string $fallbackDifficulty, ?string $fallbackPatternType, int $fallbackWeight): array
    {
        $lines = file($sourcePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $items = [];

        foreach ($lines as $index => $line) {
            $pattern = trim((string) $line);
            $this->assertValidPattern($pattern, $index + 1);

            $items[] = [
                'pattern' => $pattern,
                'outcome_count' => strlen($pattern),
                'difficulty' => $fallbackDifficulty,
                'pattern_type' => $fallbackPatternType,
                'weight' => max(1, $fallbackWeight),
                'ordinal' => $index,
            ];
        }

        return $items;
    }

    private function parseCsvTrendFile(string $sourcePath, ?string $fallbackDifficulty, ?string $fallbackPatternType, int $fallbackWeight): array
    {
        $handle = fopen($sourcePath, 'rb');
        if (! $handle) {
            throw ValidationException::withMessages([
                'trend_file' => 'Unable to read the uploaded CSV trend file.',
            ]);
        }

        $header = fgetcsv($handle) ?: [];
        $normalized = array_map(fn ($item) => Str::snake(trim((string) $item)), $header);
        $items = [];
        $rowIndex = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowIndex++;
            if ($row === [null] || $row === false) {
                continue;
            }

            $mapped = [];
            foreach ($normalized as $i => $key) {
                $mapped[$key] = isset($row[$i]) ? trim((string) $row[$i]) : null;
            }

            $pattern = (string) ($mapped['pattern'] ?? $mapped['trend'] ?? $mapped['outcomes'] ?? '');
            $this->assertValidPattern($pattern, $rowIndex);

            $items[] = [
                'pattern' => $pattern,
                'outcome_count' => strlen($pattern),
                'difficulty' => $mapped['difficulty'] ?: $fallbackDifficulty,
                'pattern_type' => $mapped['pattern_type'] ?: $fallbackPatternType,
                'weight' => max(1, (int) ($mapped['weight'] ?: $fallbackWeight)),
                'ordinal' => count($items),
            ];
        }

        fclose($handle);

        return $items;
    }

    private function assertValidPattern(string $pattern, int $lineNumber): void
    {
        if (! preg_match('/^[12]{20}$/', $pattern)) {
            throw ValidationException::withMessages([
                'trend_file' => 'Invalid trend on line '.$lineNumber.'. Every trend must contain exactly 20 digits using only 1 and 2.',
            ]);
        }
    }

    private function selectTrend(PredictionTrendLibrary $library): ?PredictionTrend
    {
        $trends = PredictionTrend::query()
            ->where('prediction_trend_library_id', $library->id)
            ->where('is_active', true)
            ->get(['id', 'pattern', 'outcome_count', 'weight']);

        if ($trends->isEmpty()) {
            return null;
        }

        if (! $library->use_weighted_selection) {
            return $trends->random();
        }

        $totalWeight = max(1, (int) $trends->sum(fn (PredictionTrend $trend) => max(1, (int) $trend->weight)));
        $pick = random_int(1, $totalWeight);
        $cursor = 0;

        foreach ($trends as $trend) {
            $cursor += max(1, (int) $trend->weight);
            if ($pick <= $cursor) {
                return $trend;
            }
        }

        return $trends->last();
    }

    private function uniqueSlug(string $base): string
    {
        $base = Str::slug($base) ?: 'prediction-trend-library';
        $slug = $base;
        $counter = 2;

        while (PredictionTrendLibrary::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function defaultSourcePath(): string
    {
        return dirname(base_path(), 2).DIRECTORY_SEPARATOR.'1000_diverse_arrangements_10ones10twos.txt';
    }
}



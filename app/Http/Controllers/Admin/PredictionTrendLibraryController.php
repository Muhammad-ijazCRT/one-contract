<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PredictionTrendLibrary;
use App\Services\AdminLogService;
use App\Services\PredictionTrendDeckService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PredictionTrendLibraryController extends Controller
{
    public function __construct(
        private readonly PredictionTrendDeckService $trendDeck,
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(): View
    {
        $libraries = PredictionTrendLibrary::query()
            ->withCount('trends')
            ->orderByDesc('is_active')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.prediction-trends.index', [
            'libraries' => $libraries,
            'activeLibrary' => $this->trendDeck->activeLibrarySummary(),
            'defaultSourcePath' => \App\Services\PlatformSettings::get('prediction_trend_source_path'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'game_mode' => ['required', 'string', 'max:60'],
            'trend_file' => ['required', 'file', 'mimes:txt,csv'],
            'difficulty' => ['nullable', 'string', 'max:60'],
            'pattern_type' => ['nullable', 'string', 'max:60'],
            'default_weight' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'use_weighted_selection' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $library = $this->trendDeck->importUploadedLibrary(
            $request->file('trend_file'),
            [
                'name' => $data['name'],
                'game_mode' => $data['game_mode'],
                'difficulty' => $data['difficulty'] ?? null,
                'pattern_type' => $data['pattern_type'] ?? null,
                'default_weight' => $data['default_weight'] ?? 1,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'use_weighted_selection' => $request->boolean('use_weighted_selection'),
                'is_active' => $request->boolean('is_active', true),
            ],
            $request->user()
        );

        $this->adminLog->log(
            $request->user(),
            'prediction_trend_library_uploaded',
            $library,
            'Prediction trend library uploaded',
            [
                'game_mode' => $library->game_mode,
                'trend_count' => $library->trend_count,
                'source_name' => $library->source_name,
            ]
        );

        return back()->with('success', 'Trend library uploaded successfully.');
    }

    public function activate(Request $request, PredictionTrendLibrary $library): RedirectResponse
    {
        $this->trendDeck->activateLibrary($library);

        $this->adminLog->log(
            $request->user(),
            'prediction_trend_library_activated',
            $library,
            'Prediction trend library activated',
            [
                'game_mode' => $library->game_mode,
                'trend_count' => $library->trend_count,
            ]
        );

        return back()->with('success', 'Trend library activated.');
    }
}

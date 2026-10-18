@extends('layouts.admin')

@section('title', 'Prediction Trend Libraries - Admin')
@section('page_title', 'Prediction Trend Libraries')

@push('styles')
<style>
.prediction-trends-page {
    max-width: 100%;
    min-width: 0;
    overflow-x: hidden;
}
.prediction-trends-page .settings-panel,
.prediction-trends-page .settings-section-card,
.prediction-trends-page .admin-settings-form {
    max-width: 100%;
    min-width: 0;
    overflow: hidden;
}
.prediction-trends-page .settings-section-card .table-wrap {
    overflow-x: auto;
}
.prediction-trends-page .dash-field input[type="text"],
.prediction-trends-page .dash-field input[type="number"],
.prediction-trends-page .dash-field input[type="datetime-local"],
.prediction-trends-page .dash-field input[type="file"] {
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
}
.prediction-trends-page .path-input {
    font-size: 0.78rem;
    word-break: break-all;
}
.prediction-trends-page .settings-save-bar {
    flex-wrap: wrap;
    gap: 0.75rem;
}
.prediction-trends-page .settings-save-hint {
    flex: 1 1 220px;
    min-width: 0;
}
.prediction-trends-page .settings-save-bar .btn {
    flex: 0 0 auto;
}
.prediction-trends-page .table-wrap {
    max-width: 100%;
}
.prediction-trends-page .dash-table {
    min-width: 760px;
}
.prediction-trends-page .lib-name {
    display: block;
    font-weight: 700;
}
.prediction-trends-page .lib-source {
    display: block;
    color: #94a3b8;
    font-size: 0.72rem;
    word-break: break-all;
}
.prediction-trends-page .lib-actions {
    white-space: nowrap;
}
@media (max-width: 1024px) {
    .prediction-trends-page .admin-settings-grid-3 {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 767px) {
    .prediction-trends-page .admin-settings-grid,
    .prediction-trends-page .admin-settings-grid-3 {
        grid-template-columns: 1fr;
    }
    .prediction-trends-page .settings-save-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .prediction-trends-page .settings-save-bar .btn {
        width: 100%;
    }
    .prediction-trends-page .dash-table {
        min-width: 0;
    }
}
</style>
@endpush

@section('content')
<div class="dash-page settings-page prediction-trends-page">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <div class="panel settings-panel">
        <div class="settings-tab-intro">
            <div>
                <h3>Prediction Trend Engine</h3>
                <p class="panel-sub">Upload, schedule, and activate predefined trend libraries for Prediction Duel without touching source code.</p>
            </div>
        </div>

        <div class="settings-section-card" style="margin-bottom: 1.25rem;">
            <div class="settings-section-head">
                <span class="settings-section-icon settings-section-icon--blue" aria-hidden="true">
                    <i class="fa-solid fa-wave-square"></i>
                </span>
                <div>
                    <h4>Active Library</h4>
                    <p>Current live source for Prediction Duel outcomes.</p>
                </div>
            </div>

            @if ($activeLibrary)
                <div class="admin-settings-grid admin-settings-grid-3">
                    <div class="dash-field">
                        <label>Name</label>
                        <input type="text" value="{{ $activeLibrary['name'] }}" readonly>
                    </div>
                    <div class="dash-field">
                        <label>Trend Count</label>
                        <input type="text" value="{{ number_format($activeLibrary['trend_count']) }}" readonly>
                    </div>
                    <div class="dash-field">
                        <label>Outcomes Per Trend</label>
                        <input type="text" value="{{ $activeLibrary['outcomes_per_trend'] }}" readonly>
                    </div>
                </div>
            @else
                <p class="panel-sub">No active trend library yet. Upload one below or make sure the default file path exists.</p>
            @endif

            <div class="dash-field" style="margin-top: 1rem;">
                <label>Default Auto-load Source Path</label>
                <input type="text" class="path-input" value="{{ $defaultSourcePath }}" readonly>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.prediction-trends.store') }}" enctype="multipart/form-data" class="dash-form admin-settings-form">
            @csrf
            <div class="settings-section-card">
                <div class="settings-section-head">
                    <span class="settings-section-icon settings-section-icon--gold" aria-hidden="true">
                        <i class="fa-solid fa-upload"></i>
                    </span>
                    <div>
                        <h4>Upload New Library</h4>
                        <p>TXT: one 20-digit trend per line. CSV: add optional difficulty, pattern_type, and weight columns.</p>
                    </div>
                </div>

                <div class="admin-settings-grid admin-settings-grid-3">
                    <div class="dash-field">
                        <label for="name">Library Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', 'Prediction Duel Library') }}" required>
                        @error('name')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dash-field">
                        <label for="game_mode">Game Mode</label>
                        <input type="text" id="game_mode" name="game_mode" value="{{ old('game_mode', 'prediction_duel') }}" required>
                        @error('game_mode')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dash-field">
                        <label for="trend_file">Trend File (.txt or .csv)</label>
                        <input type="file" id="trend_file" name="trend_file" accept=".txt,.csv" required>
                        @error('trend_file')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="dash-field">
                        <label for="difficulty">Default Difficulty</label>
                        <input type="text" id="difficulty" name="difficulty" value="{{ old('difficulty', 'standard') }}" placeholder="easy / standard / hard">
                    </div>
                    <div class="dash-field">
                        <label for="pattern_type">Default Pattern Type</label>
                        <input type="text" id="pattern_type" name="pattern_type" value="{{ old('pattern_type', 'balanced') }}" placeholder="balanced / streak / alternating">
                    </div>
                    <div class="dash-field">
                        <label for="default_weight">Default Weight</label>
                        <input type="number" min="1" id="default_weight" name="default_weight" value="{{ old('default_weight', 1) }}">
                    </div>
                    <div class="dash-field">
                        <label for="starts_at">Starts At</label>
                        <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at') }}">
                    </div>
                    <div class="dash-field">
                        <label for="ends_at">Ends At</label>
                        <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at') }}">
                    </div>
                    <div class="settings-toggle-list" style="align-self: end; gap: .75rem;">
                        <label class="checkbox-label">
                            <input type="checkbox" name="use_weighted_selection" value="1" {{ old('use_weighted_selection') ? 'checked' : '' }}>
                            <span class="checkbox-mark"></span>
                            <span class="checkbox-text"><strong>Enable weighted selection</strong></span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <span class="checkbox-mark"></span>
                            <span class="checkbox-text"><strong>Activate immediately</strong></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="settings-save-bar">
                <p class="settings-save-hint">Uploading an active library switches the engine to the new trend source instantly.</p>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Upload Library
                </button>
            </div>
        </form>

        <div class="settings-section-card" style="margin-top: 1.25rem;">
            <div class="settings-section-head">
                <span class="settings-section-icon settings-section-icon--green" aria-hidden="true">
                    <i class="fa-solid fa-database"></i>
                </span>
                <div>
                    <h4>Available Libraries</h4>
                    <p>Activate a scheduled or uploaded trend library at any time.</p>
                </div>
            </div>

            <div class="table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Mode</th>
                            <th>Trends</th>
                            <th>Difficulty</th>
                            <th>Pattern</th>
                            <th>Selection</th>
                            <th>Window</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($libraries as $library)
                            <tr>
                                <td data-label="Name">
                                    <span class="lib-name">{{ $library->name }}</span>
                                    <span class="lib-source">{{ $library->source_name }}</span>
                                </td>
                                <td data-label="Mode">{{ $library->game_mode }}</td>
                                <td data-label="Trends">{{ number_format($library->trend_count ?: $library->trends_count) }} x {{ $library->outcomes_per_trend }}</td>
                                <td data-label="Difficulty">{{ $library->difficulty ?: '-' }}</td>
                                <td data-label="Pattern">{{ $library->pattern_type ?: '-' }}</td>
                                <td data-label="Selection">{{ $library->use_weighted_selection ? 'Weighted' : 'Uniform' }}</td>
                                <td data-label="Window">
                                    {{ $library->starts_at?->format('d M Y H:i') ?: 'Now' }}
                                    <br>
                                    <small>to {{ $library->ends_at?->format('d M Y H:i') ?: 'indefinite' }}</small>
                                </td>
                                <td data-label="Status">
                                    @if ($library->is_active)
                                        <span class="status-badge success">Live</span>
                                    @else
                                        <span class="status-badge warning">Inactive</span>
                                    @endif
                                </td>
                                <td data-label="Action" class="lib-actions">
                                    @if (! $library->is_active)
                                        <form method="POST" action="{{ route('admin.prediction-trends.activate', $library) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm">Activate</button>
                                        </form>
                                    @else
                                        <span class="status-badge success">Active</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="empty-cell" data-label="">No trend libraries uploaded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

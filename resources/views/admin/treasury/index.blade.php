@extends('layouts.admin')

@section('title', 'Treasury — Admin')
@section('page_title', 'Treasury Liquidity')

@section('content')
<div class="dash-page admin-treasury-page">
    <div class="stats-cards admin-stats-primary admin-stats-compact">
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon gold" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 21h18"/>
                    <path d="M5 21V10l7-5 7 5v11"/>
                    <path d="M9 21v-6h6v6"/>
                    <path d="M10 10h4"/>
                </svg>
            </div>
            <div class="stat-card-body">
                <span class="stat-card-label">Treasury Balance</span>
                <span class="stat-card-value">{{ number_format($stats['balance'], 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon green" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3c4.5 4.2 7 7.4 7 10.5A7 7 0 0 1 5 13.5C5 10.4 7.5 7.2 12 3Z"/>
                    <path d="M12 16.5v.01"/>
                </svg>
            </div>
            <div class="stat-card-body">
                <span class="stat-card-label">Available Liquidity</span>
                <span class="stat-card-value">{{ number_format($stats['available_liquidity'], 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon orange" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="10" rx="2"/>
                    <path d="M7 11V8a5 5 0 0 1 10 0v3"/>
                    <circle cx="12" cy="16" r="1.5"/>
                </svg>
            </div>
            <div class="stat-card-body">
                <span class="stat-card-label">Reserved Balance</span>
                <span class="stat-card-value">{{ number_format($treasury?->reserved_balance ?? 0, 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon blue" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </div>
            <div class="stat-card-body">
                <span class="stat-card-label">Treasury Health</span>
                <span class="stat-card-value admin-treasury-health admin-treasury-health--{{ strtolower($stats['health']) }}">{{ $stats['health'] }}</span>
            </div>
        </div>
    </div>

    <div class="panel admin-treasury-panel">
        <div class="panel-header"><h3>Manage Treasury Liquidity</h3></div>
        <form method="POST" action="{{ route('admin.treasury.update') }}" class="dash-form admin-settings-form admin-treasury-form">
            @csrf
            @method('PUT')
            <div class="admin-settings-grid">
                <div class="dash-field">
                    <label for="balance">Treasury Balance (USDT)</label>
                    <input type="number" step="0.01" min="0" id="balance" name="balance" value="{{ old('balance', $treasury?->balance ?? 0) }}" required>
                    @error('balance')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="dash-field">
                    <label for="reserved_balance">Reserved Balance (USDT)</label>
                    <input type="number" step="0.01" min="0" id="reserved_balance" name="reserved_balance" value="{{ old('reserved_balance', $treasury?->reserved_balance ?? 0) }}" required>
                    @error('reserved_balance')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <button type="submit" class="btn btn-primary admin-treasury-submit">Update Treasury</button>
        </form>
    </div>
</div>
@endsection

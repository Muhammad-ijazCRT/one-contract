@extends('layouts.admin')

@section('title', 'Investment Schemes — Admin')
@section('page_title', 'Investment Schemes')

@section('content')
@php
    $iconMap = [
        'binary' => 'fa-solid fa-code',
        'crypto' => 'fa-brands fa-bitcoin',
        'forex' => 'fa-solid fa-chart-line',
        'casino' => 'fa-solid fa-dice',
        'polymarket' => 'fa-solid fa-scale-balanced',
    ];
@endphp
<div class="dash-page admin-clubs-page">
    <div class="stats-cards admin-stats-primary admin-stats-compact">
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon gold"><i class="fa-solid fa-layer-group"></i></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Active Schemes</span>
                <span class="stat-card-value">{{ $clubs->where('is_active', true)->count() }}</span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon green"><i class="fa-solid fa-coins"></i></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Total Active Capital</span>
                <span class="stat-card-value">{{ number_format($totalActiveCapital, 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon blue"><i class="fa-solid fa-user-group"></i></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Active Investors</span>
                <span class="stat-card-value">{{ number_format($totalInvestors) }}</span>
            </div>
        </div>
    </div>

    <div class="panel admin-clubs-panel">
        <div class="panel-header">
            <div>
                <h3>Scheme Settings</h3>
                <p class="panel-sub admin-clubs-sub">Update reward %, cycle, minimums, and visibility for each investment scheme.</p>
            </div>
        </div>

        <div class="admin-club-grid">
            @foreach ($clubs as $club)
                @php
                    $slug = $club->slug instanceof \BackedEnum ? $club->slug->value : (string) $club->slug;
                    $icon = $iconMap[$slug] ?? 'fa-solid fa-chart-pie';
                    $isEdited = (string) old('_club_id') === (string) $club->id;
                    $reward = $isEdited ? old('reward_percent', $club->reward_percent) : $club->reward_percent;
                    $minInvest = $isEdited ? old('min_investment', $club->min_investment) : $club->min_investment;
                    $maxInvest = $isEdited ? old('max_investment', $club->max_investment ?? 450) : ($club->max_investment ?? 450);
                    $nameValue = $isEdited ? old('name', $club->name) : $club->name;
                    $cycleValue = $isEdited ? old('cycle_days', $club->cycle_days) : $club->cycle_days;
                    $sortValue = $isEdited ? old('sort_order', $club->sort_order) : $club->sort_order;
                    $activeValue = $isEdited ? old('is_active', $club->is_active) : $club->is_active;
                @endphp
                <form method="POST" action="{{ route('admin.clubs.update', $club) }}" class="admin-club-card dash-form {{ $isEdited && $errors->any() ? 'admin-club-card--error' : '' }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_club_id" value="{{ $club->id }}">

                    <div class="admin-club-card-head">
                        <div class="admin-club-identity">
                            <span class="admin-club-icon"><i class="{{ $icon }}" aria-hidden="true"></i></span>
                            <div class="admin-club-titles">
                                <strong>{{ $club->name }}</strong>
                                <span class="admin-club-slug">{{ $slug }}</span>
                            </div>
                        </div>
                        <label class="admin-toggle">
                            <input type="checkbox" name="is_active" value="1" {{ $activeValue ? 'checked' : '' }}>
                            <span class="admin-toggle-track" aria-hidden="true"></span>
                            <span class="admin-toggle-text">Active</span>
                        </label>
                    </div>

                    <div class="admin-club-fields">
                        <div class="dash-field admin-club-field-full">
                            <label for="name-{{ $club->id }}">Display name</label>
                            <input id="name-{{ $club->id }}" type="text" name="name" value="{{ $nameValue }}" required autocomplete="off">
                        </div>
                        <div class="dash-field">
                            <label for="reward-{{ $club->id }}">Daily reward %</label>
                            <input id="reward-{{ $club->id }}" type="number" step="0.01" min="0" max="100" name="reward_percent" value="{{ is_numeric($reward) ? number_format((float) $reward, 2, '.', '') : $reward }}" required>
                        </div>
                        <div class="dash-field">
                            <label for="cycle-{{ $club->id }}">Cycle days</label>
                            <input id="cycle-{{ $club->id }}" type="number" min="1" max="365" name="cycle_days" value="{{ $cycleValue }}" required>
                        </div>
                        <div class="dash-field">
                            <label for="min-{{ $club->id }}">Min investment</label>
                            <div class="admin-input-affix">
                                <span>$</span>
                                <input id="min-{{ $club->id }}" type="number" step="0.01" min="0.01" name="min_investment" value="{{ is_numeric($minInvest) ? number_format((float) $minInvest, 2, '.', '') : $minInvest }}" required>
                            </div>
                        </div>
                        <div class="dash-field {{ $isEdited && $errors->has('max_investment') ? 'has-error' : '' }}">
                            <label for="max-{{ $club->id }}">Max investment</label>
                            <div class="admin-input-affix">
                                <span>$</span>
                                <input id="max-{{ $club->id }}" type="number" step="0.01" min="0.01" name="max_investment" value="{{ is_numeric($maxInvest) ? number_format((float) $maxInvest, 2, '.', '') : $maxInvest }}" required>
                            </div>
                            @if ($isEdited && $errors->has('max_investment'))
                                <p class="admin-field-error">{{ $errors->first('max_investment') }}</p>
                            @endif
                        </div>
                        <div class="dash-field">
                            <label for="sort-{{ $club->id }}">Sort order</label>
                            <input id="sort-{{ $club->id }}" type="number" min="0" max="100" name="sort_order" value="{{ $sortValue }}" required>
                        </div>
                    </div>

                    <div class="admin-club-card-meta">
                        <div>
                            <span>Capital</span>
                            <strong>{{ number_format((float) $club->active_capital, 2) }} USDT</strong>
                        </div>
                        <div>
                            <span>Investors</span>
                            <strong>{{ number_format((int) $club->investor_count) }}</strong>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary admin-club-save">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        Save Scheme
                    </button>
                </form>
            @endforeach
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>User Investments</h3>
            <form method="GET" action="{{ route('admin.clubs.index') }}" class="admin-search-form">
                <input type="search" name="q" value="{{ $search }}" placeholder="Search user, scheme, amount...">
                <button type="submit" class="btn btn-outline-dark btn-sm">Search</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Scheme</th>
                        <th>Amount</th>
                        <th>Rewards Earned</th>
                        <th>Next Reward</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($investments as $investment)
                        <tr>
                            <td data-label="User">
                                <strong>{{ $investment->user?->name }}</strong>
                                <span class="cell-sub">{{ $investment->user?->member_id }}</span>
                            </td>
                            <td data-label="Scheme">{{ $investment->club?->name }}</td>
                            <td data-label="Amount"><strong>{{ number_format((float) $investment->amount, 2) }}</strong> USDT</td>
                            <td data-label="Rewards Earned">{{ number_format((float) $investment->total_rewards_earned, 2) }} USDT</td>
                            <td data-label="Next Reward">{{ $investment->next_reward_at?->format('M d, Y H:i') ?? '—' }}</td>
                            <td data-label="Status">
                                <span class="admin-status-pill {{ $investment->status->value === 'active' ? 'is-active' : ($investment->status->value === 'closed' ? 'is-closed' : '') }}">
                                    {{ ucfirst($investment->status->value) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-cell">No scheme investments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $investments->links() }}
    </div>
</div>
@endsection

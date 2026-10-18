@extends('layouts.admin')

@section('title', 'Savings Plans — Admin')
@section('page_title', 'Savings Plans')

@section('content')
<div class="dash-page">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="dash-alert dash-alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="stats-cards admin-stats-primary admin-stats-compact">
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon gold"><i class="fa-solid fa-piggy-bank"></i></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Active Plans</span>
                <span class="stat-card-value">{{ $counts['active'] }}</span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon green"><i class="fa-solid fa-wallet"></i></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Saved (Active/Pending)</span>
                <span class="stat-card-value">{{ number_format($activeSaved, 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon orange"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Pending Early Returns</span>
                <span class="stat-card-value">{{ $counts['pending_early_return'] }}</span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon blue"><i class="fa-solid fa-flag-checkered"></i></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Completed (2X)</span>
                <span class="stat-card-value">{{ $counts['completed'] }}</span>
            </div>
        </div>
    </div>

    <div class="admin-filter-stats">
        <a href="{{ route('admin.savings.index') }}" class="admin-filter-pill {{ ! $statusFilter ? 'active' : '' }}">
            All <span>{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.savings.index', ['status' => 'active']) }}" class="admin-filter-pill {{ $statusFilter === 'active' ? 'active' : '' }}">
            Active <span>{{ $counts['active'] }}</span>
        </a>
        <a href="{{ route('admin.savings.index', ['status' => 'pending_early_return']) }}" class="admin-filter-pill {{ $statusFilter === 'pending_early_return' ? 'active' : '' }}">
            Early Pending <span class="pill-warn">{{ $counts['pending_early_return'] }}</span>
        </a>
        <a href="{{ route('admin.savings.index', ['status' => 'completed']) }}" class="admin-filter-pill {{ $statusFilter === 'completed' ? 'active' : '' }}">
            Completed <span>{{ $counts['completed'] }}</span>
        </a>
        <a href="{{ route('admin.savings.index', ['status' => 'withdrawn_early']) }}" class="admin-filter-pill {{ $statusFilter === 'withdrawn_early' ? 'active' : '' }}">
            Withdrawn Early <span>{{ $counts['withdrawn_early'] }}</span>
        </a>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>Savings Plans</h3>
            <form method="GET" action="{{ route('admin.savings.index') }}" class="admin-search-form">
                @if ($statusFilter)
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                <input type="search" name="q" value="{{ $search }}" placeholder="Search user, member ID, amount...">
                <button type="submit" class="btn btn-outline-dark btn-sm">Search</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Daily</th>
                        <th>Days</th>
                        <th>Total Saved</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td data-label="User">
                                <strong>{{ $plan->user?->name }}</strong>
                                <span class="cell-sub">{{ $plan->user?->member_id }}</span>
                            </td>
                            <td data-label="Daily">${{ number_format((float) $plan->daily_amount, 2) }}</td>
                            <td data-label="Days">{{ $plan->successful_days }} / {{ $plan->target_days }}</td>
                            <td data-label="Total Saved"><strong>{{ number_format((float) $plan->total_saved, 2) }}</strong> USDT</td>
                            <td data-label="Status">{{ str_replace('_', ' ', ucfirst($plan->status->value)) }}</td>
                            <td data-label="Started">{{ $plan->started_at?->format('M d, Y') ?? '—' }}</td>
                            <td data-label="Actions" class="table-actions">
                                @if ($plan->status->value === 'pending_early_return')
                                    <form method="POST" action="{{ route('admin.savings.complete-early', $plan) }}" class="inline-form" data-confirm="Credit {{ number_format((float) $plan->total_saved, 2) }} USDT early savings return to {{ $plan->user?->name }} now?" data-confirm-ok="Yes, Credit">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">Complete Early Return</button>
                                    </form>
                                    @if ($plan->early_withdrawal_available_at)
                                        <span class="cell-sub">Due {{ $plan->early_withdrawal_available_at->format('M d, H:i') }}</span>
                                    @endif
                                @elseif ($plan->status->value === 'completed')
                                    <span class="cell-sub">{{ $plan->completed_at?->format('M d, Y') ?? 'Done' }}</span>
                                @else
                                    <span class="cell-sub">{{ $plan->remainingDays() }} days left</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-cell">No savings plans yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $plans->links() }}
    </div>
</div>
@endsection

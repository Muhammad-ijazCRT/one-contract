@extends('layouts.admin')

@section('title', 'Capital Returns — Admin')
@section('page_title', 'Scheme Capital Returns')

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
            <div class="stat-card-icon orange"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Pending Amount</span>
                <span class="stat-card-value">{{ number_format($pendingAmount, 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon blue"><i class="fa-solid fa-list"></i></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Pending Requests</span>
                <span class="stat-card-value">{{ $counts['pending'] }}</span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon green"><i class="fa-solid fa-check"></i></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Completed</span>
                <span class="stat-card-value">{{ $counts['completed'] }}</span>
            </div>
        </div>
    </div>

    <div class="admin-filter-stats">
        <a href="{{ route('admin.capital-requests.index') }}" class="admin-filter-pill {{ ! $statusFilter ? 'active' : '' }}">
            All <span>{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.capital-requests.index', ['status' => 'pending']) }}" class="admin-filter-pill {{ $statusFilter === 'pending' ? 'active' : '' }}">
            Pending <span class="pill-warn">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route('admin.capital-requests.index', ['status' => 'completed']) }}" class="admin-filter-pill {{ $statusFilter === 'completed' ? 'active' : '' }}">
            Completed <span>{{ $counts['completed'] }}</span>
        </a>
        <a href="{{ route('admin.capital-requests.index', ['status' => 'cancelled']) }}" class="admin-filter-pill {{ $statusFilter === 'cancelled' ? 'active' : '' }}">
            Cancelled <span>{{ $counts['cancelled'] }}</span>
        </a>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>Capital Return Queue</h3>
            <form method="GET" action="{{ route('admin.capital-requests.index') }}" class="admin-search-form">
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
                        <th>Scheme</th>
                        <th>Amount</th>
                        <th>Requested</th>
                        <th>Available At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr>
                            <td data-label="User">
                                <strong>{{ $item->user?->name }}</strong>
                                <span class="cell-sub">{{ $item->user?->member_id }}</span>
                            </td>
                            <td data-label="Scheme">{{ $item->investment?->club?->name ?? '—' }}</td>
                            <td data-label="Amount"><strong>{{ number_format((float) $item->amount, 2) }}</strong> USDT</td>
                            <td data-label="Requested">{{ $item->requested_at?->format('M d, Y H:i') ?? '—' }}</td>
                            <td data-label="Available At">
                                {{ $item->available_at?->format('M d, Y H:i') ?? '—' }}
                                @if ($item->status->value === 'pending' && $item->available_at?->isPast())
                                    <span class="cell-sub text-green">Ready</span>
                                @endif
                            </td>
                            <td data-label="Status">
                                @php
                                    $st = $item->status->value;
                                    $badgeColor = match ($st) {
                                        'completed' => '#10b981', // Green
                                        'pending' => '#f59e0b', // Orange
                                        'cancelled' => '#ef4444', // Red
                                        default => '#64748b',
                                    };
                                    $badgeText = match ($st) {
                                        'completed' => 'Approved',
                                        'pending' => 'Processing',
                                        default => ucfirst($st),
                                    };
                                @endphp
                                <span style="display: inline-flex; align-items: center; justify-content: center; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500; border: 1px solid {{ $badgeColor }}; color: {{ $badgeColor }}; background: transparent;">
                                    {{ $badgeText }}
                                </span>
                            </td>
                            <td data-label="Actions" class="table-actions">
                                @if ($item->status->value === 'pending')
                                    <form method="POST" action="{{ route('admin.capital-requests.complete', $item) }}" class="inline-form" data-confirm="Credit {{ number_format((float) $item->amount, 2) }} USDT to {{ $item->user?->name }} Main Wallet now?" data-confirm-ok="Yes, Credit">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">Complete Now</button>
                                    </form>
                                @else
                                    <span class="cell-sub">{{ $item->completed_at?->format('M d, Y H:i') ?? '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-cell">No capital return requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
</div>
@endsection

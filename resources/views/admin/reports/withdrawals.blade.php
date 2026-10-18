@extends('layouts.admin')

@section('title', 'Withdrawal Report — Admin')
@section('page_title', 'Withdrawal Report')

@section('content')
<div class="dash-page">
    <div class="stats-cards admin-stats-primary admin-stats-compact">
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Total Completed</span>
                <span class="stat-card-value">{{ number_format($totalCompleted, 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon gold"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Reward Withdrawals</span>
                <span class="stat-card-value">{{ number_format($totalReward, 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/></svg></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Capital Withdrawals</span>
                <span class="stat-card-value">{{ number_format($totalCapital, 2) }} <small>USDT</small></span>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3>All Withdrawal Requests</h3></div>
        <div class="table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($withdrawals as $item)
                        <tr>
                            <td data-label="User">{{ $item->user?->name ?? '—' }}</td>
                            <td data-label="Type">{{ ucfirst($item->type->value) }}</td>
                            <td data-label="Amount">{{ number_format($item->amount, 2) }} USDT</td>
                            <td data-label="Status">@include('user.partials.tx-status', ['status' => $item->status->value])</td>
                            <td data-label="Date">{{ $item->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-cell">No withdrawals recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $withdrawals->links() }}
    </div>
</div>
@endsection

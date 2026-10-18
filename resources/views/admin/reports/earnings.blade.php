@extends('layouts.admin')

@section('title', 'Earnings Report — Admin')
@section('page_title', 'Earnings Report')

@section('content')
<div class="dash-page">
    <div class="stats-cards admin-stats-primary admin-stats-compact">
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 16l4-8 4 4 6-10"/></svg></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Daily Rewards Paid</span>
                <span class="stat-card-value">{{ number_format($totalDaily, 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Referral Commissions</span>
                <span class="stat-card-value">{{ number_format($totalReferral, 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon gold"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Total Earnings Paid</span>
                <span class="stat-card-value">{{ number_format($total, 2) }} <small>USDT</small></span>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3>All Reward Credits</h3></div>
        <div class="table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rewards as $reward)
                        <tr>
                            <td data-label="User">{{ $reward->user?->name ?? '—' }}</td>
                            <td data-label="Type">{{ ucwords(str_replace('_', ' ', $reward->type->value)) }}</td>
                            <td data-label="Amount">{{ number_format($reward->amount, 2) }} USDT</td>
                            <td data-label="Date">{{ $reward->credited_at?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-cell">No earnings recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rewards->links() }}
    </div>
</div>
@endsection

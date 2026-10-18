@extends('layouts.admin')

@section('title', 'Rewards — Admin')
@section('page_title', 'Daily Rewards')

@section('content')
<div class="dash-page">
    <div class="stats-cards admin-stats-primary admin-stats-compact">
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon gold"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 16l4-8 4 4 6-10"/></svg></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Daily Reward Rate</span>
                <span class="stat-card-value">{{ $dailyRate }}%</span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Total Daily Rewards Paid</span>
                <span class="stat-card-value">{{ number_format($totalDailyPaid, 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Total Referral Commissions</span>
                <span class="stat-card-value">{{ number_format($totalReferralPaid, 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon gold"><i class="fa-solid fa-chart-pie"></i></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Total Club Rewards</span>
                <span class="stat-card-value">{{ number_format($totalClubPaid, 2) }} <small>USDT</small></span>
            </div>
        </div>
        <div class="stat-card admin-stat-card">
            <div class="stat-card-icon orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg></div>
            <div class="stat-card-body">
                <span class="stat-card-label">Daily Owed (Today)</span>
                <span class="stat-card-value">{{ number_format($dailyOwedToday, 2) }} <small>USDT</small></span>
            </div>
        </div>
    </div>

    <div class="admin-filter-stats">
        <a href="{{ route('admin.rewards.index') }}" class="admin-filter-pill {{ ! $typeFilter ? 'active' : '' }}">All</a>
        <a href="{{ route('admin.rewards.index', ['type' => 'club_reward']) }}" class="admin-filter-pill {{ $typeFilter === 'club_reward' ? 'active' : '' }}">Club</a>
        <a href="{{ route('admin.rewards.index', ['type' => 'referral_commission']) }}" class="admin-filter-pill {{ $typeFilter === 'referral_commission' ? 'active' : '' }}">Referral</a>
        <a href="{{ route('admin.rewards.index', ['type' => 'daily_reward']) }}" class="admin-filter-pill {{ $typeFilter === 'daily_reward' ? 'active' : '' }}">Legacy Daily</a>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>Reward Credits</h3>
            <form method="GET" action="{{ route('admin.rewards.index') }}" class="admin-search-form">
                @if ($typeFilter)
                    <input type="hidden" name="type" value="{{ $typeFilter }}">
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
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Level</th>
                        <th>Credited</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rewards as $reward)
                        <tr>
                            <td data-label="User">
                                <strong>{{ $reward->user?->name }}</strong>
                                <span class="cell-sub">{{ $reward->user?->member_id }}</span>
                            </td>
                            <td data-label="Type">{{ ucwords(str_replace('_', ' ', $reward->type->value)) }}</td>
                            <td data-label="Amount"><strong>{{ number_format($reward->amount, 2) }}</strong> USDT</td>
                            <td data-label="Level">{{ $reward->referral_level ? 'L'.$reward->referral_level : '—' }}</td>
                            <td data-label="Credited">{{ $reward->credited_at?->format('M d, Y h:i A') ?? '—' }}</td>
                            <td data-label="Actions" class="table-actions">
                                <button type="button" class="btn btn-outline-dark btn-sm" data-detail-modal="{{ route('admin.rewards.show', $reward) }}">View</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-cell">No rewards credited yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rewards->links() }}
    </div>
</div>
@endsection

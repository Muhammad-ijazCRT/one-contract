@extends('layouts.admin')

@section('title', 'Transactions — Admin')
@section('page_title', 'Transaction History')

@section('content')
<div class="dash-page">
    <div class="admin-filter-stats admin-filter-wrap">
        <a href="{{ route('admin.transactions.index') }}" class="admin-filter-pill {{ ! $typeFilter ? 'active' : '' }}">
            All <span>{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.transactions.index', ['type' => 'deposit']) }}" class="admin-filter-pill {{ $typeFilter === 'deposit' ? 'active' : '' }}">
            Deposits <span>{{ $counts['deposit'] }}</span>
        </a>
        <a href="{{ route('admin.transactions.index', ['type' => 'daily_reward']) }}" class="admin-filter-pill {{ $typeFilter === 'daily_reward' ? 'active' : '' }}">
            Daily Rewards <span>{{ $counts['daily_reward'] }}</span>
        </a>
        <a href="{{ route('admin.transactions.index', ['type' => 'referral_commission']) }}" class="admin-filter-pill {{ $typeFilter === 'referral_commission' ? 'active' : '' }}">
            Referrals <span>{{ $counts['referral_commission'] }}</span>
        </a>
        <a href="{{ route('admin.transactions.index', ['type' => 'reward_withdrawal']) }}" class="admin-filter-pill {{ $typeFilter === 'reward_withdrawal' ? 'active' : '' }}">
            Reward WD <span>{{ $counts['reward_withdrawal'] }}</span>
        </a>
        <a href="{{ route('admin.transactions.index', ['type' => 'capital_withdrawal']) }}" class="admin-filter-pill {{ $typeFilter === 'capital_withdrawal' ? 'active' : '' }}">
            Capital WD <span>{{ $counts['capital_withdrawal'] }}</span>
        </a>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>All Transactions</h3>
            <form method="GET" action="{{ route('admin.transactions.index') }}" class="admin-search-form">
                @if ($typeFilter)
                    <input type="hidden" name="type" value="{{ $typeFilter }}">
                @endif
                <input type="search" name="q" value="{{ $search ?? '' }}" placeholder="Search user, description, amount...">
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
                        <th>Description</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr>
                            <td data-label="User">
                                <strong>{{ $tx->user?->name ?? '—' }}</strong>
                                <span class="cell-sub">{{ $tx->user?->member_id }}</span>
                            </td>
                            <td data-label="Type">{{ ucwords(str_replace('_', ' ', $tx->type->value)) }}</td>
                            <td data-label="Amount"><strong>{{ number_format($tx->amount, 2) }}</strong> USDT</td>
                            <td data-label="Description">{{ $tx->description ?? '—' }}</td>
                            <td data-label="Status">@include('user.partials.tx-status', ['status' => $tx->status ?? 'completed'])</td>
                            <td data-label="Date">{{ $tx->created_at->format('M d, Y h:i A') }}</td>
                            <td data-label="Actions" class="table-actions">
                                <button type="button" class="btn btn-outline-dark btn-sm action-btn action-btn--view" data-detail-modal="{{ route('admin.transactions.show', $tx) }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-cell">No transactions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $transactions->links() }}
    </div>
</div>
@endsection

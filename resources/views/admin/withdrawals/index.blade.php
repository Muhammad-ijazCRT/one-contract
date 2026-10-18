@extends('layouts.admin')

@section('title', ($pageTitle ?? 'Withdrawals').' — Admin')
@section('page_title', $pageTitle ?? 'Withdrawals')

@section('content')
@php
    $filterParams = $typeFilter ? ['type' => $typeFilter] : [];
    $currentRoute = request()->routeIs('admin.withdrawals.reward')
        ? 'admin.withdrawals.reward'
        : (request()->routeIs('admin.withdrawals.capital')
            ? 'admin.withdrawals.capital'
            : 'admin.withdrawals.index');
@endphp
<div class="dash-page admin-withdrawals-page">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <div class="admin-filter-stats">
        <a href="{{ route($currentRoute, $filterParams) }}" class="admin-filter-pill {{ ! $statusFilter ? 'active' : '' }}">
            All <span>{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route($currentRoute, array_merge($filterParams, ['status' => 'pending'])) }}" class="admin-filter-pill {{ $statusFilter === 'pending' ? 'active' : '' }}">
            Pending <span class="pill-warn">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route($currentRoute, array_merge($filterParams, ['status' => 'completed'])) }}" class="admin-filter-pill {{ $statusFilter === 'completed' ? 'active' : '' }}">
            Completed <span>{{ $counts['completed'] }}</span>
        </a>
        <a href="{{ route($currentRoute, array_merge($filterParams, ['status' => 'rejected'])) }}" class="admin-filter-pill {{ $statusFilter === 'rejected' ? 'active' : '' }}">
            Rejected <span>{{ $counts['rejected'] }}</span>
        </a>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>
                @if ($typeFilter === 'reward')
                    Main Wallet Requests
                @elseif ($typeFilter === 'capital')
                    Capital Withdrawal Requests
                @else
                    Withdrawal Requests
                @endif
            </h3>
            <form method="GET" action="{{ route($currentRoute) }}" class="admin-search-form">
                @if ($typeFilter)
                    <input type="hidden" name="type" value="{{ $typeFilter }}">
                @endif
                @if ($statusFilter)
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                <input type="search" name="q" value="{{ $search }}" placeholder="Search user, email, member ID, wallet...">
                <button type="submit" class="btn btn-outline-dark btn-sm">Search</button>
            </form>
        </div>
        <div class="table-wrap admin-wd-table-wrap">
            <table class="dash-table admin-wd-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Fee</th>
                        <th>Payout</th>
                        <th>Network</th>
                        <th>Wallet</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($withdrawals as $item)
                        <tr class="{{ in_array($item->status->value, ['pending', 'approved']) ? 'row-pending' : '' }}">
                            <td data-label="User">
                                <strong>{{ $item->user?->name }}</strong>
                                <span class="cell-sub">{{ $item->user?->member_id }}</span>
                            </td>
                            <td data-label="Type">
                                <span class="badge-status {{ $item->type->value === 'reward' ? 'active' : 'pending' }}">
                                    {{ $item->type->label() }}
                                </span>
                            </td>
                            <td data-label="Amount"><strong>{{ number_format($item->amount, 2) }}</strong> USDT</td>
                            <td data-label="Fee">{{ number_format($item->fee, 2) }} USDT</td>
                            <td data-label="Payout"><strong>{{ number_format($item->netAmount(), 2) }}</strong> USDT</td>
                            <td data-label="Network">{{ $item->network ?? '—' }}</td>
                            <td data-label="Wallet">
                                <button type="button" class="tx-hash tx-hash--clickable" data-copy-modal="{{ $item->wallet_address }}" data-copy-modal-label="Wallet Address ({{ $item->network ?? 'Network N/A' }})">
                                    {{ Str::limit($item->wallet_address, 18) }}
                                </button>
                            </td>
                            <td data-label="Status">@include('user.partials.tx-status', ['status' => $item->status->value])</td>
                            <td data-label="Date">
                                {{ $item->created_at->format('M d, Y h:i A') }}
                                @if ($item->processed_at)
                                    <span class="cell-sub">Processed: {{ $item->processed_at->format('M d, Y h:i A') }}</span>
                                @endif
                            </td>
                            <td data-label="Actions" class="table-actions">
                                <button type="button" class="btn btn-outline-dark btn-sm" data-detail-modal="{{ route('admin.withdrawals.show', $item) }}">View</button>
                                @if (in_array($item->status->value, ['pending', 'approved']))
                                    <form method="POST" action="{{ route('admin.withdrawals.complete', $item) }}" class="inline-form" data-confirm="Approve and send {{ number_format($item->netAmount(), 2) }} USDT to the user wallet? (Requested {{ number_format($item->amount, 2) }} USDT, fee {{ number_format($item->fee, 2) }} USDT)" data-confirm-ok="Yes, Approve">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success btn-sm">{{ $item->status->value === 'pending' ? 'Approve' : 'Complete' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.withdrawals.reject', $item) }}" class="inline-form" data-confirm="Reject this withdrawal of {{ number_format($item->amount, 2) }} USDT?" data-confirm-ok="Yes, Reject" data-confirm-variant="danger" data-confirm-textarea="Reason for rejection (optional)">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty-cell">
                                @if ($typeFilter === 'capital')
                                    No capital withdrawal requests.
                                @elseif ($typeFilter === 'reward')
                                    No Main Wallet withdrawal requests.
                                @else
                                    No withdrawal requests.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $withdrawals->links() }}
    </div>
</div>
@endsection

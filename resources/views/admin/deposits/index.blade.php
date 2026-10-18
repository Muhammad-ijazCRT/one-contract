@extends('layouts.admin')

@section('title', 'Deposits â€” Admin')
@section('page_title', 'Deposits')

@section('content')
<div class="dash-page">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <div class="admin-filter-stats">
        <a href="{{ route('admin.deposits.index') }}" class="admin-filter-pill {{ ! $statusFilter ? 'active' : '' }}">
            All <span>{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.deposits.index', ['status' => 'pending']) }}" class="admin-filter-pill {{ $statusFilter === 'pending' ? 'active' : '' }}">
            Pending <span class="pill-warn">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route('admin.deposits.index', ['status' => 'active']) }}" class="admin-filter-pill {{ $statusFilter === 'active' ? 'active' : '' }}">
            Active <span>{{ $counts['active'] }}</span>
        </a>
        <a href="{{ route('admin.deposits.index', ['status' => 'rejected']) }}" class="admin-filter-pill {{ $statusFilter === 'rejected' ? 'active' : '' }}">
            Rejected <span>{{ $counts['rejected'] }}</span>
        </a>
        <a href="{{ route('admin.deposits.index', ['status' => 'withdrawn']) }}" class="admin-filter-pill {{ $statusFilter === 'withdrawn' ? 'active' : '' }}">
            Withdrawn <span>{{ $counts['withdrawn'] }}</span>
        </a>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>Deposit Requests</h3>
            <form method="GET" action="{{ route('admin.deposits.index') }}" class="admin-search-form">
                @if ($statusFilter)
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                <input type="search" name="q" value="{{ $search }}" placeholder="Search user, email, member ID, amount...">
                <button type="submit" class="btn btn-outline-dark btn-sm">Search</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="dash-table admin-deposits-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deposits as $deposit)
                        @php $status = $deposit->status->value; @endphp
                        <tr class="admin-deposit-row admin-deposit-row--{{ $status }}">
                            <td data-label="User">
                                <strong>{{ $deposit->user?->name }}</strong>
                                <span class="cell-sub">{{ $deposit->user?->member_id }}</span>
                            </td>
                            <td data-label="Amount"><strong>{{ number_format($deposit->amount, 2) }}</strong> USDT</td>
                            <td data-label="Status">
                                @include('admin.partials.deposit-status', ['status' => $status])
                            </td>
                            <td data-label="Submitted">
                                <span>{{ $deposit->deposited_at?->format('M d, Y h:i A') ?? $deposit->created_at->format('M d, Y h:i A') }}</span>
                                @if ($status === 'active' && $deposit->approved_at)
                                    <span class="cell-sub text-green">Approved: {{ $deposit->approved_at->format('M d, Y h:i A') }}</span>
                                @elseif ($status === 'rejected' && $deposit->approved_at)
                                    <span class="cell-sub text-orange">Rejected: {{ $deposit->approved_at->format('M d, Y h:i A') }}</span>
                                @endif
                            </td>
                            <td data-label="Actions" class="table-actions">
                                <button type="button" class="btn btn-outline-dark btn-sm action-btn action-btn--view" data-detail-modal="{{ route('admin.deposits.show', $deposit) }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View Details
                                </button>
                                @if ($status === 'pending')
                                    <div class="deposit-action-pending">
                                        <form method="POST" action="{{ route('admin.deposits.approve', $deposit) }}" class="inline-form" data-confirm="Approve {{ number_format($deposit->amount, 2) }} USDT for {{ $deposit->user?->name }}?" data-confirm-ok="Yes, Approve">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success btn-sm">✓ Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.deposits.reject', $deposit) }}" class="inline-form" data-confirm="Reject this deposit of {{ number_format($deposit->amount, 2) }} USDT?" data-confirm-ok="Yes, Reject" data-confirm-variant="danger" data-confirm-textarea="Reason for rejection (optional)">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm">✕ Reject</button>
                                        </form>
                                    </div>
                                @elseif ($status === 'active')
                                    <div class="deposit-action-done">
                                        <span class="action-badge action-badge--success">✓ Approved</span>
                                        @if ($deposit->approver)
                                            <span class="cell-sub">by {{ $deposit->approver->name }}</span>
                                        @endif
                                    </div>
                                @elseif ($status === 'rejected')
                                    <div class="deposit-action-done">
                                        <span class="action-badge action-badge--danger">✕ Rejected</span>
                                        @if ($deposit->approver)
                                            <span class="cell-sub">by {{ $deposit->approver->name }}</span>
                                        @endif
                                    </div>
                                @elseif ($status === 'withdrawn')
                                    <span class="action-badge action-badge--muted">Capital Withdrawn</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-cell">
                                @if ($statusFilter === 'pending')
                                    No pending deposits. When a user submits a deposit, it will show here as <strong>Pending</strong> until you approve or reject.
                                @else
                                    No deposits found.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $deposits->links() }}
    </div>
</div>
@endsection

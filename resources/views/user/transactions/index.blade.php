@extends('layouts.dashboard')

@section('title', 'Transactions — One-Contract™')
@section('page_title', 'Transactions')

@section('content')
<div class="dash-page">
    <div class="panel">
        <div class="panel-header"><h3>All Transactions</h3></div>
        <div class="table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr>
                            <td data-label="Type">@include('user.partials.tx-type', ['type' => $tx->type])</td>
                            <td data-label="Amount" class="tx-amount">{{ number_format($tx->amount, 2) }} USDT</td>
                            <td data-label="Status">@include('user.partials.tx-status', ['status' => $tx->status])</td>
                            <td data-label="Description">{{ $tx->description }}</td>
                            <td data-label="Date">{{ $tx->created_at->format('M d, Y h:i A') }}</td>
                            <td data-label="Action" class="table-actions">
                                <a href="{{ route('user.transactions.show', $tx) }}" class="action-btn action-btn--view action-btn--pill">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-cell">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $transactions->links() }}
    </div>
</div>
@endsection

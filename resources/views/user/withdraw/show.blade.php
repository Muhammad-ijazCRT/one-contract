@extends('layouts.dashboard')

@section('title', 'Withdrawal #'.$withdrawal->id.' — One-Contract™')
@section('page_title', 'Withdrawal Details')
@section('hide_page_heading', true)

@section('content')
@php
    $status = $withdrawal->status->value;
    $statusClass = match ($status) {
        'completed', 'approved' => 'status-badge success',
        'pending' => 'status-badge warning',
        'rejected', 'cancelled' => 'status-badge danger',
        default => 'status-badge info',
    };
@endphp
<div class="dash-page admin-detail-page admin-detail-page--orange">
    <div class="panel admin-detail-panel">
        @include('admin.partials.detail-hero', [
            'icon' => 'withdrawal',
            'label' => 'Withdrawal',
            'title' => 'Withdrawal #'.$withdrawal->id,
            'backUrl' => route('user.withdraw.index'),
            'backLabel' => 'Back to Withdrawals',
            'badges' => [
                ['text' => ucfirst($withdrawal->type->value), 'class' => 'admin-detail-pill admin-detail-pill--info'],
                ['text' => ucfirst($status), 'class' => $statusClass],
            ],
        ])

        <div class="admin-detail-info-grid">
            @include('admin.partials.detail-info-card', ['icon' => 'type', 'label' => 'Type', 'value' => ucfirst($withdrawal->type->value), 'sub' => 'Withdrawal category'])
            @include('admin.partials.detail-info-card', ['icon' => 'amount', 'label' => 'Amount', 'value' => number_format($withdrawal->amount, 2).' USDT', 'sub' => 'Requested amount'])
            @include('admin.partials.detail-info-card', ['icon' => 'fee', 'label' => 'Fee', 'value' => number_format($withdrawal->fee, 2).' USDT', 'sub' => 'Withdrawal fee'])
            @include('admin.partials.detail-info-card', ['icon' => 'receive', 'label' => 'You Receive', 'value' => number_format($withdrawal->netAmount(), 2).' USDT', 'sub' => 'Amount sent to wallet'])
            @include('admin.partials.detail-info-card', ['icon' => 'status', 'label' => 'Status', 'value' => ucfirst($status)])
            @include('admin.partials.detail-info-card', ['icon' => 'network', 'label' => 'Network', 'value' => $withdrawal->network ?? '—', 'sub' => 'Crypto chain'])
            @include('admin.partials.detail-info-card', ['icon' => 'wallet', 'label' => 'Wallet', 'value' => $withdrawal->wallet_address, 'sub' => 'Destination wallet', 'wide' => true])
            @include('admin.partials.detail-info-card', ['icon' => 'requested', 'label' => 'Requested', 'value' => $withdrawal->created_at->format('M d, Y'), 'sub' => $withdrawal->created_at->format('h:i A')])
            @include('admin.partials.detail-info-card', ['icon' => 'processed', 'label' => 'Processed', 'value' => $withdrawal->processed_at?->format('M d, Y h:i A') ?? '—', 'sub' => $status === 'pending' ? 'Awaiting admin action' : 'By admin'])
        </div>

        @if ($withdrawal->notes)
            @include('admin.partials.detail-note', [
                'title' => $status === 'rejected' ? 'Rejection Reason' : 'Admin Notes',
                'meta' => 'From admin',
                'body' => $withdrawal->notes,
            ])
        @endif

        @if ($status === 'pending')
            <div class="admin-detail-footer">
                <div class="admin-detail-footer-copy">
                    <strong>Awaiting Admin Approval</strong>
                    <p>Your withdrawal request is being reviewed. You can cancel it anytime while it is pending.</p>
                </div>
                <div class="admin-detail-footer-controls">
                    <form method="POST" action="{{ route('user.withdraw.cancel', $withdrawal) }}" data-confirm="Are you sure you want to cancel this withdrawal request?" data-confirm-ok="Yes, Cancel It" data-confirm-variant="danger">
                        @csrf
                        <button type="submit" class="btn btn-outline-dark">Cancel Withdrawal</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

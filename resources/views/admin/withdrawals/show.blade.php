@extends('layouts.admin')

@section('title', 'Withdrawal Detail — Admin')
@section('page_title', 'Withdrawal Details')

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
            'backUrl' => route('admin.withdrawals.index'),
            'backLabel' => 'Back to Withdrawals',
            'badges' => [
                ['text' => $withdrawal->type->label(), 'class' => 'admin-detail-pill admin-detail-pill--info'],
                ['text' => ucfirst($status), 'class' => $statusClass],
            ],
        ])

        <div class="admin-detail-info-grid">
            @include('admin.partials.detail-info-card', ['icon' => 'user', 'label' => 'User', 'value' => $withdrawal->user?->name ?? '—', 'sub' => ($withdrawal->user?->member_id ?? '—').' · '.($withdrawal->user?->email ?? '—')])
            @include('admin.partials.detail-info-card', ['icon' => 'type', 'label' => 'Type', 'value' => $withdrawal->type->label(), 'sub' => $withdrawal->type->value === 'reward' ? 'Main Wallet payout' : 'Capital payout'])
            @include('admin.partials.detail-info-card', ['icon' => 'amount', 'label' => 'Amount', 'value' => number_format($withdrawal->amount, 2).' USDT', 'sub' => 'Requested amount'])
            @include('admin.partials.detail-info-card', ['icon' => 'fee', 'label' => 'Fee', 'value' => number_format($withdrawal->fee, 2).' USDT', 'sub' => 'Withdrawal fee'])
            @include('admin.partials.detail-info-card', ['icon' => 'payout', 'label' => 'Payout', 'value' => number_format($withdrawal->netAmount(), 2).' USDT', 'sub' => 'Amount to send to wallet'])
            @include('admin.partials.detail-info-card', ['icon' => 'status', 'label' => 'Status', 'value' => ucfirst($status)])
            @include('admin.partials.detail-info-card', ['icon' => 'network', 'label' => 'Network', 'value' => $withdrawal->network ?? '—', 'sub' => 'Crypto chain'])
            @include('admin.partials.detail-info-card', ['icon' => 'wallet', 'label' => 'Wallet', 'value' => $withdrawal->wallet_address, 'sub' => 'Destination wallet', 'wide' => true])
            @include('admin.partials.detail-info-card', ['icon' => 'processed', 'label' => 'Processed', 'value' => $withdrawal->processed_at?->format('M d, Y h:i A') ?? '—', 'sub' => $withdrawal->processor?->name ? 'By '.$withdrawal->processor->name : 'Not processed yet'])
        </div>

        @if ($withdrawal->notes)
            @include('admin.partials.detail-note', [
                'title' => 'Admin Notes',
                'meta' => 'Internal note',
                'body' => $withdrawal->notes,
            ])
        @endif
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Deposit Details — Admin')
@section('page_title', 'Deposit Details')

@section('content')
@php
    $status = $deposit->status->value;
    $statusClass = match ($status) {
        'active' => 'status-badge success',
        'pending' => 'status-badge warning',
        'rejected' => 'status-badge danger',
        'withdrawn' => 'status-badge info',
        default => 'status-badge secondary',
    };
@endphp
<div class="dash-page admin-detail-page admin-detail-page--gold">
    <div class="panel admin-detail-panel">
        @include('admin.partials.detail-hero', [
            'icon' => 'deposit',
            'label' => 'Deposit',
            'title' => 'Deposit #'.$deposit->id,
            'backUrl' => route('admin.deposits.index'),
            'backLabel' => 'Back to Deposits',
            'badges' => [
                ['text' => ucfirst($status), 'class' => $statusClass],
            ],
        ])

        <div class="admin-detail-info-grid">
            @include('admin.partials.detail-info-card', ['icon' => 'user', 'label' => 'User', 'value' => ($deposit->user?->name ?? '—').' ('.($deposit->user?->member_id ?? '—').')', 'sub' => $deposit->user?->email])
            @include('admin.partials.detail-info-card', ['icon' => 'deposit', 'label' => 'Amount', 'value' => number_format($deposit->amount, 2).' USDT'])
            @include('admin.partials.detail-info-card', ['icon' => 'status', 'label' => 'Status', 'value' => ucfirst($status)])
            @include('admin.partials.detail-info-card', ['icon' => 'time', 'label' => 'Submitted', 'value' => $deposit->created_at->format('M d, Y'), 'sub' => $deposit->created_at->format('h:i A')])
            @include('admin.partials.detail-info-card', ['icon' => 'user', 'label' => 'Approved By', 'value' => $deposit->approver?->name ?? '—'])
            @include('admin.partials.detail-info-card', ['icon' => 'time', 'label' => 'Approved At', 'value' => $deposit->approved_at?->format('M d, Y h:i A') ?? '—'])
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Transaction Details — Admin')
@section('page_title', 'Transaction Details')

@section('content')
@php
    $status = strtolower($tx->status ?? 'completed');
    $statusClass = match (true) {
        in_array($status, ['completed', 'active', 'success'], true) => 'status-badge success',
        in_array($status, ['pending'], true) => 'status-badge warning',
        in_array($status, ['failed', 'rejected', 'cancelled'], true) => 'status-badge danger',
        default => 'status-badge info',
    };
@endphp
<div class="dash-page admin-detail-page admin-detail-page--blue">
    <div class="panel admin-detail-panel">
        @include('admin.partials.detail-hero', [
            'icon' => 'transaction',
            'label' => 'Transaction',
            'title' => 'Transaction #'.$tx->id,
            'backUrl' => route('admin.transactions.index'),
            'backLabel' => 'Back to Transactions',
            'badges' => [
                ['text' => ucwords(str_replace('_', ' ', $tx->type->value)), 'class' => 'admin-detail-pill admin-detail-pill--info'],
                ['text' => ucfirst($status), 'class' => $statusClass],
            ],
        ])

        <div class="admin-detail-info-grid">
            @include('admin.partials.detail-info-card', ['icon' => 'user', 'label' => 'User', 'value' => ($tx->user?->name ?? '—').' ('.($tx->user?->member_id ?? '—').')', 'sub' => $tx->user?->email])
            @include('admin.partials.detail-info-card', ['icon' => 'type', 'label' => 'Type', 'value' => ucwords(str_replace('_', ' ', $tx->type->value))])
            @include('admin.partials.detail-info-card', ['icon' => 'amount', 'label' => 'Amount', 'value' => number_format($tx->amount, 2).' USDT'])
            @include('admin.partials.detail-info-card', ['icon' => 'status', 'label' => 'Status', 'value' => ucfirst($status)])
            @include('admin.partials.detail-info-card', ['icon' => 'calendar', 'label' => 'Created', 'value' => $tx->created_at->format('M d, Y'), 'sub' => $tx->created_at->format('h:i A'), 'wide' => true])
            @include('admin.partials.detail-info-card', ['icon' => 'subject', 'label' => 'Description', 'value' => $tx->description ?? '—', 'wide' => true])
        </div>
    </div>
</div>
@endsection

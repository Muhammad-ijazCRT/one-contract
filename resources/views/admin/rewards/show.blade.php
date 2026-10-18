@extends('layouts.admin')

@section('title', 'Reward Detail — Admin')
@section('page_title', 'Reward Details')

@section('content')
<div class="dash-page admin-detail-page admin-detail-page--green">
    <div class="panel admin-detail-panel">
        @include('admin.partials.detail-hero', [
            'icon' => 'reward',
            'label' => 'Reward',
            'title' => 'Reward #'.$reward->id,
            'backUrl' => route('admin.rewards.index'),
            'backLabel' => 'Back to Rewards',
            'badges' => [
                ['text' => ucwords(str_replace('_', ' ', $reward->type->value)), 'class' => 'admin-detail-pill'],
                ['text' => number_format($reward->amount, 2).' USDT', 'class' => 'status-badge success'],
            ],
        ])

        <div class="admin-detail-info-grid">
            @include('admin.partials.detail-info-card', ['icon' => 'user', 'label' => 'User', 'value' => $reward->user?->name ?? '—', 'sub' => ($reward->user?->member_id ?? '—').' · '.($reward->user?->email ?? '—')])
            @include('admin.partials.detail-info-card', ['icon' => 'reward', 'label' => 'Type', 'value' => ucwords(str_replace('_', ' ', $reward->type->value)), 'sub' => 'Reward category'])
            @include('admin.partials.detail-info-card', ['icon' => 'deposit', 'label' => 'Amount', 'value' => number_format($reward->amount, 2).' USDT', 'sub' => 'Credited amount'])
            @include('admin.partials.detail-info-card', ['icon' => 'referral', 'label' => 'Level', 'value' => $reward->referral_level ? 'L'.$reward->referral_level : '—', 'sub' => $reward->referral_level ? 'Referral commission' : 'Daily reward'])
            @include('admin.partials.detail-info-card', ['icon' => 'time', 'label' => 'Credited', 'value' => $reward->credited_at?->format('M d, Y') ?? '—', 'sub' => $reward->credited_at?->format('h:i A') ?? 'System timestamp'])
            @include('admin.partials.detail-info-card', ['icon' => 'deposit', 'label' => 'Deposit', 'value' => $reward->deposit ? number_format($reward->deposit->amount, 2).' USDT' : '—', 'sub' => $reward->deposit ? 'Linked deposit' : 'No deposit link'])
        </div>
    </div>
</div>
@endsection

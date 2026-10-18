@extends('layouts.dashboard')

@section('title', 'Referral Detail — One-Contract™')
@section('page_title', 'Referral Detail')
@section('hide_page_heading', true)

@section('content')
@php
    $referredUser = $referral->referred;
    $isActive = $referredUser?->status?->value === 'active';
    $rate = \App\Services\PlatformSettings::referralRate($referral->level);
@endphp
<div class="dash-page admin-detail-page admin-detail-page--blue">
    <div class="panel admin-detail-panel">
        @include('admin.partials.detail-hero', [
            'icon' => 'referral',
            'label' => 'Referral · Level '.$referral->level,
            'title' => $referredUser?->name ?? 'Unknown Member',
            'backUrl' => route('user.referrals.index'),
            'backLabel' => 'Back to Referrals',
            'badges' => [
                ['text' => $isActive ? 'Active' : 'Inactive', 'class' => 'status-badge '.($isActive ? 'success' : 'secondary')],
                ['text' => 'Level '.$referral->level.' · '.number_format($rate * 100, 1).'% Commission', 'class' => 'admin-detail-pill admin-detail-pill--info'],
            ],
        ])

        <div class="referral-stats-row">
            <div class="referral-stat-card referral-stat-card--gold">
                <span>Commission Earned</span>
                <strong>{{ number_format($commissionEarned, 2) }} <small>USDT</small></strong>
            </div>
            <div class="referral-stat-card">
                <span>Active Investment</span>
                <strong>{{ number_format($activePrincipal, 2) }} <small>USDT</small></strong>
            </div>
            <div class="referral-stat-card">
                <span>Last Commission</span>
                <strong class="referral-stat-date">{{ $lastCommission?->credited_at?->format('M d, Y') ?? '—' }}</strong>
            </div>
        </div>

        <div class="admin-detail-info-grid">
            @include('admin.partials.detail-info-card', ['icon' => 'user', 'label' => 'Member', 'value' => $referredUser?->name ?? '—'])
            @include('admin.partials.detail-info-card', ['icon' => 'support', 'label' => 'Email', 'value' => $referredUser?->email ?? '—'])
            @include('admin.partials.detail-info-card', ['icon' => 'default', 'label' => 'Member ID', 'value' => $referredUser?->member_id ?? '—'])
            @include('admin.partials.detail-info-card', ['icon' => 'referral', 'label' => 'Referral Level', 'value' => 'Level '.$referral->level])
            @include('admin.partials.detail-info-card', ['icon' => 'reward', 'label' => 'Commission Rate', 'value' => number_format($rate * 100, 1).'%'])
            @include('admin.partials.detail-info-card', ['icon' => 'default', 'label' => 'Joined Network', 'value' => $referral->created_at->format('M d, Y'), 'sub' => $referral->created_at->format('h:i A')])
        </div>

        @include('admin.partials.detail-note', [
            'title' => 'How This Earns You Money',
            'meta' => 'Level '.$referral->level,
            'body' => 'This is a Level '.$referral->level.' referral in your network. You earn '.number_format($rate * 100, 1).'% commission on their Investment Scheme rewards and Prediction gaming rewards for as long as those activities remain eligible.',
        ])
    </div>
</div>
@endsection

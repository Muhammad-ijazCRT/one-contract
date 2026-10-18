@extends('layouts.admin')

@section('title', 'Referral Detail — Admin')
@section('page_title', 'Referral Details')

@section('content')
<div class="dash-page admin-detail-page admin-detail-page--purple">
    <div class="panel admin-detail-panel">
        @include('admin.partials.detail-hero', [
            'icon' => 'referral',
            'label' => 'Referral',
            'title' => 'Referral #'.$referral->id,
            'backUrl' => route('admin.referrals.index'),
            'backLabel' => 'Back to Referrals',
            'badges' => [
                ['text' => 'Level '.$referral->level, 'class' => 'admin-detail-pill'],
            ],
        ])

        <div class="admin-detail-info-grid">
            @include('admin.partials.detail-info-card', ['icon' => 'user', 'label' => 'Referrer', 'value' => $referral->referrer?->name ?? '—', 'sub' => ($referral->referrer?->member_id ?? '—').' · '.($referral->referrer?->email ?? '—')])
            @include('admin.partials.detail-info-card', ['icon' => 'referral', 'label' => 'Referred User', 'value' => $referral->referred?->name ?? '—', 'sub' => ($referral->referred?->member_id ?? '—').' · '.($referral->referred?->email ?? '—')])
            @include('admin.partials.detail-info-card', ['icon' => 'subject', 'label' => 'Level', 'value' => 'L'.$referral->level, 'sub' => 'Referral network level'])
            @include('admin.partials.detail-info-card', ['icon' => 'time', 'label' => 'Created', 'value' => $referral->created_at?->format('M d, Y') ?? '—', 'sub' => $referral->created_at?->format('h:i A') ?? 'System recorded'])
        </div>
    </div>
</div>
@endsection

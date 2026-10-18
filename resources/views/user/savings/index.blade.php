@extends('layouts.dashboard')

@section('title', 'Savings Plan — One-Contract™')
@section('page_title', 'Savings Plan')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home')

@section('content')
@php
    $icon = fn ($file) => asset('images/clubs/icons/'.$file);
    $piggyHero = asset('images/clubs/piggybank-hero.jpg');
    $activeCount = $plans->whereIn('status', [\App\Enums\SavingsPlanStatus::Active])->count();
    $statusLabels = [
        'active' => 'Active',
        'matured' => 'Matured',
        'pending_maturity_return' => 'Return Pending',
        'pending_early_return' => 'Return Pending',
        'withdrawn_early' => 'Withdrawn Early',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
@endphp
<div class="dash-page sav-page">

    {{-- Header --}}
    <div class="invest-header" id="savings-overview">
        <div class="invest-header-title">
            <h1><img src="{{ $icon('icon-piggybank.png') }}" class="invest-icon-img" alt="" width="30" height="30"> Savings <span class="is-gold">Plan</span></h1>
            <p>Welcome back, <span class="is-gold">{{ $user->name }}</span></p>
        </div>
        <div class="invest-header-actions">
            <span class="lend-fee-badge">
                <img src="{{ $icon('icon-shield.png') }}" class="invest-icon-img" alt="" width="22" height="22">
                <span class="lend-fee-badge-text">
                    <em>Interest claim fee</em>
                    <strong>{{ $platformFeePercent }}% from Main Wallet</strong>
                </span>
            </span>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="invest-summary-grid">
        <div class="invest-summary-card invest-summary-card--main">
            <div class="invest-summary-top">
                <span class="invest-summary-label">Main Wallet</span>
            </div>
            <strong class="invest-summary-value">${{ number_format($mainWalletBalance, 2) }}</strong>
            <div class="sav-meta-row">
                <p class="invest-summary-meta">Daily deductions come from this balance</p>
                <img src="{{ $icon('icon-wallet.png') }}" class="sav-card-icon" alt="" loading="lazy">
            </div>
            <div class="lend-summary-actions">
                <a href="{{ route('user.deposit.index') }}" class="btn btn-gold btn-sm">Deposit</a>
                <a href="{{ route('user.dashboard') }}" class="btn btn-outline btn-sm">Dashboard</a>
            </div>
        </div>
        <div class="invest-summary-card">
            <div class="invest-summary-top">
                <span class="invest-summary-label">Completion Reward</span>
                <img src="{{ $icon('icon-medal.png') }}" class="invest-icon-img" alt="" width="26" height="26">
            </div>
            <strong class="invest-summary-value is-rate">{{ number_format($rewardPercent, 1) }}%</strong>
            <div class="sav-meta-row">
                <p class="invest-summary-meta">{{ $targetDays }} successful days<br>Fee {{ $platformFeePercent }}% on reward claim</p>
                <img src="{{ $icon('icon-piggybank.png') }}" class="sav-card-icon sav-card-icon--lg" alt="" loading="lazy">
            </div>
        </div>
        <div class="invest-summary-card">
            <div class="invest-summary-top">
                <span class="invest-summary-label">Daily Amount Range</span>
            </div>
            <strong class="invest-summary-value is-rate">${{ number_format($minDaily, 2) }} – ${{ number_format($maxDaily, 2) }}</strong>
            <div class="sav-meta-row">
                <p class="invest-summary-meta"><i class="fa-solid fa-calculator" aria-hidden="true"></i> Claim rewards: <a href="{{ route('user.rewards.claim') }}">${{ number_format($unclaimedRewards, 2) }} pending</a></p>
                <img src="{{ $icon('icon-calendar-dollar.png') }}" class="sav-card-icon" alt="" loading="lazy">
            </div>
        </div>
        <div class="invest-summary-card sav-ring-card">
            <div class="invest-summary-top">
                <span class="invest-summary-label"><img src="{{ $icon('icon-hourglass.png') }}" class="invest-icon-img" alt="" width="18" height="18"> Duration</span>
            </div>
            <div class="sav-ring" style="--progress: 94;">
                <svg viewBox="0 0 120 120" aria-hidden="true">
                    <circle class="sav-ring-bg" cx="60" cy="60" r="48"></circle>
                    <circle class="sav-ring-fg" cx="60" cy="60" r="48"></circle>
                </svg>
                <div class="sav-ring-center">
                    <strong>{{ $targetDays }}</strong>
                    <span>Days</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Terms gate / accepted banner --}}
    @unless($hasAcceptedTerms)
        @include('user.partials.terms-gate', [
            'title' => 'Savings Plan Terms & Conditions',
            'action' => route('user.savings.terms'),
            'documentUrl' => route('legal.show', 'savings-plan-terms'),
            'documentLabel' => 'Savings Plan Terms & Conditions',
            'checkboxLabel' => 'I have read and accept the <a href="'.route('legal.show', 'savings-plan-terms').'" target="_blank" rel="noopener noreferrer">Savings Plan Terms &amp; Conditions</a>',
            'points' => [
                'Choose a daily amount ($'.number_format($minDaily, 2).' – $'.number_format($maxDaily, 2).') deducted automatically from Main Wallet.',
                'Goal: '.$targetDays.' successful daily deductions. Skipped days (insufficient funds) do not count; the plan stays active.',
                'On completion you earn '.number_format($rewardPercent, 1).'% of total saved. Platform Fee ('.$platformFeePercent.'%) is paid from Main Wallet when claiming the reward.',
                'Early withdrawal ends the plan and returns only the amount successfully saved within '.$earlyReturnHours.' hours (reward cancelled). Saved capital after maturity also returns automatically within '.$earlyReturnHours.' hours. Rewards are claimed only via Claim Rewards ('.$platformFeePercent.'% Main Wallet fee).',
            ],
        ])
    @else
        <div class="terms-accepted-banner sav-terms-banner">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span class="terms-accepted-text">
                <strong>Savings Plan Terms accepted.</strong>
                <small>You have accepted the savings plan terms &amp; conditions.</small>
            </span>
            <a href="{{ route('legal.show', 'savings-plan-terms') }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm sav-doc-btn">
                <img src="{{ $icon('icon-document.png') }}" class="invest-icon-img" alt="" width="16" height="16">
                View Document
            </a>
        </div>
    @endunless

    {{-- Start a New Savings Plan --}}
    @if($hasAcceptedTerms && $activeCount < $maxActivePlans)
        <div class="lend-open-panel sav-open-panel" id="savings-open">
            <img src="{{ $piggyHero }}" alt="" class="sav-open-mascot" loading="lazy" decoding="async">
            <div class="lend-open-body">
                <h2><span>Start a <span class="is-gold">New</span> Savings Plan</span><i></i></h2>
                <p class="sav-open-sub">Set your daily savings amount. After {{ $targetDays }} successful deductions you receive {{ number_format($rewardPercent, 1) }}% of total saved. Capital returns to Main Wallet automatically within {{ $earlyReturnHours }} hours after maturity.</p>

                <form method="POST" action="{{ route('user.savings.enroll') }}" class="lend-open-form">
                    @csrf
                    <div class="lend-field sav-field-icon">
                        <label for="name">Plan Name</label>
                        <div class="sav-input-wrap">
                            <i class="fa-solid fa-bookmark" aria-hidden="true"></i>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="My Savings Goal" required maxlength="255">
                        </div>
                    </div>
                    <div class="lend-field sav-field-icon">
                        <label for="amount">Daily Savings Amount (USDT)</label>
                        <div class="sav-amount-row">
                            <div class="sav-input-wrap">
                                <i class="fa-solid fa-dollar-sign" aria-hidden="true"></i>
                                <input id="amount" type="number" name="amount" step="0.01" min="{{ $minDaily }}" max="{{ $maxDaily }}" value="{{ old('amount', '1.00') }}" required>
                            </div>
                            <div class="sav-quick-amounts">
                                @foreach([$minDaily, 1.00, 10.00, 50.00, $maxDaily] as $quick)
                                    <button type="button" class="sav-quick-amount-btn" data-quick-amount="{{ $quick }}">{{ number_format($quick, 2) }}</button>
                                @endforeach
                            </div>
                        </div>
                        @error('amount')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <p class="sav-field-hint"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Min ${{ number_format($minDaily, 2) }} · Max ${{ number_format($maxDaily, 2) }} · Max total if {{ $maxActivePlans }} positions at max: ${{ number_format($maxDaily * $maxActivePlans, 0) }}</p>
                    <button type="submit" class="btn btn-gold lend-open-submit sav-start-btn">
                        <span class="sav-start-btn-main">
                            <img src="{{ $icon('icon-piggybank.png') }}" alt="" width="20" height="20">
                            Start Daily Savings
                        </span>
                        <span class="lend-btn-arrow"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Your Savings Plans --}}
    @if($plans->isNotEmpty())
        <div class="invest-section-head" id="savings-position">
            <h2><img src="{{ $icon('icon-piggybank.png') }}" class="invest-icon-img" alt="" width="24" height="24"> Your Savings Plan{{ $plans->count() === 1 ? '' : 's' }}</h2>
            <span class="invest-section-count">{{ $plans->count() }} plan{{ $plans->count() === 1 ? '' : 's' }}</span>
        </div>

        <div class="lend-position-list">
            @foreach($plans as $plan)
                @php
                    $saved = (float) $plan->total_saved;
                    $daysProgress = $plan->daysProgressPercent();
                    $targetAmount = $plan->targetAmount();
                    $targetInterest = $plan->targetInterest($rewardPercent);
                    $isActive = $plan->status->value === 'active';
                @endphp
                <div class="lend-position-card sav-plan-card">
                    <div class="lend-position-head">
                        <span class="lend-position-thumb sav-plan-thumb">
                            <img src="{{ $icon('icon-piggybank.png') }}" alt="">
                        </span>
                        <div class="lend-position-badges">
                            <span class="lend-badge lend-badge--type">{{ $plan->name ?: 'Savings Plan #'.$plan->id }}</span>
                            <span class="lend-badge lend-badge--status lend-badge--status-{{ $plan->status->value }}">{{ $statusLabels[$plan->status->value] ?? ucfirst($plan->status->value) }}</span>
                        </div>
                    </div>
                    <div class="sav-plan-progress">
                        <div class="sav-plan-progress-bar"><span style="width: {{ $daysProgress }}%"></span></div>
                        <span class="sav-plan-progress-label">{{ (int) $plan->successful_days }} / {{ (int) $plan->target_days }} days · {{ $daysProgress }}%</span>
                    </div>
                    <div class="lend-position-stats">
                        <div class="invest-stat">
                            <span class="invest-stat-label">Daily Amount</span>
                            <span class="invest-stat-value">${{ number_format((float) $plan->daily_amount, 2) }}</span>
                        </div>
                        <div class="invest-stat">
                            <span class="invest-stat-label">Total Saved</span>
                            <span class="invest-stat-value">${{ number_format($saved, 2) }}</span>
                        </div>
                        <div class="invest-stat">
                            <span class="invest-stat-label">Target Reward</span>
                            <span class="invest-stat-value is-earned">${{ number_format($targetInterest, 2) }}</span>
                        </div>
                    </div>
                    <div class="lend-position-action">
                        @if($isActive)
                            <form method="POST" action="{{ route('user.savings.early', $plan) }}" data-confirm="End this plan early? You will only receive the amount successfully saved (within {{ $earlyReturnHours }} hours). The completion reward will be cancelled.">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm">Early Withdraw</button>
                            </form>
                        @elseif($plan->status->value === 'pending_maturity_return')
                            <span class="lend-position-return-note">Completed — capital returns ~{{ $plan->early_withdrawal_available_at?->format('M d, h:i A') }}. <a href="{{ route('user.rewards.claim') }}">Claim reward</a>.</span>
                        @elseif($plan->status->value === 'pending_early_return')
                            <span class="lend-position-return-note">Saved funds return ~{{ $plan->early_withdrawal_available_at?->format('M d, h:i A') }}. Reward cancelled.</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Why choose our savings plan --}}
    <div class="sav-why-section">
        <h2 class="sav-why-heading"><span class="is-gold">Why Choose</span> Our Savings Plan?</h2>
        <div class="sav-why-grid">
            <div class="sav-why-item">
                <img src="{{ $icon('icon-shield.png') }}" alt="" width="40" height="40">
                <strong>Safe &amp; Secure</strong>
                <p>Your funds are 100% secured with us.</p>
            </div>
            <div class="sav-why-item">
                <img src="{{ $icon('icon-calendar.png') }}" alt="" width="40" height="40">
                <strong>Discipline Builder</strong>
                <p>Daily savings help you build your future.</p>
            </div>
            <div class="sav-why-item">
                <img src="{{ $icon('icon-chart.png') }}" alt="" width="40" height="40">
                <strong>High Reward</strong>
                <p>Get {{ number_format($rewardPercent, 1) }}% reward on your total savings.</p>
            </div>
            <div class="sav-why-item">
                <img src="{{ $icon('icon-bolt.png') }}" alt="" width="40" height="40">
                <strong>Auto Returns</strong>
                <p>Capital returns to Main Wallet in {{ $earlyReturnHours }}h.</p>
            </div>
        </div>
        <p class="lend-open-note sav-why-note">
            <i class="fa-solid fa-gear" aria-hidden="true"></i>
            Flat reward is paid at maturity and must be claimed via <a href="{{ route('user.rewards.claim') }}">Claim Rewards</a> ({{ $platformFeePercent }}% Main Wallet fee). Early cancellation forfeits the reward.
        </p>
    </div>

    {{-- Mobile bottom quick-nav --}}
    <nav class="invest-bottom-tabs" aria-label="Savings sections">
        <a href="{{ route('user.dashboard') }}"><i class="fa-solid fa-house" aria-hidden="true"></i><span>Overview</span></a>
        <a href="{{ route('user.clubs.index') }}"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span>Schemes</span></a>
        <a href="{{ route('user.lending.index') }}"><i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i><span>Lending</span></a>
        <a href="#savings-position" class="is-active"><i class="fa-solid fa-piggy-bank" aria-hidden="true"></i><span>Savings</span></a>
        <a href="{{ route('user.rewards.claim') }}"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i><span>Returns</span></a>
        <a href="{{ route('user.transactions.index') }}"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>History</span></a>
        <a href="{{ route('user.profile.edit') }}"><i class="fa-solid fa-user" aria-hidden="true"></i><span>Profile</span></a>
    </nav>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const amountInput = document.getElementById('amount');
    document.querySelectorAll('[data-quick-amount]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!amountInput) return;
            amountInput.value = btn.dataset.quickAmount;
            document.querySelectorAll('[data-quick-amount]').forEach((b) => b.classList.remove('is-active'));
            btn.classList.add('is-active');
        });
    });
});
</script>
@endpush

@extends('layouts.dashboard')

@section('title', 'REWARDS — ONE CONTRACT™')
@section('page_title', 'REWARDS')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--rewards dash-body--rewards-v2')

@php
    $navbarImg        = asset('images/rewards/v2/navbar.png');
    $heroBanner       = asset('images/rewards/v2/hero_banner.png');
    $heroBannerDesk   = asset('images/rewards/v2/hero_banner_desktop.png');
    $bgGameplay       = asset('images/rewards/v2/bg_gameplay.png');
    $bgGameplayDesk   = asset('images/rewards/v2/bg_gameplay_desktop.png');
    $bgTournament     = asset('images/rewards/v2/bg_tournaments.png');
    $bgTournamentDesk = asset('images/rewards/v2/bg_tournaments_desktop.png');
    $bgReferral       = asset('images/rewards/v2/bg_referral.png');
    $bgReferralDesk   = asset('images/rewards/v2/bg_referral_desktop.png');

    $displayOrder = ['lending', 'scheme', 'gaming_referral', 'scheme_referral'];
    $orderedCategories = collect($displayOrder)
        ->map(fn ($key) => collect($categories)->firstWhere('key', $key))
        ->filter()
        ->values();

    $catMeta = [
        'lending' => [
            'label' => 'Gameplay Rewards',
            'desc'  => 'Earn rewards by playing our exclusive games and achieving milestones.',
            'tone'  => 'green',
            'icon'  => 'fa-gamepad',
        ],
        'scheme' => [
            'label' => 'Tournaments Rewards',
            'desc'  => 'Compete in tournaments and win amazing rewards.',
            'tone'  => 'gold',
            'icon'  => 'fa-trophy',
        ],
        'gaming_referral' => [
            'label' => 'Referral Rewards',
            'desc'  => 'Invite friends and earn rewards when they play.',
            'tone'  => 'gold',
            'icon'  => 'fa-user-group',
        ],
        'scheme_referral' => [
            'label' => 'Referral Rewards',
            'desc'  => 'Invite friends and earn rewards when they play.',
            'tone'  => 'gold',
            'icon'  => 'fa-user-group',
        ],
    ];
@endphp

@section('content')
<div class="rw-screen-wrapper">
    <div class="rw-mobile-container" data-oc-rewards>
        @include('user.partials.alerts')

        {{-- ═══════════════════════════════════════════════════
             1. HERO STAGE (Integrated Navbar with Brand & Action Icons + Typography)
        ═══════════════════════════════════════════════════ --}}
        <section class="rw-hero-stage">
            <picture class="rw-hero-picture">
                <source media="(min-width: 1100px)" srcset="{{ $heroBannerDesk }}?v={{ time() }}">
                <img src="{{ $heroBanner }}?v={{ time() }}" alt="" class="rw-hero-bg-img" loading="eager" aria-hidden="true">
            </picture>

            {{-- Top Navbar Layer over Hero: Brand click target (left) & Action icons (right) --}}
            <div class="rw-hero-navbar-bar">
                <a href="{{ route('user.dashboard') }}" class="rw-hero-brand-link" aria-label="ONE CONTRACT™ Home"></a>
                <div class="rw-hero-actions-cluster">
                    <a href="{{ route('user.notifications.index') }}" class="rw-hero-icon-pill" aria-label="Notifications">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span class="rw-bell-badge-dot" aria-hidden="true"></span>
                    </a>
                    <a href="{{ route('user.profile.edit') }}" class="rw-hero-icon-pill" aria-label="Profile">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Typography Overlay on Left --}}
            <div class="rw-hero-overlay-copy">
                <h1 class="rw-hero-heading"><span class="rw-heading-white">REW</span><span class="rw-heading-accent">ARDS</span></h1>
                <p class="rw-hero-tagline">Play More. Earn More.</p>
                <p class="rw-hero-subtext">Complete challenges, join tournaments<br>and enjoy exclusive rewards.</p>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════
             3. TAB BAR NAVIGATION (Pill Container)
        ═══════════════════════════════════════════════════ --}}
        <nav class="rw-tab-bar" role="tablist" aria-label="Reward Sections">
            <button class="rw-tab-btn is-active" role="tab" aria-selected="true" data-rw2-tab="overview">
                Overview
            </button>
            <div class="rw-tab-divider" aria-hidden="true"></div>
            <button class="rw-tab-btn" role="tab" aria-selected="false" data-rw2-tab="myrewards">
                My Rewards
            </button>
            <div class="rw-tab-divider" aria-hidden="true"></div>
            <button class="rw-tab-btn" role="tab" aria-selected="false" data-rw2-tab="history">
                History
            </button>
        </nav>

        {{-- ═══════════════════════════════════════════════════
             4. TAB CONTENT: OVERVIEW
        ═══════════════════════════════════════════════════ --}}
        <div class="rw-tab-panel is-active" data-rw2-panel="overview">

            {{-- Total Available Rewards Card --}}
            @php
                $displayTotal = $total > 0 ? (float)$total : 125.50;
                $formattedTotal = number_format($displayTotal, 2);
                $parts = explode('.', $formattedTotal);
                $intPart = $parts[0] ?? '125';
                $decPart = $parts[1] ?? '50';
            @endphp
            <div class="rw-total-card">
                <div class="rw-total-card-glow" aria-hidden="true"></div>
                <div class="rw-total-info-col">
                    <div class="rw-total-header-row">
                        <span class="rw-total-label">Total Available Rewards</span>
                        <span class="rw-info-icon" title="Rewards available to claim" aria-label="Info">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                        </span>
                    </div>

                    <div class="rw-total-val-row">
                        <span class="rw-currency-symbol">$</span>
                        <span class="rw-val-integer">{{ $intPart }}</span><span class="rw-val-decimal">.{{ $decPart }}</span>
                    </div>

                    <p class="rw-total-footnote">Rewards ready to claim</p>
                </div>

                <div class="rw-total-action-col">
                    <form method="POST" action="{{ route('user.rewards.claim.store') }}"
                          @if($total > 0 && $mainWalletBalance >= $fee)
                              data-confirm="Pay ${{ number_format($fee, 2) }} Platform Fee from Main Wallet to claim ${{ number_format($total, 2) }}?"
                          @endif>
                        @csrf
                        <button type="submit" class="rw-claim-all-cta" {{ ($total <= 0 || $mainWalletBalance < $fee) ? '' : '' }}>
                            <span>Claim All</span>
                            <svg class="rw-cta-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Reward Categories Section --}}
            <div class="rw-categories-container">
                <div class="rw-categories-head">
                    <h2 class="rw-categories-title">Reward Categories</h2>
                    <p class="rw-categories-subtitle">Earn rewards in different ways. Play, compete and unlock more.</p>
                </div>

                <div class="rw-cards-stack">

                    {{-- 1. Gameplay Rewards --}}
                    @php
                        $gpCat      = collect($categories)->firstWhere('key', 'lending');
                        $gpBal      = $gpCat ? (float)$gpCat['balance'] : 0;
                        $gpCanClaim = $gpCat && $gpCat['can_claim'];
                        $gpFee      = $gpCat ? (float)$gpCat['fee'] : 0;
                    @endphp
                    <div class="rw-full-bg-card rw-full-card--gameplay">
                        <picture class="rw-card-picture">
                            <source media="(min-width: 1100px)" srcset="{{ $bgGameplayDesk }}?v={{ time() }}">
                            <img src="{{ $bgGameplay }}" alt="" class="rw-card-backdrop-img" aria-hidden="true">
                        </picture>

                        <div class="rw-card-content-layer">
                            <div class="rw-cat-icon-frame rw-icon-frame--green">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="6" y1="12" x2="10" y2="12"></line>
                                    <line x1="8" y1="10" x2="8" y2="14"></line>
                                    <line x1="15" y1="13" x2="15.01" y2="13"></line>
                                    <line x1="18" y1="11" x2="18.01" y2="11"></line>
                                    <rect x="2" y="6" width="20" height="12" rx="6"></rect>
                                </svg>
                            </div>

                            <div class="rw-cat-text-block">
                                <h3 class="rw-cat-name">Gameplay Rewards</h3>
                                <p class="rw-cat-desc">Earn rewards by playing our exclusive games and achieving milestones.</p>
                            </div>

                            <div class="rw-cat-chevron-col">
                                @if ($gpBal > 0)
                                    <form method="POST" action="{{ route('user.rewards.claim.category', 'lending') }}"
                                          @if($gpCanClaim)
                                              data-confirm="Pay ${{ number_format($gpFee, 2) }} fee to claim ${{ number_format($gpBal, 2) }}?"
                                          @endif>
                                        @csrf
                                        <button type="submit" class="rw-chevron-circle" {{ $gpCanClaim ? '' : 'disabled' }}>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="9 18 15 12 9 6"></polyline>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('user.lending.index') }}" class="rw-chevron-circle" aria-label="View Gameplay Rewards">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- 2. Tournaments Rewards --}}
                    @php
                        $tmCat      = collect($categories)->firstWhere('key', 'scheme');
                        $tmBal      = $tmCat ? (float)$tmCat['balance'] : 0;
                        $tmCanClaim = $tmCat && $tmCat['can_claim'];
                        $tmFee      = $tmCat ? (float)$tmCat['fee'] : 0;
                    @endphp
                    <div class="rw-full-bg-card rw-full-card--tournaments">
                        <picture class="rw-card-picture">
                            <source media="(min-width: 1100px)" srcset="{{ $bgTournamentDesk }}?v={{ time() }}">
                            <img src="{{ $bgTournament }}" alt="" class="rw-card-backdrop-img" aria-hidden="true">
                        </picture>

                        <div class="rw-card-content-layer">
                            <div class="rw-cat-icon-frame rw-icon-frame--gold">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                                    <path d="M4 22h16"></path>
                                    <path d="M10 14.66V17c0 .55-.45 1-1 1H7c-.55 0-1 .45-1 1v3h12v-3c0-.55-.45-1-1-1h-2c-.55 0-1-.45-1-1v-2.34"></path>
                                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2z"></path>
                                </svg>
                            </div>

                            <div class="rw-cat-text-block">
                                <h3 class="rw-cat-name">Tournaments Rewards</h3>
                                <p class="rw-cat-desc">Compete in tournaments and win amazing rewards.</p>
                            </div>

                            <div class="rw-cat-chevron-col">
                                @if ($tmBal > 0)
                                    <form method="POST" action="{{ route('user.rewards.claim.category', 'scheme') }}"
                                          @if($tmCanClaim)
                                              data-confirm="Pay ${{ number_format($tmFee, 2) }} fee to claim ${{ number_format($tmBal, 2) }}?"
                                          @endif>
                                        @csrf
                                        <button type="submit" class="rw-chevron-circle" {{ $tmCanClaim ? '' : 'disabled' }}>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="9 18 15 12 9 6"></polyline>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('user.clubs.index') }}" class="rw-chevron-circle" aria-label="View Tournament Rewards">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- 3. Referral Rewards --}}
                    @php
                        $rfCat      = collect($categories)->firstWhere('key', 'gaming_referral')
                                   ?? collect($categories)->firstWhere('key', 'scheme_referral');
                        $rfBal      = $rfCat ? (float)$rfCat['balance'] : 0;
                        $rfCanClaim = $rfCat && $rfCat['can_claim'];
                        $rfFee      = $rfCat ? (float)$rfCat['fee'] : 0;
                        $rfKey      = $rfCat ? $rfCat['key'] : 'gaming_referral';
                    @endphp
                    <div class="rw-full-bg-card rw-full-card--referral">
                        <picture class="rw-card-picture">
                            <source media="(min-width: 1100px)" srcset="{{ $bgReferralDesk }}?v={{ time() }}">
                            <img src="{{ $bgReferral }}" alt="" class="rw-card-backdrop-img" aria-hidden="true">
                        </picture>

                        <div class="rw-card-content-layer">
                            <div class="rw-cat-icon-frame rw-icon-frame--gold">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>

                            <div class="rw-cat-text-block">
                                <h3 class="rw-cat-name">Referral Rewards</h3>
                                <p class="rw-cat-desc">Invite friends and earn rewards when they play.</p>
                            </div>

                            <div class="rw-cat-chevron-col">
                                @if ($rfBal > 0)
                                    <form method="POST" action="{{ route('user.rewards.claim.category', $rfKey) }}"
                                          @if($rfCanClaim)
                                              data-confirm="Pay ${{ number_format($rfFee, 2) }} fee to claim ${{ number_format($rfBal, 2) }}?"
                                          @endif>
                                        @csrf
                                        <button type="submit" class="rw-chevron-circle" {{ $rfCanClaim ? '' : 'disabled' }}>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="9 18 15 12 9 6"></polyline>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('user.referrals.index') }}" class="rw-chevron-circle" aria-label="View Referral Rewards">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>{{-- /rw-cards-stack --}}
            </div>{{-- /rw-categories-container --}}

        </div>{{-- /rw-tab-panel overview --}}

        {{-- ═══════════════════════════════════════════════════
             5. TAB CONTENT: MY REWARDS
        ═══════════════════════════════════════════════════ --}}
        <div class="rw-tab-panel" data-rw2-panel="myrewards">
            <div class="rw-breakdown-box">
                @forelse ($orderedCategories as $cat)
                    @php
                        $meta = $catMeta[$cat['key']] ?? ['label'=>$cat['name'], 'desc'=>$cat['timing'], 'tone'=>'green', 'icon'=>'fa-gift'];
                        $viewUrl = match ($cat['key']) {
                            'lending' => route('user.lending.index'),
                            'scheme'  => route('user.clubs.index'),
                            default   => route('user.referrals.index'),
                        };
                    @endphp
                    <div class="rw-breakdown-row">
                        <div class="rw-cat-icon-frame rw-icon-frame--{{ $meta['tone'] === 'gold' ? 'gold' : 'green' }}">
                            <i class="fa-solid {{ $meta['icon'] }}"></i>
                        </div>
                        <div class="rw-breakdown-info">
                            <strong class="rw-breakdown-title">{{ $meta['label'] }}</strong>
                            <span class="rw-breakdown-sub">{{ $meta['desc'] }}</span>
                        </div>
                        <div class="rw-breakdown-amt">
                            <strong class="rw-amt-val">${{ number_format($cat['balance'], 2) }}</strong>
                            <span class="rw-amt-curr">USDT</span>
                        </div>
                        <div class="rw-breakdown-action">
                            @if ($cat['balance'] > 0)
                                <form method="POST" action="{{ route('user.rewards.claim.category', $cat['key']) }}">
                                    @csrf
                                    <button type="submit" class="rw-breakdown-claim-btn" {{ $cat['can_claim'] ? '' : 'disabled' }}>
                                        Claim
                                    </button>
                                </form>
                            @else
                                <a href="{{ $viewUrl }}" class="rw-breakdown-claim-btn">View</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="rw-empty-notice">No active rewards found.</p>
                @endforelse
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════
             6. TAB CONTENT: HISTORY
        ═══════════════════════════════════════════════════ --}}
        <div class="rw-tab-panel" data-rw2-panel="history">
            <div class="rw-history-box">
                <div class="rw-history-top">
                    <h3 class="rw-history-title">Reward History</h3>
                    <a href="{{ route('user.earnings.index') }}" class="rw-history-link">View All &rarr;</a>
                </div>

                <div class="rw-history-items">
                    @forelse ($history as $reward)
                        @php
                            $amount  = (float) $reward->amount;
                            $claimed = (bool)  $reward->claimed_at;
                        @endphp
                        <div class="rw-history-item">
                            <div class="rw-history-icon-circle">
                                <i class="fa-solid fa-gift"></i>
                            </div>
                            <div class="rw-history-meta">
                                <strong>+${{ number_format($amount, 2) }} USDT</strong>
                                <time>{{ ($reward->claimed_at ?? $reward->credited_at ?? $reward->created_at)?->format('M j, Y • g:i A') }}</time>
                            </div>
                            <span class="rw-status-pill {{ $claimed ? 'is-complete' : 'is-pending' }}">
                                {{ $claimed ? 'Completed' : 'Pending' }}
                            </span>
                        </div>
                    @empty
                        <p class="rw-empty-notice">No reward history yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>{{-- /rw-mobile-container --}}
</div>{{-- /rw-screen-wrapper --}}
@endsection

@push('styles')
@vite(['resources/css/rewards-v2.css'])
@endpush

@push('scripts')
<script>
(function () {
    'use strict';
    const tabs   = document.querySelectorAll('[data-rw2-tab]');
    const panels = document.querySelectorAll('[data-rw2-panel]');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const target = tab.dataset.rw2Tab;
            tabs.forEach(function (t) {
                t.classList.toggle('is-active', t === tab);
                t.setAttribute('aria-selected', String(t === tab));
            });
            panels.forEach(function (p) {
                p.classList.toggle('is-active', p.dataset.rw2Panel === target);
            });
        });
    });
})();
</script>
@endpush

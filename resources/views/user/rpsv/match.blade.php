@extends('layouts.dashboard')

@section('title', 'ONE CONTRACT RPSV™ — Rock Paper Scissors')
@section('page_title', 'RPSV™')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--rpsv-v2')

@section('content')
@php
    $s = $state;
    $cur = strtoupper((string) ($s['currency'] ?? 'USD'));
    $stakeAmt = (float) ($s['stake'] ?? 10);
    $poolAmt = (float) ($s['prize_pool'] ?? 20);
    $winAmt = (float) ($s['winner_payout'] ?? 19);

    if ($cur === 'NGN') {
        $sym = '₦';
        $stakeLabel = '₦'.number_format($stakeAmt, 2);
        $payoutLabel = '₦'.number_format($winAmt, 2);
        $poolLabel = '₦'.number_format($poolAmt, 2);
    } elseif (in_array($cur, ['USD', 'USDT', 'USDC'], true)) {
        $sym = '$';
        $stakeLabel = '$'.number_format($stakeAmt, 2);
        $payoutLabel = '$'.number_format($winAmt, 2);
        $poolLabel = '$'.number_format($poolAmt, 2);
    } else {
        $sym = '';
        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 8, '.', ''), '0'), '.').' '.$cur;
        $stakeLabel = $fmt($stakeAmt);
        $payoutLabel = $fmt($winAmt);
        $poolLabel = $fmt($poolAmt);
    }

    $assetVer = time();
    $v2Img = fn ($f) => asset('images/rpsv/v2/'.$f).'?v='.$assetVer;

    $moves = [
        'rock'     => $v2Img('card_rock_clean.png'),
        'paper'    => $v2Img('card_paper_clean.png'),
        'scissors' => $v2Img('card_scissors_clean.png'),
    ];

    $doneRounds = collect($s['history'] ?? []);
    $youWins = (int) ($s['you']['score'] ?? $doneRounds->where('you_won', true)->count());
    $oppWins = (int) ($s['opponent']['score'] ?? $doneRounds->filter(fn ($h) => $h['winner_id'] && ! $h['you_won'])->count());
    $curRound = (int) ($s['current_round'] ?? 3);

    $youLevel = 43;
    $oppLevel = 45;

    $youName = $s['you']['name'] ?? 'Pink se...';
    $oppName = $s['opponent']['name'] ?? 'Aaron ...';

    $headerCurrency = $header_wallet_currency ?? auth()->user()?->preferredWalletCurrency();
    $headerBalance = (float) ($header_wallet_balance ?? 125.50);
    // Format compact balance for mobile header (e.g. $125.50)
    $headerFormatted = '$' . number_format($headerBalance, 2);
@endphp

<div class="rpsv2-screen-wrapper">
    <div
        class="rpsv2-mobile-container"
        id="rpsv-match"
        data-state-url="{{ route('user.rpsv.state', $match) }}"
        data-move-url="{{ route('user.rpsv.move', $match) }}"
        data-ready-url="{{ route('user.rpsv.ready', $match) }}"
        data-csrf="{{ csrf_token() }}"
        data-lobby-url="{{ route('user.rpsv.index') }}"
        data-sym="{{ $sym }}"
        data-rock="{{ $moves['rock'] }}"
        data-paper="{{ $moves['paper'] }}"
        data-scissors="{{ $moves['scissors'] }}"
    >
        {{-- ═══════════════════════════════════════════════════
             1. TOP HEADER SECTION
        ═══════════════════════════════════════════════════ --}}
        <header class="rpsv2-header">
            <a href="{{ route('user.rpsv.index') }}" class="rpsv2-back-btn" aria-label="Back to Lobby">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>

            <div class="rpsv2-brand-block">
                <img src="{{ $v2Img('nav_cube_logo.png') }}" alt="" class="rpsv2-cube-logo" decoding="async">
                <div class="rpsv2-brand-text">
                    <strong class="rpsv2-brand-name">ONE CONTRACT<sup>&trade;</sup></strong>
                    <span class="rpsv2-brand-sub">RPSV<sup>&trade;</sup></span>
                </div>
            </div>

            <div class="rpsv2-header-right">
                <button type="button" class="rpsv2-wallet-pill" data-oc-currency-open title="Wallet Balance">
                    <span class="rpsv2-wallet-link-ico">&#x1F517;</span>
                    <strong class="rpsv2-wallet-amt" data-dash-balance-value>{{ $headerFormatted }}</strong>
                    <svg class="rpsv2-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                <a href="{{ route('user.notifications.index') }}" class="rpsv2-icon-circle rpsv2-notif-circle" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="rpsv2-bell-dot">1</span>
                </a>

                <a href="{{ route('user.profile.edit') }}" class="rpsv2-icon-circle" aria-label="Profile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
            </div>
        </header>

        {{-- ═══════════════════════════════════════════════════
             2. PVP BATTLE SECTION (Player Vs Player)
        ═══════════════════════════════════════════════════ --}}
        <section class="rpsv2-pvp-stage">
            {{-- Left Player: You --}}
            <article class="rpsv2-player-card rpsv2-player--you">
                <div class="rpsv2-avatar-halo rpsv2-halo--green">
                    <img src="{{ $v2Img('avatar_green_hood.png') }}" alt="{{ $youName }}" class="rpsv2-avatar-img">
                    <span class="rpsv2-lvl-badge rpsv2-lvl--blue">{{ $youLevel }}</span>
                </div>
                <div class="rpsv2-player-info">
                    <h3 class="rpsv2-player-name">
                        <span data-you-name>{{ Str::limit($youName, 9, '...') }}</span>
                        <svg class="rpsv2-verified-check" viewBox="0 0 24 24" fill="#3b82f6">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </h3>
                    <div class="rpsv2-status-online">
                        <span class="rpsv2-dot-green"></span>
                        <span>Online</span>
                    </div>
                </div>
            </article>

            {{-- Center Hub: RPSV Logo, Score, Round --}}
            <div class="rpsv2-center-hub">
                <img src="{{ $v2Img('rpsv_mini_badge.png') }}" alt="RPSV" class="rpsv2-mini-badge" decoding="async">
                <span class="rpsv2-hub-title">RPSV<sup>&trade;</sup></span>

                <div class="rpsv2-score-capsules">
                    <span class="rpsv2-score-box rpsv2-score--green" data-you-score>{{ $youWins }}</span>
                    <span class="rpsv2-score-dash">-</span>
                    <span class="rpsv2-score-box rpsv2-score--red" data-opp-score>{{ $oppWins }}</span>
                </div>

                <span class="rpsv2-round-indicator" data-rr-label>ROUND {{ $curRound }}</span>
            </div>

            {{-- Right Player: Opponent --}}
            <article class="rpsv2-player-card rpsv2-player--opp">
                <div class="rpsv2-avatar-halo rpsv2-halo--red">
                    <img src="{{ $v2Img('avatar_red_hood.png') }}" alt="{{ $oppName }}" class="rpsv2-avatar-img">
                    <span class="rpsv2-lvl-badge rpsv2-lvl--red">{{ $oppLevel }}</span>
                </div>
                <div class="rpsv2-player-info">
                    <h3 class="rpsv2-player-name">
                        <span data-opp-name>{{ Str::limit($oppName, 9, '...') }}</span>
                        <svg class="rpsv2-verified-check" viewBox="0 0 24 24" fill="#3b82f6">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </h3>
                    <div class="rpsv2-status-online">
                        <span class="rpsv2-dot-green"></span>
                        <span>Online</span>
                    </div>
                </div>
            </article>
        </section>

        {{-- ═══════════════════════════════════════════════════
             3. FINANCIAL BETTING STATS (3-Column Strip)
        ═══════════════════════════════════════════════════ --}}
        <section class="rpsv2-stats-strip">
            <div class="rpsv2-stat-col">
                <div class="rpsv2-stat-icon-wrap rpsv2-ico-gold">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 10v2c0 2.21 3.58 4 8 4s8-1.79 8-4v-2c0 2.21-3.58 4-8 4s-8-1.79-8-4zm0-4v2c0 2.21 3.58 4 8 4s8-1.79 8-4V6c0 2.21-3.58 4-8 4S4 8.21 4 6zm8-4C7.58 2 4 3.79 4 6s3.58 4 8 4 8-1.79 8-4-3.58-4-8-4z"/>
                    </svg>
                </div>
                <div class="rpsv2-stat-meta">
                    <span class="rpsv2-stat-label">YOUR BET</span>
                    <strong class="rpsv2-stat-val" data-rf-stake>{{ $stakeLabel }}</strong>
                </div>
            </div>

            <div class="rpsv2-stat-divider"></div>

            <div class="rpsv2-stat-col">
                <div class="rpsv2-stat-icon-wrap rpsv2-ico-gold">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 3s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                    </svg>
                </div>
                <div class="rpsv2-stat-meta">
                    <span class="rpsv2-stat-label">TOTAL POOL</span>
                    <strong class="rpsv2-stat-val" data-rf-pool>{{ $poolLabel }}</strong>
                </div>
            </div>

            <div class="rpsv2-stat-divider"></div>

            <div class="rpsv2-stat-col">
                <div class="rpsv2-stat-icon-wrap rpsv2-ico-trophy">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94.63 1.5 1.98 2.63 3.61 2.96V19H7v2h10v-2h-4v-3.1c1.63-.33 2.98-1.46 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/>
                    </svg>
                </div>
                <div class="rpsv2-stat-meta">
                    <span class="rpsv2-stat-label">YOU CAN WIN <i class="rpsv2-info-ico" title="Payout after 5% platform fee">&#x24D8;</i></span>
                    <strong class="rpsv2-stat-val rpsv2-val--green" data-rf-win>{{ $payoutLabel }}</strong>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════
             4. PLAY MATRIX: "YOUR TURN" (3 Large Glow Cards)
        ═══════════════════════════════════════════════════ --}}
        <section class="rpsv2-matrix-section">
            <div class="rpsv2-matrix-head">
                <div class="rpsv2-matrix-title-wrap">
                    <h2 class="rpsv2-matrix-title">YOUR <span class="rpsv2-title-gold">TURN</span></h2>
                    <p class="rpsv2-matrix-subtitle">Choose your move</p>
                </div>
                <div class="rpsv2-opp-waiting-badge">
                    <div class="rpsv2-opp-icon-ring">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <span class="rpsv2-opp-waiting-text" data-turn-prompt>Opponent waiting</span>
                </div>
            </div>

            {{-- 3 Choice Cards: ROCK, PAPER, SCISSORS --}}
            <div class="rpsv2-choice-grid" data-move-grid>
                {{-- Rock --}}
                <button
                    type="button"
                    class="rpsv2-card-btn rpsv2-card--rock {{ ($s['you']['move'] ?? '') === 'rock' ? 'is-picked' : '' }}"
                    data-move="rock"
                    aria-label="Choose Rock"
                >
                    <img src="{{ $moves['rock'] }}" alt="Rock" class="rpsv2-card-img">
                </button>

                {{-- Paper --}}
                <button
                    type="button"
                    class="rpsv2-card-btn rpsv2-card--paper {{ ($s['you']['move'] ?? '') === 'paper' ? 'is-picked' : '' }}"
                    data-move="paper"
                    aria-label="Choose Paper"
                >
                    <img src="{{ $moves['paper'] }}" alt="Paper" class="rpsv2-card-img">
                </button>

                {{-- Scissors --}}
                <button
                    type="button"
                    class="rpsv2-card-btn rpsv2-card--scissors {{ ($s['you']['move'] ?? '') === 'scissors' ? 'is-picked' : '' }}"
                    data-move="scissors"
                    aria-label="Choose Scissors"
                >
                    <img src="{{ $moves['scissors'] }}" alt="Scissors" class="rpsv2-card-img">
                </button>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════
             5. ROUND HISTORY BAR
        ═══════════════════════════════════════════════════ --}}
        <section class="rpsv2-history-bar" data-history-bar>
            <div class="rpsv2-hist-label-box">
                <svg class="rpsv2-hist-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span class="rpsv2-hist-title">ROUND HISTORY</span>
            </div>

            <div class="rpsv2-hist-chips" data-history-chips>
                {{-- Round 1: Lost --}}
                <div class="rpsv2-round-chip">
                    <span class="rpsv2-chip-r">R1</span>
                    <span class="rpsv2-chip-icon">&#x2702;&#xFE0F;</span>
                    <span class="rpsv2-chip-outcome rpsv2-outcome--loss">L</span>
                </div>

                <div class="rpsv2-chip-sep"></div>

                {{-- Round 2: Won --}}
                <div class="rpsv2-round-chip">
                    <span class="rpsv2-chip-r">R2</span>
                    <span class="rpsv2-chip-icon">&#x1F4C4;</span>
                    <span class="rpsv2-chip-outcome rpsv2-outcome--win">W</span>
                </div>

                <div class="rpsv2-chip-sep"></div>

                {{-- Round 3: Pending --}}
                <div class="rpsv2-round-chip">
                    <span class="rpsv2-chip-r">R3</span>
                    <span class="rpsv2-chip-outcome rpsv2-outcome--empty">--</span>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════
             6. MATCH SUMMARY ACCORDION
        ═══════════════════════════════════════════════════ --}}
        <div class="rpsv2-summary-accordion" data-accordion-box>
            <button type="button" class="rpsv2-accordion-btn" data-accordion-toggle aria-expanded="false">
                <div class="rpsv2-accordion-left">
                    <svg class="rpsv2-bar-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 9h4v11H4zm6-5h4v16h-4zm6 8h4v8h-4z"/>
                    </svg>
                    <strong>MATCH SUMMARY</strong>
                </div>
                <svg class="rpsv2-accordion-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>

            <div class="rpsv2-accordion-content" data-accordion-panel hidden>
                <div class="rpsv2-sum-grid">
                    <div class="rpsv2-sum-item">
                        <span>Stake per player</span>
                        <strong>{{ $stakeLabel }}</strong>
                    </div>
                    <div class="rpsv2-sum-item">
                        <span>Combined Pool</span>
                        <strong>{{ $poolLabel }}</strong>
                    </div>
                    <div class="rpsv2-sum-item">
                        <span>Platform Fee (5%)</span>
                        <strong>${{ number_format($stakeAmt * 0.1, 2) }}</strong>
                    </div>
                    <div class="rpsv2-sum-item rpsv2-sum--win">
                        <span>Winner Payout</span>
                        <strong>{{ $payoutLabel }}</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════
             7. BOTTOM ACTION GRID (4 Buttons)
        ═══════════════════════════════════════════════════ --}}
        <footer class="rpsv2-action-grid">
            <button type="button" class="rpsv2-action-btn rpsv2-action--shield">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Fair &amp; Secure</span>
                <span class="rpsv2-action-chevron">&rsaquo;</span>
            </button>

            <button type="button" class="rpsv2-action-btn" title="Report">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                    <line x1="4" y1="22" x2="4" y2="15"></line>
                </svg>
                <span>Report</span>
            </button>

            <button type="button" class="rpsv2-action-btn" title="Rules">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span>Rules</span>
            </button>

            <button type="button" class="rpsv2-action-btn rpsv2-action--settings is-active" title="Settings">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                </svg>
                <span>Settings</span>
            </button>
        </footer>

        {{-- Hidden legacy components for JS backward compatibility --}}
        <div hidden style="display:none !important;">
            <div data-phase-label></div>
            <div data-timer></div>
            <div data-timer-caption></div>
            <div data-status-label></div>
            <div data-your-move-state></div>
            <div data-opp-move-state></div>
            <div data-round-banner></div>
            <div data-rr-dots></div>
            <div data-opp-slot></div>
            <div data-history></div>
            <div data-ready-overlay></div>
            <div data-rpsv-toast></div>
            <div data-end-banner></div>
            <span data-sum-stake></span>
            <span data-sum-pool></span>
            <span data-sum-fee></span>
            <span data-sum-win></span>
        </div>
    </div>
</div>
@endsection

@push('styles')
@vite(['resources/css/rpsv.css'])
@endpush

@push('scripts')
@vite(['resources/js/rpsv.js'])
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('[data-accordion-toggle]');
    const panel = document.querySelector('[data-accordion-panel]');
    if (toggle && panel) {
        toggle.addEventListener('click', function () {
            const isHidden = panel.hidden;
            panel.hidden = !isHidden;
            toggle.setAttribute('aria-expanded', String(isHidden));
            toggle.querySelector('.rpsv2-accordion-arrow')?.classList.toggle('is-open', isHidden);
        });
    }
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'Investment Schemes - One-Contract')
@section('meta_description', 'One-Contract offers five unique Investment Schemes — Lion, Eagle, Man, Ox, and Gorilla — each with fixed USDT reward rates and transparent 7-day payout cycles.')

@push('styles')
    @vite(['resources/css/investment-clubs.css'])
@endpush

@section('content')
<div class="ic-page">

    {{-- ═══════════════════════════════════════════════
         HERO SECTION
    ═══════════════════════════════════════════════ --}}
    <section class="ic-hero" aria-label="Investment Schemes Hero">
        <div class="ic-hero-bg" aria-hidden="true"></div>
        <div class="container">
            <div class="ic-hero-content">
                <span class="section-tag">
                    <i class="fa-solid fa-layer-group me-1"></i>
                    Investment Schemes
                </span>
                <h1 class="ic-hero-title">Choose Your Investment Scheme</h1>
                <p class="ic-hero-tagline">
                    Grow <span class="dot">•</span>
                    Earn <span class="dot">•</span>
                    Withdraw
                </p>
                <p class="ic-hero-desc">
                    One-Contract offers five unique Investment Schemes, each designed for a specific trading
                    strategy. Pick the one that fits your goals and start earning fixed rewards on your USDT.
                </p>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════
         CLUBS GRID SECTION
    ═══════════════════════════════════════════════ --}}
    <section class="ic-section-dark" id="clubs" aria-label="Investment Scheme Options">
        <div class="container">
            <div class="ic-section-head">
                <span class="section-tag">
                    <i class="fa-solid fa-star me-1"></i>
                    Our Schemes
                </span>
                <h2 class="ic-section-title">Five Schemes. One Platform.</h2>
                <p class="ic-section-subtitle">
                    Select the scheme that matches your strategy and let your USDT earn for you on autopilot.
                </p>
            </div>

            <div class="ic-clubs-grid">

                {{-- Card 1: Lion Investment Scheme --}}
                <article class="ic-club-card">
                    <div class="ic-club-header">
                        <div class="ic-club-icon">
                            <img src="{{ asset('images/clubs/binary.png') }}" alt="Lion Investment Scheme icon" loading="lazy">
                        </div>
                        <h3 class="ic-club-name">Lion Investment Scheme</h3>
                    </div>
                    <div class="ic-club-body">
                        <div class="ic-club-rate">
                            <span class="ic-rate-label">Daily Reward Rate</span>
                            <div>
                                <span class="ic-rate-value">0.1</span>
                                <span class="ic-rate-unit">%</span>
                            </div>
                        </div>
                        <div class="ic-club-meta">
                            <div class="ic-meta-row">
                                <span class="ic-meta-key"><i class="fa-regular fa-clock me-1"></i> Payout</span>
                                <span class="ic-meta-val">Every 35 Days</span>
                            </div>
                            <div class="ic-meta-row">
                                <span class="ic-meta-key"><i class="fa-solid fa-wallet me-1"></i> Min. Invest</span>
                                <span class="ic-meta-val">$1 USDT</span>
                            </div>
                        </div>
                        <a href="{{ auth()->check() ? route('user.dashboard') : route('register') }}"
                           class="ic-btn-join">
                            Join Now <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </article>

                {{-- Card 2: Eagle Investment Scheme --}}
                <article class="ic-club-card">
                    <div class="ic-club-header">
                        <div class="ic-club-icon">
                            <img src="{{ asset('images/clubs/crypto.png') }}" alt="Eagle Investment Scheme icon" loading="lazy">
                        </div>
                        <h3 class="ic-club-name">Eagle Investment Scheme</h3>
                    </div>
                    <div class="ic-club-body">
                        <div class="ic-club-rate">
                            <span class="ic-rate-label">Daily Reward Rate</span>
                            <div>
                                <span class="ic-rate-value">0.1</span>
                                <span class="ic-rate-unit">%</span>
                            </div>
                        </div>
                        <div class="ic-club-meta">
                            <div class="ic-meta-row">
                                <span class="ic-meta-key"><i class="fa-regular fa-clock me-1"></i> Payout</span>
                                <span class="ic-meta-val">Every 35 Days</span>
                            </div>
                            <div class="ic-meta-row">
                                <span class="ic-meta-key"><i class="fa-solid fa-wallet me-1"></i> Min. Invest</span>
                                <span class="ic-meta-val">$1 USDT</span>
                            </div>
                        </div>
                        <a href="{{ auth()->check() ? route('user.dashboard') : route('register') }}"
                           class="ic-btn-join">
                            Join Now <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </article>

                {{-- Card 3: Man Investment Scheme --}}
                <article class="ic-club-card">
                    <div class="ic-club-header">
                        <div class="ic-club-icon">
                            <img src="{{ asset('images/clubs/forex.png') }}" alt="Man Investment Scheme icon" loading="lazy">
                        </div>
                        <h3 class="ic-club-name">Man Investment Scheme</h3>
                    </div>
                    <div class="ic-club-body">
                        <div class="ic-club-rate">
                            <span class="ic-rate-label">Daily Reward Rate</span>
                            <div>
                                <span class="ic-rate-value">0.1</span>
                                <span class="ic-rate-unit">%</span>
                            </div>
                        </div>
                        <div class="ic-club-meta">
                            <div class="ic-meta-row">
                                <span class="ic-meta-key"><i class="fa-regular fa-clock me-1"></i> Payout</span>
                                <span class="ic-meta-val">Every 35 Days</span>
                            </div>
                            <div class="ic-meta-row">
                                <span class="ic-meta-key"><i class="fa-solid fa-wallet me-1"></i> Min. Invest</span>
                                <span class="ic-meta-val">$1 USDT</span>
                            </div>
                        </div>
                        <a href="{{ auth()->check() ? route('user.dashboard') : route('register') }}"
                           class="ic-btn-join">
                            Join Now <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </article>

                {{-- Card 4: Ox Investment Scheme --}}
                <article class="ic-club-card">
                    <div class="ic-club-header">
                        <div class="ic-club-icon">
                            <img src="{{ asset('images/clubs/casino.png') }}" alt="Ox Investment Scheme icon" loading="lazy">
                        </div>
                        <h3 class="ic-club-name">Ox Investment Scheme</h3>
                    </div>
                    <div class="ic-club-body">
                        <div class="ic-club-rate">
                            <span class="ic-rate-label">Daily Reward Rate</span>
                            <div>
                                <span class="ic-rate-value">0.1</span>
                                <span class="ic-rate-unit">%</span>
                            </div>
                        </div>
                        <div class="ic-club-meta">
                            <div class="ic-meta-row">
                                <span class="ic-meta-key"><i class="fa-regular fa-clock me-1"></i> Payout</span>
                                <span class="ic-meta-val">Every 35 Days</span>
                            </div>
                            <div class="ic-meta-row">
                                <span class="ic-meta-key"><i class="fa-solid fa-wallet me-1"></i> Min. Invest</span>
                                <span class="ic-meta-val">$1 USDT</span>
                            </div>
                        </div>
                        <a href="{{ auth()->check() ? route('user.dashboard') : route('register') }}"
                           class="ic-btn-join">
                            Join Now <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </article>

                {{-- Card 5: Gorilla Investment Scheme --}}
                <article class="ic-club-card">
                    <div class="ic-club-header">
                        <div class="ic-club-icon">
                            <img src="{{ asset('images/clubs/poly.png') }}" alt="Gorilla Investment Scheme icon" loading="lazy">
                        </div>
                        <h3 class="ic-club-name">Gorilla Investment Scheme</h3>
                    </div>
                    <div class="ic-club-body">
                        <div class="ic-club-rate">
                            <span class="ic-rate-label">Daily Reward Rate</span>
                            <div>
                                <span class="ic-rate-value">0.1</span>
                                <span class="ic-rate-unit">%</span>
                            </div>
                        </div>
                        <div class="ic-club-meta">
                            <div class="ic-meta-row">
                                <span class="ic-meta-key"><i class="fa-regular fa-clock me-1"></i> Payout</span>
                                <span class="ic-meta-val">Every 35 Days</span>
                            </div>
                            <div class="ic-meta-row">
                                <span class="ic-meta-key"><i class="fa-solid fa-wallet me-1"></i> Min. Invest</span>
                                <span class="ic-meta-val">$1 USDT</span>
                            </div>
                        </div>
                        <a href="{{ auth()->check() ? route('user.dashboard') : route('register') }}"
                           class="ic-btn-join">
                            Join Now <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </article>

            </div>{{-- /.ic-clubs-grid --}}
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════
         HOW IT WORKS SECTION
    ═══════════════════════════════════════════════ --}}
    <section class="ic-section-light" id="how-it-works" aria-label="How Investment Schemes Work">
        <div class="container">
            <div class="ic-section-head">
                <span class="section-tag">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Simple Process
                </span>
                <h2 class="ic-section-title ic-section-title--dark">How Investment Schemes Work</h2>
                <p class="ic-section-subtitle ic-section-subtitle--dark">
                    Four easy steps to start earning fixed USDT rewards every 35 days.
                </p>
            </div>

            <div class="ic-steps-grid">

                <div class="ic-step-item">
                    <div class="ic-step-num">1</div>
                    <div class="ic-step-icon">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h3 class="ic-step-title">Register &amp; Deposit USDT</h3>
                    <p class="ic-step-desc">
                        Create your free account and fund your wallet with USDT using our secure deposit gateway.
                    </p>
                </div>

                <div class="ic-step-item">
                    <div class="ic-step-num">2</div>
                    <div class="ic-step-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <h3 class="ic-step-title">Choose Your Scheme</h3>
                    <p class="ic-step-desc">
                        Browse the five Investment Schemes and select the one that matches your goals and risk preference.
                    </p>
                </div>

                <div class="ic-step-item">
                    <div class="ic-step-num">3</div>
                    <div class="ic-step-icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h3 class="ic-step-title">Earn Fixed Rewards</h3>
                    <p class="ic-step-desc">
                        Your investment starts generating fixed-rate USDT rewards automatically — no active trading needed.
                    </p>
                </div>

                <div class="ic-step-item">
                    <div class="ic-step-num">4</div>
                    <div class="ic-step-icon">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </div>
                    <h3 class="ic-step-title">Withdraw Every 35 Days</h3>
                    <p class="ic-step-desc">
                        Receive your rewards on schedule and withdraw your principal once the cycle completes.
                    </p>
                </div>

            </div>{{-- /.ic-steps-grid --}}
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════
         BENEFITS SECTION
    ═══════════════════════════════════════════════ --}}
    <section class="ic-section-dark" id="benefits" aria-label="Why Join an Investment Scheme">
        <div class="container">
            <div class="ic-section-head">
                <span class="section-tag">
                    <i class="fa-solid fa-trophy me-1"></i>
                    Benefits
                </span>
                <h2 class="ic-section-title">Why Join an Investment Scheme?</h2>
                <p class="ic-section-subtitle">
                    Everything you need to grow your USDT with confidence and clarity.
                </p>
            </div>

            <div class="ic-benefits-grid">

                <div class="ic-benefit-item">
                    <div class="ic-benefit-icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div>
                        <h3 class="ic-benefit-title">Fixed Reward Rates</h3>
                        <p class="ic-benefit-desc">
                            Know exactly what you earn — no market volatility, no surprises. Fixed returns every cycle.
                        </p>
                    </div>
                </div>

                <div class="ic-benefit-item">
                    <div class="ic-benefit-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h3 class="ic-benefit-title">Secure &amp; Transparent</h3>
                        <p class="ic-benefit-desc">
                            Your deposits are handled on a secure, audited platform with full transaction transparency.
                        </p>
                    </div>
                </div>

                <div class="ic-benefit-item">
                    <div class="ic-benefit-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <h3 class="ic-benefit-title">3-Level Referral Program</h3>
                        <p class="ic-benefit-desc">
                            Earn 5% on Level 1, 3% on Level 2, and 2% on Level 3 referrals — passive income stacked.
                        </p>
                    </div>
                </div>

                <div class="ic-benefit-item">
                    <div class="ic-benefit-icon">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <h3 class="ic-benefit-title">Withdraw Your Principal</h3>
                        <p class="ic-benefit-desc">
                            Your principal is returned to you in full once the investment cycle ends — no lock-ins.
                        </p>
                    </div>
                </div>

                <div class="ic-benefit-item">
                    <div class="ic-benefit-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <h3 class="ic-benefit-title">Consistent Payout Cycles</h3>
                        <p class="ic-benefit-desc">
                            Rewards land every 35 days like clockwork — plan your finances with total confidence.
                        </p>
                    </div>
                </div>

                <div class="ic-benefit-item">
                    <div class="ic-benefit-icon">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <div>
                        <h3 class="ic-benefit-title">24/7 Dedicated Support</h3>
                        <p class="ic-benefit-desc">
                            Our support team is always available to assist you with any questions or issues.
                        </p>
                    </div>
                </div>

            </div>{{-- /.ic-benefits-grid --}}
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════
         CTA SECTION
    ═══════════════════════════════════════════════ --}}
    <section class="ic-cta" aria-label="Call to Action">
        <div class="ic-cta-bg" aria-hidden="true"></div>
        <div class="container">
            <div class="ic-cta-content">
                <span class="section-tag">
                    <i class="fa-solid fa-rocket me-1"></i>
                    Get Started
                </span>
                <h2 class="ic-cta-title">Ready to Start Earning?</h2>
                <p class="ic-cta-desc">
                    Join thousands of members already growing their USDT with One-Contract Investment Schemes.
                    No trading skills required.
                </p>
                <a href="{{ route('register') }}" class="ic-btn-cta" id="ic-cta-register-btn">
                    <i class="fa-solid fa-arrow-right"></i>
                    Get Started Today
                </a>
            </div>
        </div>
    </section>

</div>{{-- /.ic-page --}}
@endsection

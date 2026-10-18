@extends('layouts.app')

@section('title', 'How It Works — One-Contract™')
@section('meta_description', 'Learn how One-Contract™ works — deposit USDT, earn 1% daily rewards, invite friends, and withdraw every 7 days.')

@push('styles')
    @vite(['resources/css/how-it-works.css'])
@endpush

@section('content')
@php
    $whyItems = [
        ['label' => 'Fixed 1% Daily Reward', 'color' => 'blue', 'icon' => 'chart'],
        ['label' => 'Simple Interest (No Compounding)', 'color' => 'green', 'icon' => 'percent'],
        ['label' => 'Unlimited Earning Period While Capital Remains Active', 'color' => 'purple', 'icon' => 'infinity'],
        ['label' => 'Withdrawal Requests Every 7 Days', 'color' => 'blue', 'icon' => 'calendar'],
        ['label' => 'Instant Reward Withdrawals When Treasury Liquidity Is Available', 'color' => 'green', 'icon' => 'bolt'],
        ['label' => 'Principal Processed Within 72 Hours', 'color' => 'orange', 'icon' => 'clock'],
        ['label' => 'Three-Level Referral Program', 'color' => 'purple', 'icon' => 'users'],
        ['label' => 'Secure & User-Friendly Dashboard', 'color' => 'green', 'icon' => 'shield-check'],
        ['label' => 'Transparent Transaction History', 'color' => 'blue', 'icon' => 'monitor'],
        ['label' => 'Professional Treasury Management', 'color' => 'orange', 'icon' => 'building'],
    ];
@endphp
<main class="hiw-page">
    {{-- Hero --}}
    <section class="hiw-hero">
        <div class="hiw-hero-bg"></div>
        <div class="container hiw-hero-grid">
            <div class="hiw-hero-content">
                <span class="section-tag">How One-Contract™ Works</span>
                <h1 class="hiw-hero-title">A Simple Way to Grow Your USDT</h1>
                <p class="hiw-hero-desc">
                    One-Contract™ provides a straightforward process that allows participants to deposit
                    USDT, earn fixed daily rewards, and withdraw their earnings according to the platform's
                    withdrawal policy.
                </p>
            </div>
            <div class="hiw-hero-visual">
                <img
                    src="{{ asset('images/how-it-works/hero-phone.png') }}"
                    alt="One-Contract mobile dashboard — USDT balance and rewards"
                    class="hiw-hero-image"
                    width="560"
                    height="420"
                    loading="eager"
                >
            </div>
        </div>
    </section>

    <div class="hiw-light">
        {{-- Steps --}}
        <section class="hiw-steps">
            <div class="container hiw-steps-container">
                <div class="hiw-steps-grid hiw-steps-grid--4">
                    <article class="hiw-step-card hiw-step-card--account">
                        <div class="hiw-step-head">
                            <span class="hiw-step-num blue">1</span>
                            <h2>Create Your Account</h2>
                        </div>
                        <div class="hiw-step-icon-wrap">
                            <div class="hiw-step-icon blue">@include('how-it-works.partials.step-icon', ['name' => 'user'])</div>
                            <span class="hiw-step-badge green">✓</span>
                        </div>
                        <p class="hiw-step-copy hiw-copy-primary">Register a free One-Contract™ account using your email address and create a secure password.</p>
                        <p class="hiw-step-copy hiw-copy-secondary">After registration, log in to access your personal dashboard.</p>
                    </article>

                    <article class="hiw-step-card hiw-step-card--deposit">
                        <div class="hiw-step-head">
                            <span class="hiw-step-num blue">2</span>
                            <h2>Deposit USDT</h2>
                        </div>
                        <div class="hiw-step-icon-wrap">
                            <div class="hiw-step-icon green">@include('how-it-works.partials.step-icon', ['name' => 'wallet'])</div>
                        </div>
                        <p class="hiw-step-copy hiw-copy-primary">From your dashboard, generate your deposit address and transfer USDT to the platform.</p>
                        <p class="hiw-step-copy hiw-copy-secondary">Once your transaction is confirmed, your deposit becomes active and your investment starts earning rewards immediately.</p>
                    </article>

                    <article class="hiw-step-card hiw-step-card--earn">
                        <div class="hiw-step-head">
                            <span class="hiw-step-num blue">3</span>
                            <h2>Start Earning Daily Rewards</h2>
                        </div>
                        <div class="hiw-step-icon-wrap">
                            <div class="hiw-step-icon purple">@include('how-it-works.partials.step-icon', ['name' => 'chart'])</div>
                            <span class="hiw-step-badge teal">₮</span>
                        </div>
                        <p class="hiw-step-copy hiw-copy-primary">Your active deposit earns a fixed 1% daily reward.</p>
                        <p class="hiw-step-copy hiw-copy-secondary">Rewards are calculated every 24 hours using simple interest, meaning only your original deposited capital earns rewards. Your rewards are not compounded.</p>
                        <div class="hiw-example hiw-example--purple">
                            <strong>Example</strong>
                            <p>Deposit: 1,000 USDT<br>Daily Reward: 1,000 × 1% = <span>10 USDT per day</span><br>The daily reward remains 10 USDT for as long as your 1,000 USDT remains active.</p>
                        </div>
                    </article>

                    <article class="hiw-step-card hiw-step-card--track">
                        <div class="hiw-step-head">
                            <span class="hiw-step-num blue">4</span>
                            <h2>Track Your Earnings</h2>
                        </div>
                        <div class="hiw-step-icon-wrap">
                            <div class="hiw-step-icon blue">@include('how-it-works.partials.step-icon', ['name' => 'monitor'])</div>
                        </div>
                        <p class="hiw-step-copy hiw-copy-primary">Your dashboard allows you to monitor your investment in real time.</p>
                        <p class="hiw-step-copy hiw-copy-label">You can view:</p>
                        <ul class="hiw-checklist">
                            <li><span class="hiw-check">✓</span> Active Deposit</li>
                            <li><span class="hiw-check">✓</span> Daily Reward (1%)</li>
                            <li><span class="hiw-check">✓</span> Total Rewards Earned</li>
                            <li><span class="hiw-check">✓</span> Available Reward Balance</li>
                            <li><span class="hiw-check">✓</span> Referral Earnings</li>
                            <li><span class="hiw-check">✓</span> Transaction History</li>
                            <li><span class="hiw-check">✓</span> Withdrawal Countdown</li>
                            <li><span class="hiw-check">✓</span> Next Withdrawal Date</li>
                        </ul>
                    </article>
                </div>

                <div class="hiw-steps-grid hiw-steps-grid--3">
                    <article class="hiw-step-card hiw-step-card--invite">
                        <div class="hiw-step-head">
                            <span class="hiw-step-num blue">5</span>
                            <h2>Invite Friends</h2>
                        </div>
                        <div class="hiw-step-icon-wrap">
                            <div class="hiw-step-icon orange">@include('how-it-works.partials.step-icon', ['name' => 'users'])</div>
                        </div>
                        <p class="hiw-step-copy hiw-copy-primary">Share your referral link and earn referral commissions whenever your referrals earn Investment Scheme rewards or Prediction gaming rewards.</p>
                        <div class="hiw-referral">
                            <strong>Referral Structure</strong>
                            <ul>
                                <li>Level 1: <span>5%</span></li>
                                <li>Level 2: <span>3%</span></li>
                                <li>Level 3: <span>2%</span></li>
                            </ul>
                        </div>
                        <p class="hiw-step-copy hiw-copy-secondary">Referral commissions are credited automatically to your account.</p>
                    </article>

                    <article class="hiw-step-card hiw-step-card--withdraw">
                        <div class="hiw-step-head">
                            <span class="hiw-step-num blue">6</span>
                            <h2>Withdraw Your Rewards</h2>
                        </div>
                        <div class="hiw-step-icon-wrap">
                            <div class="hiw-step-icon green">@include('how-it-works.partials.step-icon', ['name' => 'withdraw'])</div>
                        </div>
                        <p class="hiw-step-copy hiw-copy-primary">Withdrawals are available once every 7 days. The countdown begins from:</p>
                        <ul class="hiw-checklist hiw-checklist--green">
                            <li><span class="hiw-check green">✓</span> Your deposit date, or</li>
                            <li><span class="hiw-check green">✓</span> Your last successful withdrawal.</li>
                        </ul>
                        <p class="hiw-step-copy hiw-copy-secondary">If sufficient treasury liquidity is available, reward withdrawals are processed instantly.</p>
                        <p class="hiw-step-copy hiw-copy-tertiary">If liquidity is temporarily unavailable, your request remains pending until funds become available.</p>
                    </article>

                    <article class="hiw-step-card hiw-step-card--capital">
                        <div class="hiw-step-head">
                            <span class="hiw-step-num blue">7</span>
                            <h2>Withdraw Your Capital</h2>
                        </div>
                        <div class="hiw-step-icon-wrap">
                            <div class="hiw-step-icon blue">@include('how-it-works.partials.step-icon', ['name' => 'building'])</div>
                            <span class="hiw-step-badge teal">₮</span>
                        </div>
                        <p class="hiw-step-copy hiw-copy-primary">Your deposited capital remains active until you decide to withdraw it.</p>
                        <p class="hiw-step-copy hiw-copy-secondary">After the applicable 7-day withdrawal interval, you may request withdrawal of your principal.</p>
                        <p class="hiw-step-copy hiw-copy-tertiary">Capital withdrawal requests are processed within 72 hours.</p>
                        <p class="hiw-step-copy hiw-copy-label">Once your principal has been paid:</p>
                        <ul class="hiw-checklist hiw-checklist--green">
                            <li><span class="hiw-check green">✓</span> Your investment is marked as completed.</li>
                            <li><span class="hiw-check green">✓</span> Daily reward generation stops.</li>
                            <li><span class="hiw-check green">✓</span> You may create a new investment at any time by making another deposit.</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        {{-- Why Choose --}}
        <section class="hiw-why">
            <div class="container">
                <div class="hiw-section-head">
                    <div class="hiw-head-icon blue">@include('how-it-works.partials.step-icon', ['name' => 'star'])</div>
                    <h2>Why Choose One-Contract™</h2>
                </div>
                <div class="hiw-why-grid">
                    @foreach ($whyItems as $w)
                        <div class="hiw-why-item">
                            <div class="hiw-why-icon {{ $w['color'] }}">
                                @include('how-it-works.partials.step-icon', ['name' => $w['icon']])
                            </div>
                            <span>{{ $w['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Important Information --}}
        <section class="hiw-info">
            <div class="container">
                <div class="hiw-section-head">
                    <div class="hiw-head-icon blue">@include('how-it-works.partials.step-icon', ['name' => 'info'])</div>
                    <h2>Important Information</h2>
                </div>
                <div class="hiw-info-grid">
                    <div class="hiw-info-col">
                        <div class="hiw-info-item">
                            <div class="hiw-info-icon green"><span>18+</span></div>
                            <div>
                                <h3>Participants must be 18 years or older.</h3>
                            </div>
                        </div>
                        <div class="hiw-info-item">
                            <div class="hiw-info-icon blue">@include('how-it-works.partials.step-icon', ['name' => 'globe'])</div>
                            <div>
                                <h3>Participation must be legal in your country or jurisdiction.</h3>
                            </div>
                        </div>
                        <div class="hiw-info-item">
                            <div class="hiw-info-icon orange">@include('how-it-works.partials.step-icon', ['name' => 'warn'])</div>
                            <div>
                                <h3>Digital asset activities involve risk.</h3>
                            </div>
                        </div>
                    </div>
                    <div class="hiw-info-col">
                        <div class="hiw-info-item">
                            <div class="hiw-info-icon purple">@include('how-it-works.partials.step-icon', ['name' => 'building'])</div>
                            <div>
                                <h3>One-Contract™ is currently undergoing company registration in the Republic of Panama and is preparing for its official launch.</h3>
                            </div>
                        </div>
                        <div class="hiw-info-item">
                            <div class="hiw-info-icon blue">@include('how-it-works.partials.step-icon', ['name' => 'shield'])</div>
                            <div>
                                <h3>Where licenses or regulatory approvals are required for specific services, those services will only be offered after the necessary legal requirements have been satisfied.</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>
@endsection

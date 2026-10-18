@extends('layouts.app')

@section('title', 'Features — One-Contract™')
@section('meta_description', 'Explore One-Contract™ features — 1% daily rewards, USDT deposits, 7-day withdrawals, referral program, real-time dashboard, and more.')

@push('styles')
    @vite(['resources/css/features.css'])
@endpush

@section('content')
@php
    $features = [
        ['n' => 1, 'title' => 'Fixed Daily Reward', 'color' => 'blue', 'icon' => 'chart', 'desc' => 'Earn a fixed 1% daily reward on your active USDT deposit using a simple non-compounding interest model.', 'list' => null, 'footnote' => null],
        ['n' => 2, 'title' => 'Simple Interest System', 'color' => 'green', 'icon' => 'percent', 'desc' => 'Rewards are calculated only on your original deposited capital. Previously earned rewards do not generate additional rewards.', 'list' => null, 'footnote' => null],
        ['n' => 3, 'title' => 'Unlimited Investment Duration', 'color' => 'purple', 'icon' => 'infinity', 'desc' => 'There is no fixed contract period. Your investment continues earning daily rewards until you choose to withdraw your principal.', 'list' => null, 'footnote' => null],
        ['n' => 4, 'title' => 'USDT Deposits', 'color' => 'teal', 'icon' => 'usdt', 'desc' => 'Deposit USDT quickly and securely to activate your investment and begin earning daily rewards.', 'list' => null, 'footnote' => null],
        ['n' => 5, 'title' => 'Reward Withdrawals Every 7 Days', 'color' => 'blue', 'icon' => 'calendar', 'desc' => 'Submit reward withdrawal requests once every seven (7) days based on your deposit date or last successful withdrawal.', 'list' => null, 'footnote' => null],
        ['n' => 6, 'title' => 'Instant Reward Withdrawals', 'color' => 'green', 'icon' => 'bolt', 'desc' => 'Reward withdrawals are processed instantly whenever sufficient treasury liquidity is available.', 'list' => null, 'footnote' => null],
        ['n' => 7, 'title' => 'Principal Withdrawal Within 72 Hours', 'color' => 'blue', 'icon' => 'clock', 'desc' => 'Principal withdrawal requests are processed within 72 hours after submission and verification.', 'list' => null, 'footnote' => null],
        ['n' => 8, 'title' => 'Three-Level Referral Program', 'color' => 'purple', 'icon' => 'users', 'desc' => 'Earn additional income through referrals. Level 1: 5%, Level 2: 3%, Level 3: 2%. Referral commissions apply on Investment Scheme rewards and Prediction gaming and are automatically credited to your account.', 'list' => null, 'footnote' => null],
        ['n' => 9, 'title' => 'Real-Time Dashboard', 'color' => 'blue', 'icon' => 'monitor-chart', 'desc' => null, 'list' => ['Active Investment', 'Daily Rewards', 'Total Rewards Earned', 'Referral Commissions', 'Withdrawal Status', 'Transaction History', 'Withdrawal Countdown Timer'], 'footnote' => null],
        ['n' => 10, 'title' => 'Secure Treasury Management', 'color' => 'green', 'icon' => 'shield-check', 'desc' => 'The platform uses treasury management procedures to monitor liquidity and support timely processing of eligible withdrawals.', 'list' => null, 'footnote' => null],
        ['n' => 11, 'title' => 'Transaction History', 'color' => 'blue', 'icon' => 'document-clock', 'desc' => null, 'list' => ['Deposits', 'Daily Reward Credits', 'Referral Commissions', 'Reward Withdrawals', 'Principal Withdrawals'], 'footnote' => null],
        ['n' => 12, 'title' => 'User Notifications', 'color' => 'purple', 'icon' => 'bell', 'desc' => null, 'list' => ['Deposit confirmations', 'Daily reward credits', 'Referral commissions', 'Withdrawal requests', 'Successful withdrawals', 'Account updates'], 'footnote' => null],
        ['n' => 13, 'title' => 'Advanced Security', 'color' => 'green', 'icon' => 'lock-shield', 'desc' => null, 'list' => ['Encrypted communications', 'Two-factor authentication', 'Transaction verification', 'Real-time monitoring', 'Secure treasury wallets'], 'footnote' => null],
        ['n' => 14, 'title' => 'Responsive Design', 'color' => 'blue', 'icon' => 'devices', 'desc' => null, 'list' => ['Desktop computers', 'Laptops', 'Tablets', 'Mobile devices'], 'footnote' => null],
        ['n' => 15, 'title' => 'Professional Admin Dashboard', 'color' => 'purple', 'icon' => 'gear', 'desc' => null, 'list' => ['Total Capital Invested', 'Total Rewards Paid Out', 'Total Referral Rewards', 'User Management', 'Deposit Management', 'Withdrawal Management', 'Financial Analysis', 'Audit Logs'], 'footnote' => null],
        ['n' => 16, 'title' => 'Transparent Operations', 'color' => 'blue', 'icon' => 'eye', 'desc' => 'Participants have real-time visibility into their investments, withdrawals, and referral earnings through a clean and user-friendly interface.', 'list' => null, 'footnote' => null],
        ['n' => 17, 'title' => 'Global Accessibility', 'color' => 'green', 'icon' => 'globe', 'desc' => 'One-Contract™ is designed for eligible users worldwide, subject to applicable local laws and platform availability.', 'list' => null, 'footnote' => null],
        ['n' => 18, 'title' => 'Ongoing Platform Development', 'color' => 'purple', 'icon' => 'rocket', 'desc' => 'One-Contract™ is continuously improving its technology, security and operational systems to provide a reliable and scalable digital asset platform.', 'list' => null, 'footnote' => null],
    ];

    $whyItems = [
        ['label' => 'Fixed 1% Daily Reward', 'color' => 'blue', 'icon' => 'chart'],
        ['label' => 'Simple Interest (No Compounding)', 'color' => 'green', 'icon' => 'percent'],
        ['label' => 'Unlimited Earning Period', 'color' => 'purple', 'icon' => 'infinity'],
        ['label' => 'Withdraw Every 7 Days', 'color' => 'blue', 'icon' => 'calendar'],
        ['label' => 'Instant Reward Withdrawals (Subject to Treasury Liquidity)', 'color' => 'green', 'icon' => 'bolt'],
        ['label' => 'Principal Processed Within 72 Hours', 'color' => 'blue', 'icon' => 'clock'],
        ['label' => 'Three-Level Referral Program', 'color' => 'purple', 'icon' => 'users'],
        ['label' => 'Secure USDT Deposits', 'color' => 'green', 'icon' => 'shield-check'],
        ['label' => 'Real-Time Dashboard', 'color' => 'blue', 'icon' => 'monitor'],
        ['label' => 'Professional Treasury Management', 'color' => 'green', 'icon' => 'building'],
    ];
@endphp

<main class="feat-page">
    <section class="feat-hero">
        <div class="feat-hero-bg"></div>
        <div class="container feat-hero-grid">
            <div class="feat-hero-content">
                <span class="section-tag">Features</span>
                <h1 class="feat-hero-title">One-Contract™ Features</h1>
                <p class="feat-hero-tagline">Powerful Features Built for Simplicity, Security &amp; Transparency</p>
                <p class="feat-hero-desc">
                    One-Contract™ combines an intuitive user experience with professional treasury
                    management to provide a secure and reliable digital asset platform.
                </p>
            </div>
            <div class="feat-hero-visual">
                <img
                    src="{{ asset('images/features/hero-laptop.png') }}"
                    alt="One-Contract dashboard with USDT rewards and security"
                    class="feat-hero-image"
                    width="560"
                    height="420"
                    loading="eager"
                >
            </div>
        </div>
    </section>

    <div class="feat-light">
        <section class="feat-grid-section">
            <div class="container feat-grid-container">
                <div class="feat-cards-grid">
                    @foreach ($features as $f)
                        <article class="feat-card">
                            <div class="feat-card-head">
                                <span class="feat-card-num {{ $f['color'] }}">{{ $f['n'] }}</span>
                                <h2>{{ $f['title'] }}</h2>
                            </div>
                            <div class="feat-card-icon {{ $f['color'] }}">
                                @include('features.partials.icon', ['name' => $f['icon']])
                            </div>
                            @if ($f['desc'])
                                <p>{{ $f['desc'] }}</p>
                            @endif
                            @if ($f['list'])
                                <ul class="feat-card-list">
                                    @foreach ($f['list'] as $item)
                                        <li><span class="feat-check">✓</span> {{ $item }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (!empty($f['footnote']))
                                <p class="feat-card-footnote">{{ $f['footnote'] }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="feat-why">
            <div class="container feat-why-container">
                <div class="feat-why-inner">
                    <h2 class="feat-why-title">Why Choose One-Contract™?</h2>
                    <div class="feat-why-grid">
                        @foreach ($whyItems as $w)
                            <div class="feat-why-item">
                                <div class="feat-why-icon {{ $w['color'] }}">
                                    @include('features.partials.icon', ['name' => $w['icon']])
                                </div>
                                <span>{{ $w['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>
@endsection

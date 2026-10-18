@extends('layouts.app')

@section('title', 'FAQ — One-Contract™')
@section('meta_description', 'Frequently asked questions about One-Contract™ — deposits, daily rewards, withdrawals, referral program, and account security.')

@push('styles')
    @vite(['resources/css/faq-page.css'])
@endpush

@section('content')
@php
    $categories = [
        [
            'title' => 'Withdrawals',
            'icon' => 'wallet',
            'color' => 'teal',
            'items' => [
                ['q' => 'What is One-Contract™?', 'a' => 'One-Contract™ is a smart USDT investment platform offering fixed 1% daily rewards, secure withdrawals, and transparent treasury management. Investors can earn passive income while maintaining full control of their capital.'],
                ['q' => 'How do I create an account?', 'a' => 'Click "Get Started" to register with your email address and create a secure password. After email verification, your account is active and ready to use.'],
                ['q' => 'What is the minimum deposit?', 'a' => 'You can start with as little as 3 USDT. There is no maximum limit on deposits.'],
                ['q' => 'Which cryptocurrency is supported?', 'a' => 'Users may deposit using any supported cryptocurrency of their choice. All deposits are recorded based on their US Dollar (USD) value at the time of deposit. All withdrawals are processed exclusively in USDT. The amount withdrawn is calculated according to the USD value recorded at the time of the initial deposit, regardless of any subsequent changes in the market price of the deposited cryptocurrency. All withdrawals are made in USDT only.'],
                ['q' => 'How much can I earn daily?', 'a' => 'You earn a fixed 1% daily reward on your active deposit. For example, a 1,000 USDT deposit earns 10 USDT per day.'],
                ['q' => 'When are daily rewards credited?', 'a' => 'Rewards are calculated every 24 hours and credited to your reward balance automatically. You can withdraw rewards every 7 days.'],
                ['q' => 'How often can I withdraw profits?', 'a' => 'Profit (reward) withdrawals are available once every 7 days. The 7-day countdown begins from your deposit date or your last successful withdrawal.'],
                ['q' => 'How do I withdraw my capital?', 'a' => 'After the 7-day withdrawal interval, you can request capital withdrawal from your dashboard. Capital withdrawals are processed within 72 hours.'],
                ['q' => 'Are there withdrawal fees?', 'a' => 'Yes. One-Contract™ charges a 0.5% withdrawal fee on each reward and capital withdrawal. The fee is deducted from your requested amount, and the remaining balance is sent to your wallet.'],
                ['q' => 'What are the referral commission rates?', 'a' => 'Level 1: 5%, Level 2: 3%, Level 3: 2%. Commissions are credited automatically on Investment Scheme rewards and Prediction gaming.'],
            ],
        ],
    ];
@endphp

<main class="faq-page">
    <section class="faq-hero">
        <div class="faq-hero-bg"></div>
        <div class="container faq-hero-inner">
            <span class="section-tag">FAQ</span>
            <h1 class="faq-hero-title">Frequently Asked Questions</h1>
            <p class="faq-hero-desc">
                Everything you need to know about deposits, daily rewards, withdrawals,
                referrals, and account security on One-Contract™.
            </p>
        </div>
    </section>

    <section class="faq-content">
        <div class="container">
            <div class="faq-content-row">
                <div class="faq-content-col">
                    <div class="faq-categories">
                        @foreach ($categories as $category)
                            <div class="faq-category">
                                <div class="faq-category-head">
                                    <div class="faq-category-icon {{ $category['color'] }}">
                                        @include('faq.partials.icon', ['name' => $category['icon']])
                                    </div>
                                    <h2>{{ $category['title'] }}</h2>
                                </div>
                                <div class="faq-list">
                                    @foreach ($category['items'] as $item)
                                        <details class="faq-item">
                                            <summary>{{ $item['q'] }}</summary>
                                            <p>{{ $item['a'] }}</p>
                                        </details>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="faq-cta">
        <div class="container faq-cta-inner">
            <div class="faq-cta-text">
                <h2>Still have questions?</h2>
                <p>Our support team is available 24/7 to help you with anything not covered here.</p>
            </div>
            <div class="faq-cta-actions">
                <a href="mailto:support@one-contract.com" class="btn btn-outline">Contact Support</a>
                <a href="{{ route('register') }}" class="btn btn-gold">Get Started</a>
            </div>
        </div>
    </section>
</main>
@endsection

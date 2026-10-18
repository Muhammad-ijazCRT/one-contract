@extends('layouts.dashboard')

@section('title', 'Deposit — One-Contract™')
@section('page_title', 'Deposit')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--deposit')

@php
    $img = fn (string $file) => asset('images/deposit/'.$file);
    $v2Img = fn (string $file) => asset('images/deposit/v2/'.$file).'?v='.time();
    $defaultAmount = (float) old('amount', max($minDeposit, 100));
    $preferredCode = auth()->user()->preferredWalletCurrency()->value;
    $cryptoCodes = array_map(fn (\App\Enums\WalletCurrency $c) => $c->value, \App\Enums\WalletCurrency::crypto());
    $fallbackCurrency = 'USDT';
    $requestedCurrency = strtoupper((string) request('currency', ''));
    $defaultCurrency = old('currency', in_array($requestedCurrency, $cryptoCodes, true) ? $requestedCurrency : $fallbackCurrency);
    $quickAmounts = [10, 20, 50, 100, 200, 500];
    $fiatQuickAmounts = [10000, 50000, 100000, 200000, 500000];

    $cryptos = collect(\App\Enums\WalletCurrency::crypto())
        ->mapWithKeys(fn (\App\Enums\WalletCurrency $c) => [
            $c->value => [
                'code' => $c->value,
                'name' => $c->label(),
                'label' => $c->value.' - '.$c->label(),
                'icon' => $c->iconUrl()
            ],
        ])
        ->all();

    $networks = [
        'USDT' => ['id' => 'trc20', 'label' => 'TRC20 (Tron)', 'icon' => $img('trx.svg')],
        'BTC'  => ['id' => 'btc', 'label' => 'Bitcoin Network', 'icon' => $img('btc.svg')],
        'ETH'  => ['id' => 'eth', 'label' => 'ERC20 (Ethereum)', 'icon' => $img('eth.svg')],
        'TRX'  => ['id' => 'trx', 'label' => 'TRON Network', 'icon' => $img('trx.svg')],
    ];

    $curNetwork = $networks[$defaultCurrency] ?? ['id' => strtolower($defaultCurrency), 'label' => $defaultCurrency.' Network', 'icon' => $cryptos[$defaultCurrency]['icon'] ?? $img('trx.svg')];
@endphp

@section('content')
<div class="ocdep2-screen-wrapper">
    <div
        class="ocdep2-mobile-container"
        data-ocdep2-root
        data-min="{{ $minDeposit }}"
        data-enabled="{{ $depositsEnabled ? '1' : '0' }}"
        data-usd-rates='@json($usdRates ?? [])'
        data-decimals='@json($cryptoDecimals ?? [])'
    >
        {{-- ═══════════════════════════════════════════════════
             1. TOP BAR (Shared Header)
        ═══════════════════════════════════════════════════ --}}
        <header class="ocdep2-header">
            <a href="{{ route('user.dashboard') }}" class="ocdep2-back-btn" aria-label="Back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>

            <div class="ocdep2-brand-block">
                <img src="{{ asset('images/rpsv/v2/nav_cube_logo.png') }}" alt="" class="ocdep2-cube-logo" decoding="async">
                <span class="ocdep2-brand-name">ONE CONTRACT<sup>&trade;</sup></span>
            </div>

            <div class="ocdep2-header-right">
                <button type="button" class="ocdep2-bal-pill" data-oc-currency-open title="Toggle Balance">
                    <span class="ocdep2-bal-label">TOTAL BALANCE</span>
                    <span class="ocdep2-bal-dots">
                        ••••••
                        <svg class="ocdep2-bal-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </span>
                </button>

                <a href="{{ route('user.deposit.index') }}" class="ocdep2-add-btn" aria-label="Deposit">+</a >

                <a href="{{ route('user.chat.index') }}" class="ocdep2-icon-square" aria-label="Chat">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                    </svg>
                </a>

                <a href="{{ route('user.notifications.index') }}" class="ocdep2-icon-square" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="ocdep2-bell-dot">1</span>
                </a>

                <a href="{{ route('user.profile.edit') }}" class="ocdep2-icon-square" aria-label="Profile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
            </div>
        </header>

        {{-- ═══════════════════════════════════════════════════
             2. HERO HEADER (Deposit Title + Dynamic 3D Graphic)
        ═══════════════════════════════════════════════════ --}}
        <section class="ocdep2-hero-banner">
            <div class="ocdep2-hero-copy">
                <h1 class="ocdep2-hero-title">Deposit</h1>
                <p class="ocdep2-hero-sub">Add funds to your One-Contract wallet</p>
            </div>
            <div class="ocdep2-hero-art">
                {{-- State A: Crypto Golden Wallet Overflowing --}}
                <img src="{{ $v2Img('hero_crypto.png') }}" alt="Crypto Wallet" class="ocdep2-hero-img is-active" data-hero-crypto>
                {{-- State B: 3D Bank Columns + Naira Stack --}}
                <img src="{{ $v2Img('hero_fiat.png') }}" alt="Fiat Bank" class="ocdep2-hero-img" data-hero-fiat>
            </div>
        </section>

        {{-- ═══════════════════════════════════════════════════
             3. SEGMENTED TAB TOGGLE (High-Contrast Switches)
        ═══════════════════════════════════════════════════ --}}
        <div class="ocdep2-tab-strip" role="tablist">
            <button type="button" class="ocdep2-tab-btn is-active" data-ocdep2-tab="crypto" role="tab" aria-selected="true">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 4.02 2 6.5s4.48 4.5 10 4.5 10-2.02 10-4.5S17.52 2 12 2zm0 6.5c-4.41 0-8-1.57-8-2.5s3.59-2.5 8-2.5 8 1.57 8 2.5-3.59 2.5-8 2.5zm0 4.5c-5.52 0-10-2.02-10-4.5V11c0 2.48 4.48 4.5 10 4.5s10-2.02 10-4.5v-2.5c0 2.48-4.48 4.5-10 4.5zm0 6.5c-5.52 0-10-2.02-10-4.5V17c0 2.48 4.48 4.5 10 4.5s10-2.02 10-4.5v-2.5c0 2.48-4.48 4.5-10 4.5z"/>
                </svg>
                <span>Crypto Deposit</span>
            </button>

            <button type="button" class="ocdep2-tab-btn" data-ocdep2-tab="fiat" role="tab" aria-selected="false">
                <svg viewBox="0 0 24 24">
                    <path d="M4 10v7h3v-7H4zm6 0v7h3v-7h-3zM2 22h19v-3H2v3zm14-12v7h3v-7h-3zm-5-9L2 6v2h19V6l-9-5z"/>
                </svg>
                <span>Fiat Deposit</span>
            </button>
        </div>

        {{-- Deposit Backend Submission Form --}}
        <form method="POST" action="{{ route('user.deposit.store') }}" id="ocdep2-main-form">
            @csrf
            <input type="hidden" name="method_type" value="crypto" data-input-method>
            <input type="hidden" name="currency" value="{{ $defaultCurrency }}" data-input-currency>
            <input type="hidden" name="network" value="{{ $curNetwork['id'] }}" data-input-network>
            <input type="hidden" name="amount" value="{{ number_format($defaultAmount, 2, '.', '') }}" data-input-amount>

            {{-- ═══════════════════════════════════════════════════
                 4. STATE A: CRYPTO DEPOSIT FLOW
            ═══════════════════════════════════════════════════ --}}
            <div class="ocdep2-state-panel is-active" data-panel-crypto>
                {{-- Row 1: Select Cryptocurrency --}}
                <div class="ocdep2-row-block">
                    <label class="ocdep2-step-label">
                        <span class="ocdep2-step-badge">1</span>
                        <span>Select Cryptocurrency</span>
                    </label>
                    <div class="ocdep2-select-card">
                        <div class="ocdep2-select-left">
                            <img src="{{ $cryptos[$defaultCurrency]['icon'] ?? $img('usdt.svg') }}" alt="" class="ocdep2-coin-icon" data-crypto-coin-icon>
                            <div class="ocdep2-coin-info">
                                <strong class="ocdep2-coin-code" data-crypto-coin-code>{{ $defaultCurrency }}</strong>
                                <span class="ocdep2-coin-name" data-crypto-coin-name>{{ $cryptos[$defaultCurrency]['name'] ?? 'Tether USD' }}</span>
                            </div>
                        </div>
                        <svg class="ocdep2-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>

                        <select class="ocdep2-select-native" data-crypto-coin-select>
                            @foreach ($cryptos as $code => $meta)
                                @php
                                    $net = $networks[$code] ?? ['id' => strtolower($code), 'label' => $meta['name'].' Network', 'icon' => $meta['icon']];
                                @endphp
                                <option
                                    value="{{ $code }}"
                                    data-name="{{ $meta['name'] }}"
                                    data-icon="{{ $meta['icon'] }}"
                                    data-net-id="{{ $net['id'] }}"
                                    data-net-label="{{ $net['label'] }}"
                                    data-net-icon="{{ $net['icon'] }}"
                                    @selected($defaultCurrency === $code)
                                >{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Row 2: Select Network --}}
                <div class="ocdep2-row-block">
                    <label class="ocdep2-step-label">
                        <span class="ocdep2-step-badge">2</span>
                        <span>Select Network</span>
                    </label>
                    <div class="ocdep2-network-panel">
                        <div class="ocdep2-network-left">
                            <img src="{{ $curNetwork['icon'] }}" alt="" class="ocdep2-net-icon" data-crypto-net-icon>
                            <strong class="ocdep2-net-title" data-crypto-net-title>{{ $curNetwork['label'] }}</strong>
                        </div>
                        <div class="ocdep2-circle-chevron">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Row 3: Enter Amount (USD) --}}
                <div class="ocdep2-row-block">
                    <label class="ocdep2-step-label">
                        <span class="ocdep2-step-badge">3</span>
                        <span>Enter Amount (USD)</span>
                    </label>
                    <div class="ocdep2-amount-box">
                        <div class="ocdep2-amount-left">
                            <span class="ocdep2-currency-sym">$</span>
                            <input
                                type="number"
                                step="0.01"
                                class="ocdep2-amount-input"
                                value="{{ number_format($defaultAmount, 2, '.', '') }}"
                                data-crypto-amount
                                placeholder="100.00"
                            >
                        </div>
                        <div class="ocdep2-amount-badge">
                            <span class="ocdep2-usd-coin-dot">$</span>
                            <span>USD</span>
                            <svg class="ocdep2-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </div>

                    {{-- Quick Chip Buttons --}}
                    <div class="ocdep2-chips-row" style="margin-top: 8px;">
                        @foreach ($quickAmounts as $qa)
                            <button
                                type="button"
                                class="ocdep2-chip-btn {{ abs($defaultAmount - $qa) < 0.01 ? 'is-active' : '' }}"
                                data-crypto-chip="{{ $qa }}"
                            >${{ $qa }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Dynamic Receipt Panel --}}
                <div class="ocdep2-receipt-card">
                    <div class="ocdep2-receipt-top">
                        <div class="ocdep2-receipt-main">
                            <div class="ocdep2-round-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="17 1 21 5 17 9"></polyline>
                                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                    <polyline points="7 23 3 19 7 15"></polyline>
                                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                                </svg>
                            </div>
                            <div class="ocdep2-receipt-meta">
                                <span class="ocdep2-receipt-label">You will send</span>
                                <strong class="ocdep2-receipt-amount" data-crypto-send-amt>100.07 USDT</strong>
                            </div>
                        </div>

                        <div class="ocdep2-receipt-side">
                            <div class="ocdep2-rate-head">
                                <span>Rate</span>
                                <i class="fa-solid fa-circle-info"></i>
                            </div>
                            <span class="ocdep2-rate-value" data-crypto-rate-display>1 USD &asymp; 1.0007 USDT</span>
                        </div>
                    </div>
                </div>

                {{-- Danger / Warning Alert Banner --}}
                <div class="ocdep2-warn-banner">
                    <svg class="ocdep2-warn-triangle" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                    </svg>
                    <p class="ocdep2-warn-text">
                        Make sure you send the <strong>exact amount</strong> and choose <strong>correct network</strong> to avoid loss of asset.
                    </p>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════
                 5. STATE B: FIAT DEPOSIT FLOW
            ═══════════════════════════════════════════════════ --}}
            <div class="ocdep2-state-panel" data-panel-fiat>
                {{-- Row 1: Select Fiat Currency --}}
                <div class="ocdep2-row-block">
                    <label class="ocdep2-step-label">
                        <span class="ocdep2-step-badge">1</span>
                        <span>Select Fiat Currency</span>
                    </label>
                    <div class="ocdep2-select-card">
                        <div class="ocdep2-select-left">
                            <img src="{{ $v2Img('flag_ngn.svg') }}" alt="NGN" class="ocdep2-coin-icon">
                            <div class="ocdep2-coin-info">
                                <strong class="ocdep2-coin-code">NGN</strong>
                                <span class="ocdep2-coin-name">Nigerian Naira</span>
                            </div>
                        </div>
                        <svg class="ocdep2-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Row 2: Select Payment Method --}}
                <div class="ocdep2-row-block">
                    <label class="ocdep2-step-label">
                        <span class="ocdep2-step-badge">2</span>
                        <span>Select Payment Method</span>
                    </label>
                    <div class="ocdep2-methods-grid">
                        {{-- Method 1: Bank Transfer --}}
                        <div class="ocdep2-method-card is-active" data-fiat-method="bank_transfer">
                            <div class="ocdep2-method-top">
                                <div class="ocdep2-method-ico ocdep2-ico--blue">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M4 10v7h3v-7H4zm6 0v7h3v-7h-3zM2 22h19v-3H2v3zm14-12v7h3v-7h-3zm-5-9L2 6v2h19V6l-9-5z"/>
                                    </svg>
                                </div>
                                <div class="ocdep2-method-radio">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <div class="ocdep2-method-copy">
                                <strong class="ocdep2-method-name">Bank Transfer</strong>
                                <span class="ocdep2-method-sub">Direct bank transfer</span>
                            </div>
                        </div>

                        {{-- Method 2: Debit / Credit Card --}}
                        <div class="ocdep2-method-card" data-fiat-method="card">
                            <div class="ocdep2-method-top">
                                <div class="ocdep2-method-ico ocdep2-ico--orange">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                                    </svg>
                                </div>
                                <div class="ocdep2-method-radio">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <div class="ocdep2-method-copy">
                                <strong class="ocdep2-method-name">Debit / Credit Card</strong>
                                <span class="ocdep2-method-sub">Visa, Mastercard</span>
                            </div>
                        </div>

                        {{-- Method 3: Mobile Money --}}
                        <div class="ocdep2-method-card" data-fiat-method="mobile_money">
                            <div class="ocdep2-method-top">
                                <div class="ocdep2-method-ico ocdep2-ico--purple">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                                    </svg>
                                </div>
                                <div class="ocdep2-method-radio">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <div class="ocdep2-method-copy">
                                <strong class="ocdep2-method-name">Mobile Money</strong>
                                <span class="ocdep2-method-sub">Other payment methods</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 3: Enter Amount --}}
                <div class="ocdep2-row-block">
                    <label class="ocdep2-step-label">
                        <span class="ocdep2-step-badge">3</span>
                        <span>Enter Amount</span>
                    </label>
                    <div class="ocdep2-amount-box">
                        <div class="ocdep2-amount-left">
                            <span class="ocdep2-currency-sym">&#8358;</span>
                            <input
                                type="text"
                                class="ocdep2-amount-input"
                                value="100,000"
                                data-fiat-amount
                                placeholder="100,000"
                            >
                        </div>
                        <div class="ocdep2-amount-badge">
                            <span>NGN</span>
                        </div>
                    </div>

                    {{-- Naira Quick Chip Buttons --}}
                    <div class="ocdep2-chips-row" style="margin-top: 8px;">
                        @foreach ($fiatQuickAmounts as $fa)
                            <button
                                type="button"
                                class="ocdep2-chip-btn {{ $fa === 100000 ? 'is-active' : '' }}"
                                data-fiat-chip="{{ $fa }}"
                            >&#8358;{{ number_format($fa) }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Fiat Receipt Panel --}}
                <div class="ocdep2-receipt-card">
                    <div class="ocdep2-receipt-top">
                        <div class="ocdep2-receipt-main">
                            <div class="ocdep2-round-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                                </svg>
                            </div>
                            <div class="ocdep2-receipt-meta">
                                <span class="ocdep2-receipt-label">You will receive</span>
                                <strong class="ocdep2-receipt-amount" data-fiat-receive-amt>100,000 NGN</strong>
                                <span class="ocdep2-receipt-label" style="font-size: 0.58rem;">Amount will be credited to your wallet</span>
                            </div>
                        </div>
                    </div>

                    <div class="ocdep2-fiat-rows">
                        <div class="ocdep2-fiat-row">
                            <span>Processing Time</span>
                            <span class="ocdep2-fiat-val--green">Instant</span>
                        </div>
                        <div class="ocdep2-fiat-row">
                            <span>Service Fee</span>
                            <span class="ocdep2-fiat-val--white">0 NGN</span>
                        </div>
                        <div class="ocdep2-fiat-row">
                            <span>Total</span>
                            <strong class="ocdep2-fiat-val--white" data-fiat-total-amt>100,000 NGN</strong>
                        </div>
                    </div>
                </div>

                {{-- Warning Banner --}}
                <div class="ocdep2-warn-banner">
                    <svg class="ocdep2-warn-triangle" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                    </svg>
                    <p class="ocdep2-warn-text">
                        Send the <strong>exact amount</strong> using the payment details provided on the next page to avoid delays.
                    </p>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════
                 6. UNIFIED ACTION BUTTON (Shared Footer)
            ═══════════════════════════════════════════════════ --}}
            <button type="submit" class="ocdep2-submit-btn" style="margin-top: 14px;" @disabled(! $depositsEnabled)>
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span>{{ $depositsEnabled ? 'Proceed to Deposit' : 'Deposits Disabled' }}</span>
            </button>
        </form>

        {{-- Deposit History Strip --}}
        @if ($deposits->count() > 0)
            <div style="margin-top: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-family: 'Poppins', sans-serif; font-size: 0.78rem; font-weight: 800; color: #94a3b8;">RECENT DEPOSITS</span>
                    <a href="{{ route('user.deposits.pending') }}" style="color: #ffd600; font-size: 0.72rem; font-weight: 700; text-decoration: none;">Pending ({{ $pendingDepositsCount }}) &rsaquo;</a>
                </div>
                <div class="ocdep-history-table">
                    @include('user.partials.deposit-table', [
                        'deposits' => $deposits,
                        'emptyMessage' => 'No deposits yet.',
                    ])
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
@vite(['resources/css/deposit-v2.css'])
@endpush

@push('scripts')
@vite(['resources/js/deposit-v2.js'])
@endpush

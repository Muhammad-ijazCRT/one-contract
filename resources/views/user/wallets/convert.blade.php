@extends('layouts.dashboard')

@section('title', 'Crypto Conversion — One-Contract™')
@section('page_title', 'Crypto Conversion')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--convert')
@section('layout_class', 'convert-layout')

@php
    $hero = asset('images/support/hero-cube.png');
    $heroFallback = asset('images/support/hero-cube.svg');
@endphp

@section('content')
<div
    class="dash-page oc-convert"
    data-oc-convert
    data-rates-url="{{ $ratesUrl }}"
    data-quote-url="{{ route('user.wallets.quote') }}"
    data-convert-url="{{ route('user.wallets.convert.store') }}"
    data-fee-percent="{{ $feePercent }}"
    data-balances='@json($balancesJson)'
    data-generic-icon="{{ asset('images/wallets/crypto-generic.svg') }}"
    data-from-default="{{ $fromDefault }}"
    data-to-default="{{ $toDefault }}"
>
    @include('user.partials.alerts')

    <header class="occonv-hero">
        <div class="occonv-hero-copy">
            <h1>CRYPTO <em>CONVERSION</em></h1>
            <p>Convert crypto to crypto instantly via NOWPayments liquidity. Fiat conversion is not available on this page.</p>
        </div>
        <div class="occonv-hero-art" aria-hidden="true">
            <img src="{{ $hero }}" alt="" width="120" height="120" onerror="this.onerror=null;this.src='{{ $heroFallback }}';">
        </div>
    </header>

    <section class="occonv-balances" aria-label="Your balances">
        <h2>YOUR BALANCES</h2>
        <div class="occonv-bal-scroll">
            @foreach ($cryptoCards as $card)
                <button
                    type="button"
                    class="occonv-bal-card"
                    data-occonv-pick="{{ $card['code'] }}"
                    style="--coin-accent: {{ $card['accent'] }}"
                >
                    <span class="occonv-bal-icon">
                        <img
                            src="{{ $card['icon'] }}"
                            alt="{{ $card['code'] }}"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='{{ asset('images/wallets/crypto-generic.svg') }}';"
                        >
                    </span>
                    <span class="occonv-bal-meta">
                        <strong>{{ $card['code'] }}</strong>
                        <small>{{ $card['label'] }}</small>
                    </span>
                    <span class="occonv-bal-amt">{{ $card['formatted'] }}</span>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>
            @endforeach
        </div>
    </section>

    <section class="occonv-panel">
        <form method="POST" action="{{ route('user.wallets.convert.store') }}" class="occonv-form" data-occonv-form>
            @csrf

            <div class="occonv-row">
                <div class="occonv-row-head">
                    <span>YOU SEND</span>
                    <small>Available: <b data-occonv-from-avail>—</b></small>
                </div>
                <div class="occonv-inputs">
                    @include('user.partials.coin-picker-dropdown', [
                        'name' => 'from_currency',
                        'pickerId' => 'occonv-from-picker',
                        'selected' => $fromDefault,
                        'currencies' => $currencies,
                    ])
                    <div class="occonv-amount-wrap">
                        <input type="number" name="amount" step="any" min="0" placeholder="0.00" value="{{ old('amount') }}" required data-occonv-amount>
                        <button type="button" class="occonv-max" data-occonv-max>MAX</button>
                    </div>
                </div>
                @error('amount')<span class="occonv-error">{{ $message }}</span>@enderror
            </div>

            <div class="occonv-swap-line">
                <button type="button" class="occonv-swap" data-occonv-swap aria-label="Swap currencies">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <div class="occonv-row">
                <div class="occonv-row-head">
                    <span>YOU RECEIVE</span>
                    <small>Available: <b data-occonv-to-avail>—</b></small>
                </div>
                <div class="occonv-inputs">
                    @include('user.partials.coin-picker-dropdown', [
                        'name' => 'to_currency',
                        'pickerId' => 'occonv-to-picker',
                        'selected' => $toDefault,
                        'currencies' => $currencies,
                    ])
                    <div class="occonv-amount-wrap is-readonly">
                        <input type="text" readonly value="0.00" data-occonv-receive>
                    </div>
                </div>
                @error('to_currency')<span class="occonv-error">{{ $message }}</span>@enderror
            </div>

            <div class="occonv-quote" data-occonv-quote hidden>
                <div>
                    <span>Conversion Rate</span>
                    <strong data-occonv-rate>—</strong>
                </div>
                <div>
                    <span>Total Fee</span>
                    <strong data-occonv-fee>—</strong>
                </div>
                <div>
                    <span>You Receive</span>
                    <strong data-occonv-receive-label class="is-green">—</strong>
                </div>
            </div>

            <p class="occonv-fee-note" data-occonv-fee-note hidden>
                Fees: NOWPayments provider fee (dynamic) + One-Contract {{ number_format($feePercent, 2) }}% platform fee.
            </p>

            <button type="submit" class="occonv-submit" data-occonv-submit>PREVIEW CONVERSION</button>

            <p class="occonv-secure">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                Secure Conversion: Your funds are protected with bank-level security.
            </p>
        </form>
    </section>

    <section class="occonv-recent">
        <header>
            <h2>RECENT CONVERSIONS</h2>
            <a href="{{ route('user.wallets.index') }}">View All <i class="fa-solid fa-chevron-right"></i></a>
        </header>
        <ul class="occonv-recent-list">
            @forelse ($recentConversions as $conv)
                @php
                    $from = $conv->from_currency instanceof \App\Enums\WalletCurrency ? $conv->from_currency : \App\Enums\WalletCurrency::normalize((string) $conv->from_currency);
                    $to = $conv->to_currency instanceof \App\Enums\WalletCurrency ? $conv->to_currency : \App\Enums\WalletCurrency::normalize((string) $conv->to_currency);
                @endphp
                <li>
                    <div class="occonv-recent-icons">
                        <img src="{{ $from->iconUrl() }}" alt="">
                        <img src="{{ $to->iconUrl() }}" alt="">
                    </div>
                    <div class="occonv-recent-meta">
                        <strong>{{ $from->value }} → {{ $to->value }}</strong>
                        <small>{{ $conv->created_at?->format('M j, Y • g:i A') }}</small>
                    </div>
                    <div class="occonv-recent-amts">
                        <span>{{ $from->formatCompact((float) $conv->from_amount) }}</span>
                        <span class="is-green">{{ $to->formatCompact((float) $conv->to_amount) }}</span>
                    </div>
                    <span class="occonv-status is-done">{{ ucfirst($conv->status ?? 'completed') }}</span>
                </li>
            @empty
                <li class="occonv-recent-empty">No conversions yet. Your history will appear here.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection

@push('styles')
    @vite('resources/css/convert-mock.css')
@endpush

@push('scripts')
    @vite('resources/js/convert-mock.js')
@endpush

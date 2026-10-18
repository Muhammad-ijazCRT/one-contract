@extends('layouts.dashboard')

@section('title', 'My Wallet — One-Contract™')
@section('page_title', 'My Wallet')
@section('page_subtitle', 'Manage your funds, view balances and transaction history.')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--wallet')

@php
    $hero = asset('images/wallets/wallet-hero.png').'?v=2';
    $img = fn (string $file) => asset('images/wallets/'.$file);
@endphp

@section('content')
<div class="dash-page oc-mwallet" data-oc-mwallet data-rates-url="{{ $ratesUrl ?? route('user.wallets.rates') }}">
    @include('user.partials.alerts')

    <section class="ocmw-hero">
        <div class="ocmw-hero-copy">
            <span class="ocmw-label">TOTAL BALANCE</span>
            <div class="ocmw-total">
                <strong data-ocmw-total data-value="{{ number_format($totalUsd, 2) }}">${{ number_format($totalUsd, 2) }}</strong>
                <button type="button" class="ocmw-eye" data-ocmw-toggle-balance aria-label="Toggle balance visibility">
                    <i class="fa-solid fa-eye" data-ocmw-eye-icon></i>
                </button>
            </div>
            <p class="ocmw-usdt" data-ocmw-usdt data-value="{{ number_format($totalUsdt, 2) }}">≈ {{ number_format($totalUsdt, 2) }} USDT</p>
        </div>
        <div class="ocmw-hero-art" aria-hidden="true">
            <img src="{{ $hero }}" alt="" width="1024" height="1024">
        </div>
    </section>

    <section class="ocmw-actions" aria-label="Quick actions">
        <a href="{{ route('user.deposit.index') }}" class="ocmw-action">
            <span class="ocmw-action-icon">
                <img src="{{ $img('action-deposit.svg') }}" alt="">
            </span>
            <div>
                <strong>DEPOSIT</strong>
                <span>Add funds to your wallet</span>
            </div>
        </a>
        <a href="{{ route('user.withdraw.index') }}" class="ocmw-action">
            <span class="ocmw-action-icon">
                <img src="{{ $img('action-withdraw.svg') }}" alt="">
            </span>
            <div>
                <strong>WITHDRAW</strong>
                <span>Withdraw funds from wallet</span>
            </div>
        </a>
        <a href="{{ route('user.wallets.convert') }}" class="ocmw-action">
            <span class="ocmw-action-icon">
                <img src="{{ $img('action-convert.svg') }}" alt="">
            </span>
            <div>
                <strong>CONVERT</strong>
                <span>Convert crypto instantly</span>
            </div>
        </a>
    </section>

    <section class="ocmw-overview">
        <header class="ocmw-sec-head">
            <h2><i></i> WALLET OVERVIEW</h2>
            <button type="button" class="ocmw-hide-small" data-ocmw-hide-small>
                <i class="fa-solid fa-eye-slash"></i>
                <span data-ocmw-hide-label>Hide Small Balances</span>
            </button>
        </header>

        <div class="ocmw-asset-list" data-ocmw-assets>
            @foreach ($cards as $card)
                @php
                    $isSmall = (float) ($card['usd'] ?? 0) < 1 && (float) $card['balance'] < 0.0001;
                    $detailUrl = $card['currency']->isCrypto()
                        ? route('user.deposit.index', ['currency' => $card['code']])
                        : route('user.wallets.convert');
                @endphp
                <a
                    href="{{ $detailUrl }}"
                    class="ocmw-asset"
                    data-ocmw-asset
                    data-usd="{{ number_format((float) ($card['usd'] ?? 0), 4, '.', '') }}"
                    data-small="{{ $isSmall ? '1' : '0' }}"
                >
                    <div class="ocmw-asset-left">
                        <img src="{{ $card['icon'] }}" alt="{{ $card['code'] }}" width="40" height="40" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/wallets/crypto-generic.svg') }}';">
                        <div>
                            <strong>{{ $card['code'] }}</strong>
                            <span>{{ $card['label'] }}</span>
                        </div>
                    </div>
                    <div class="ocmw-asset-right">
                        <strong class="ocmw-asset-bal" data-ocmw-mask data-value="{{ $card['formatted'] }}">{{ $card['formatted'] }}</strong>
                        <span class="ocmw-asset-usd" data-ocmw-mask data-value="{{ $card['usd_formatted'] ?? '$0.00' }}">{{ $card['usd_formatted'] ?? '$0.00' }}</span>
                    </div>
                    <i class="fa-solid fa-chevron-right ocmw-chevron"></i>
                </a>
            @endforeach
        </div>

        <a href="{{ route('user.wallets.convert') }}" class="ocmw-view-all">
            <i class="fa-solid fa-list"></i>
            VIEW ALL WALLETS
            <i class="fa-solid fa-chevron-right"></i>
        </a>
    </section>

    <section class="ocmw-tx" id="ocmw-recent">
        <header class="ocmw-sec-head">
            <h2><i></i> RECENT TRANSACTIONS</h2>
            <a href="{{ route('user.transactions.index') }}" class="ocmw-link">
                View All <i class="fa-solid fa-chevron-right"></i>
            </a>
        </header>

        <div class="ocmw-tx-list">
            @forelse ($transactions as $tx)
                <a href="{{ $tx['url'] }}" class="ocmw-tx-row">
                    <div class="ocmw-tx-icon ocmw-tx-icon--{{ $tx['kind'] }}">
                        @if ($tx['kind'] === 'deposit')
                            <i class="fa-solid fa-arrow-down"></i>
                        @elseif ($tx['kind'] === 'withdraw')
                            <i class="fa-solid fa-arrow-up"></i>
                        @else
                            <i class="fa-solid fa-right-left"></i>
                        @endif
                    </div>
                    <div class="ocmw-tx-meta">
                        <strong>{{ $tx['title'] }}</strong>
                        <span>{{ $tx['network'] }}</span>
                    </div>
                    <div class="ocmw-tx-amt ocmw-tx-amt--{{ $tx['kind'] }}">
                        <strong>{{ $tx['amount_label'] }}</strong>
                        <span>{{ $tx['usd_label'] }}</span>
                    </div>
                    <div class="ocmw-tx-status">
                        <time>{{ $tx['at'] }}</time>
                        <em>{{ $tx['status'] }}</em>
                    </div>
                </a>
            @empty
                <p class="ocmw-empty">No recent transactions yet.</p>
            @endforelse
        </div>
        {{ $transactions->links() }}
    </section>
</div>
@endsection

@push('styles')
@vite(['resources/css/wallet-mock.css'])
@endpush

@push('scripts')
@vite(['resources/js/wallet-mock.js'])
@endpush

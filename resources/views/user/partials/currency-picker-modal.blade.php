@php
    $pickerCurrencies = $header_currency_picker ?? [];
@endphp

<div
    class="ocscur-modal"
    id="oc-currency-picker"
    data-oc-currency-picker
    hidden
    aria-hidden="true"
>
    <button type="button" class="ocscur-modal-backdrop" data-oc-currency-close aria-label="Close"></button>

    <div
        class="ocscur-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ocscur-modal-title"
    >
        <header class="ocscur-head">
            <button type="button" class="ocscur-back" data-oc-currency-close aria-label="Close">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <h1 class="ocscur-title" id="ocscur-modal-title">SELECT <em>CURRENCY</em></h1>
            <span class="ocscur-brand" aria-hidden="true"><strong>ONE</strong> <span>CONTRACT™</span></span>
        </header>

        <section class="ocscur-panel">
            <div class="ocscur-panel-head">
                <div>
                    <h2>CHOOSE CURRENCY</h2>
                    <p>Select your active gaming currency. Stakes and winnings use this balance.</p>
                </div>
                <span class="ocscur-brand ocscur-brand--inline" aria-hidden="true"><strong>ONE</strong> <span>CONTRACT™</span></span>
            </div>

            <ul class="ocscur-list">
                @forelse ($pickerCurrencies as $item)
                    <li>
                        <form method="POST" action="{{ route('user.wallets.select-currency.store') }}" class="ocscur-form">
                            @csrf
                            <input type="hidden" name="currency" value="{{ $item['code'] }}">
                            <button
                                type="submit"
                                class="ocscur-row {{ !empty($item['selected']) ? 'is-selected' : '' }}"
                                style="--cur-accent: {{ $item['accent'] }}"
                            >
                                <span class="ocscur-icon" aria-hidden="true">
                                    <img src="{{ $item['icon'] }}" alt="">
                                </span>
                                <span class="ocscur-meta">
                                    <strong>{{ $item['code'] }}</strong>
                                    <small>{{ $item['label'] }}</small>
                                    @if (!empty($item['recommended']))
                                        <em class="ocscur-tag">RECOMMENDED</em>
                                    @endif
                                </span>
                                <span class="ocscur-bal">
                                    <span>Balance</span>
                                    <b>{{ $item['formatted'] }}</b>
                                    @if (($item['code'] ?? '') !== 'USDT' && ($item['code'] ?? '') !== 'USDC')
                                        <small>{{ $item['usd_formatted'] ?? '' }}</small>
                                    @endif
                                </span>
                                <i class="fa-solid fa-chevron-right ocscur-chevron" aria-hidden="true"></i>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="ocscur-empty">No wallets available.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>

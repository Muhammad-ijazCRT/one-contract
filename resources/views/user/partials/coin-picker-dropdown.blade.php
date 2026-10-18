@php
    /** @var string $name */
    /** @var string $pickerId */
    /** @var string $selected */
    /** @var list<\App\Enums\WalletCurrency> $currencies */
    $generic = asset('images/wallets/crypto-generic.svg');
    $current = \App\Enums\WalletCurrency::tryNormalize($selected) ?? $currencies[0] ?? \App\Enums\WalletCurrency::Usdt;
@endphp
<div class="occonv-coin" data-occonv-coin-picker id="{{ $pickerId }}">
    <input type="hidden" name="{{ $name }}" value="{{ $current->value }}" data-occonv-coin-input>
    <button type="button" class="occonv-coin-btn" data-occonv-coin-toggle aria-haspopup="listbox" aria-expanded="false">
        <img src="{{ $current->iconUrl() }}" alt="" data-occonv-coin-icon onerror="this.onerror=null;this.src='{{ $generic }}';">
        <span data-occonv-coin-label>{{ $current->value }}</span>
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="occonv-coin-menu" data-occonv-coin-menu hidden role="listbox">
        @foreach ($currencies as $currency)
            <button
                type="button"
                class="occonv-coin-opt {{ $current === $currency ? 'is-active' : '' }}"
                role="option"
                data-value="{{ $currency->value }}"
                data-icon="{{ $currency->iconUrl() }}"
                data-decimals="{{ $currency->decimals() }}"
                data-label="{{ $currency->label() }}"
                style="--coin-accent: {{ $currency->accentColor() }}"
            >
                <img src="{{ $currency->iconUrl() }}" alt="" onerror="this.onerror=null;this.src='{{ $generic }}';">
                <span class="occonv-coin-opt-meta">
                    <strong>{{ $currency->value }}</strong>
                    <small>{{ $currency->label() }}</small>
                </span>
            </button>
        @endforeach
    </div>
</div>

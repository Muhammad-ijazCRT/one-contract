@extends('layouts.dashboard')

@section('title', 'Deposit #'.$deposit->id.' — One-Contract™')
@section('page_title', 'Deposit Details')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--deposit')

@section('content')
@php
    $status = $deposit->status->value;
    $statusClass = match ($status) {
        'active', 'completed' => 'status-badge success',
        'pending' => 'status-badge warning',
        'rejected' => 'status-badge danger',
        'withdrawn' => 'status-badge info',
        default => 'status-badge secondary',
    };
    $paymentDone = in_array($deposit->payment_status, ['finished', 'confirmed'], true) || in_array($status, ['active', 'completed'], true);
    $creditCurrency = \App\Enums\WalletCurrency::tryNormalize($deposit->credit_currency ?? 'USDT') ?? \App\Enums\WalletCurrency::Usdt;
@endphp
<div class="dash-page oc-deposit-pay">
    @include('user.partials.alerts')

    <header class="ocdep-head">
        <a href="{{ route('user.deposit.index') }}" class="ocdep-back" aria-label="Back">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="ocdep-head-copy">
            <h1>COMPLETE DEPOSIT</h1>
            <p>Deposit #{{ $deposit->id }} · {{ number_format((float) $deposit->amount, 2) }} USD</p>
        </div>
    </header>

    @if ($status === 'pending' && ! $paymentDone && $deposit->pay_address)
        <section class="ocdep-pay-card">
            <div class="ocdep-pay-head">
                <img src="{{ $creditCurrency->iconUrl() }}" alt="">
                <div>
                    <strong>Send {{ strtoupper((string) $deposit->pay_currency) }}</strong>
                    <small>Network: {{ strtoupper((string) ($deposit->network ?: 'TRC20')) }} only</small>
                </div>
                <span class="ocdep-pay-badge">NOWPayments</span>
            </div>

            <div class="ocdep-pay-row">
                <span>Amount to pay</span>
                <strong>{{ $payAmount ? rtrim(rtrim(number_format((float) $payAmount, 8), '0'), '.') : '—' }} {{ strtoupper((string) $deposit->pay_currency) }}</strong>
            </div>
            <div class="ocdep-pay-row">
                <span>USD value</span>
                <strong>≈ ${{ number_format((float) $deposit->amount, 2) }}</strong>
            </div>

            <label class="ocdep-pay-label" for="ocdep-pay-address">Deposit address</label>
            <div class="ocdep-pay-address">
                <input id="ocdep-pay-address" type="text" readonly value="{{ $deposit->pay_address }}">
                <button type="button" class="ocdep-pay-copy" data-copy-target="ocdep-pay-address">Copy</button>
            </div>

            <p class="ocdep-pay-warn">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Send the <strong>exact amount</strong> on the <strong>correct network</strong>. Wrong network (e.g. USDT OMNI) will fail or lose funds.
            </p>
        </section>
    @endif

    <section class="ocdep-pay-meta">
        <div><span>Status</span><strong class="{{ $paymentDone ? 'is-ok' : 'is-warn' }}">{{ $paymentDone ? 'Confirmed' : ucfirst(str_replace('_', ' ', (string) $deposit->payment_status ?: 'waiting')) }}</strong></div>
        <div><span>Credit wallet</span><strong>{{ $creditCurrency->value }}</strong></div>
        <div><span>Payment ID</span><strong>{{ $deposit->payment_id ?: '—' }}</strong></div>
        <div><span>Submitted</span><strong>{{ ($deposit->deposited_at ?? $deposit->created_at)?->format('M j, Y g:i A') }}</strong></div>
    </section>

    @if ($status === 'pending' && $deposit->payment_url && ! $deposit->pay_address)
        <a href="{{ $deposit->payment_url }}" class="ocdep-cta" target="_blank" rel="noopener noreferrer">
            Continue to Payment Page
        </a>
    @endif

    <a href="{{ route('user.deposit.index') }}" class="ocdep-pay-back">Back to Deposits</a>
</div>
@endsection

@push('styles')
@vite(['resources/css/deposit-mock.css'])
<style>
.oc-deposit-pay { color: #fff; max-width: 720px; margin: 0 auto; }
.ocdep-pay-card { border: 1px solid rgba(239,255,0,.35); border-radius: 1rem; background: #0a0a0a; padding: 1rem; margin-bottom: 1rem; }
.ocdep-pay-head { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.ocdep-pay-head img { width: 42px; height: 42px; border-radius: 999px; }
.ocdep-pay-head strong { display: block; font-size: 1rem; }
.ocdep-pay-head small { color: rgba(255,255,255,.6); }
.ocdep-pay-badge { margin-left: auto; font-size: .65rem; font-weight: 800; color: #000; background: #efff00; padding: .25rem .45rem; border-radius: 999px; }
.ocdep-pay-row { display: flex; justify-content: space-between; gap: .75rem; padding: .55rem 0; border-bottom: 1px solid rgba(255,255,255,.08); }
.ocdep-pay-row span { color: rgba(255,255,255,.55); font-size: .8rem; }
.ocdep-pay-label { display: block; margin: .85rem 0 .35rem; font-size: .72rem; letter-spacing: .08em; color: rgba(255,255,255,.55); }
.ocdep-pay-address { display: grid; grid-template-columns: 1fr auto; gap: .5rem; }
.ocdep-pay-address input { width: 100%; border-radius: .55rem; border: 1px solid rgba(255,255,255,.12); background: #111; color: #fff; padding: .65rem .75rem; font-size: .78rem; }
.ocdep-pay-copy { border: 0; border-radius: .55rem; background: #efff00; color: #000; font-weight: 800; padding: 0 .85rem; cursor: pointer; }
.ocdep-pay-warn { margin: .85rem 0 0; display: flex; gap: .5rem; align-items: flex-start; font-size: .78rem; color: #ffb4b4; }
.ocdep-pay-meta { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: .65rem; margin-bottom: 1rem; }
.ocdep-pay-meta div { border: 1px solid rgba(255,255,255,.1); border-radius: .75rem; background: #0d0d0d; padding: .7rem; }
.ocdep-pay-meta span { display: block; font-size: .68rem; color: rgba(255,255,255,.55); margin-bottom: .2rem; }
.ocdep-pay-meta .is-ok { color: #7dff40; }
.ocdep-pay-meta .is-warn { color: #efff00; }
.ocdep-pay-back { display: inline-flex; color: rgba(255,255,255,.7); text-decoration: none; font-size: .85rem; margin-top: .5rem; }
@media (max-width: 520px) { .ocdep-pay-meta { grid-template-columns: 1fr; } }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('[data-copy-target]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.copyTarget;
        const input = id ? document.getElementById(id) : null;
        if (!input) return;
        try {
            await navigator.clipboard.writeText(input.value);
            btn.textContent = 'Copied';
            setTimeout(() => { btn.textContent = 'Copy'; }, 1500);
        } catch {
            input.select();
            document.execCommand('copy');
        }
    });
});
</script>
@endpush

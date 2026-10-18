@extends('layouts.dashboard')

@section('title', 'Withdraw — One-Contract™')
@section('page_title', 'Withdraw')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--withdraw')

@php
    $img = fn (string $file) => asset('images/withdraw/'.$file);
    $mainBalance = (float) ($selectedBalance ?? ($stats['main_wallet'] ?? 0));
    $selectedCurrency = $selectedCurrency ?? 'USDT';
    $decimals = $selectedCurrency === 'BTC' ? 8 : ($selectedCurrency === 'TRX' ? 6 : 2);
    $feeRate = (float) ($withdrawalFeeRate ?? 0);
    $available = max(0, $mainBalance * (1 - $feeRate));
    $formEnabled = $kycApproved && $canWithdraw && $mainBalance > 0;
    $minAmt = $selectedCurrency === 'USDT' ? (float) $minRewardWithdrawal : 0.0001;
    $quickCrypto = [10, 50, 100, 200, 500];
@endphp

@section('content')
<div
    class="dash-page oc-withdraw"
    data-oc-withdraw
    data-fee-rate="{{ $feeRate }}"
    data-balance="{{ $mainBalance }}"
    data-currency="{{ $selectedCurrency }}"
    data-min="{{ $minAmt }}"
>
    @include('user.partials.alerts')

    <header class="ocwd-head">
        <a href="{{ route('user.wallets.index') }}" class="ocwd-back" aria-label="Back">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="ocwd-head-copy">
            <h1>WITHDRAW</h1>
            <p>Withdraw funds from your One-Contract wallet.</p>
        </div>
        <a href="#ocwd-history" class="ocwd-history-btn">
            <i class="fa-solid fa-clock-rotate-left"></i>
            Withdrawal History
        </a>
    </header>

    @unless ($kycApproved)
        <div class="ocwd-alert">
            <strong>KYC {{ $kycStatus?->label() ?? 'Under Verification' }}</strong>
            Withdrawals unlock after admin confirms your identity.
        </div>
    @endunless

    @if ($nextWithdrawalAt && $nextWithdrawalAt->isFuture())
        <div class="ocwd-alert ocwd-alert--warn">
            Next withdrawal window: <strong>{{ $nextWithdrawalAt->format('M d, Y h:i A') }}</strong>
        </div>
    @endif

    <section class="ocwd-balances">
        <div class="ocwd-bal">
            <span class="ocwd-bal-label"><img src="{{ $img('usdt.svg') }}" alt=""> {{ $selectedCurrency }} Wallet Balance</span>
            <strong>{{ number_format($mainBalance, $decimals) }} {{ $selectedCurrency }}</strong>
            <small>≈ ${{ number_format($mainBalance, 2) }}</small>
        </div>
        <div class="ocwd-bal ocwd-bal--avail">
            <span class="ocwd-bal-label">Available to Withdraw</span>
            <strong>{{ number_format($available, $decimals) }} {{ $selectedCurrency }}</strong>
            <small>After {{ $withdrawalFeePercent }}% fee estimate</small>
        </div>
    </section>

    <section class="ocwd-panel is-visible" data-ocwd-panel="crypto">
        <header class="ocwd-step-head"><span>1</span> WITHDRAWAL DETAILS</header>

        <form method="POST" action="{{ route('user.withdraw.reward') }}" class="ocwd-form" data-ocwd-crypto-form
              data-confirm="Submit {{ $selectedCurrency }} withdrawal?"
              data-confirm-ok="Yes, Submit">
            @csrf
            <input type="hidden" name="currency" value="{{ $selectedCurrency }}">

            <div class="ocwd-currency-pills">
                @foreach (['USDT', 'BTC', 'TRX'] as $code)
                    <a href="{{ route('user.withdraw.index', ['currency' => $code, 'method' => 'crypto']) }}"
                       class="ocwd-pill {{ $selectedCurrency === $code ? 'is-active' : '' }}">{{ $code }}</a>
                @endforeach
            </div>

            <label class="ocwd-label" for="reward_network">Select Network</label>
            <div class="ocwd-field">
                <i class="fa-solid fa-link"></i>
                <select id="reward_network" name="network" required @disabled(! $formEnabled)>
                    <option value="" disabled @selected(! old('network'))>Select network</option>
                    @foreach ($networks as $net)
                        <option value="{{ $net->value }}" @selected(old('network') === $net->value)>{{ $net->label() }}</option>
                    @endforeach
                </select>
            </div>

            <label class="ocwd-label" for="reward_wallet">Wallet Address</label>
            <div class="ocwd-field">
                <i class="fa-solid fa-wallet"></i>
                <input type="text" id="reward_wallet" name="wallet_address" value="{{ old('wallet_address') }}" placeholder="Enter wallet address" required @disabled(! $formEnabled)>
            </div>

            <label class="ocwd-label" for="reward_amount">AMOUNT ({{ $selectedCurrency }})</label>
            <div class="ocwd-amount">
                <input type="number" step="any" min="{{ $minAmt }}" max="{{ $mainBalance }}" id="reward_amount" name="amount"
                       value="{{ old('amount') }}" placeholder="Enter amount" required
                       data-ocwd-amount data-withdrawal-fee-rate="{{ $feeRate }}" @disabled(! $formEnabled)>
                <span>{{ $selectedCurrency }}</span>
            </div>
            <div class="ocwd-quick" data-ocwd-quick="crypto">
                @foreach ($quickCrypto as $q)
                    <button type="button" class="ocwd-chip" data-ocwd-chip="{{ $q }}" @disabled(! $formEnabled || $q > $mainBalance)>$ {{ $q }}</button>
                @endforeach
                <button type="button" class="ocwd-chip" data-ocwd-max @disabled(! $formEnabled)>MAX</button>
            </div>
            <div class="ocwd-fee" id="reward-fee-preview" hidden>
                <div><span>Withdrawal</span><strong id="reward-fee-gross">0.00</strong></div>
                <div><span>Fee ({{ $withdrawalFeePercent }}%)</span><strong id="reward-fee-value">0.00</strong></div>
                <div><span>You receive</span><strong id="reward-fee-net">0.00</strong></div>
            </div>
            <input type="hidden" name="net" id="reward_net" value="">

            <header class="ocwd-step-head ocwd-step-head--sub"><span>2</span> EMAIL VERIFICATION</header>

            <label class="ocwd-label" for="email_otp">Email verification code</label>
            <div class="ocwd-otp">
                <div class="ocwd-field">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="text" id="email_otp" name="email_otp" value="{{ old('email_otp') }}" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="6-digit code" required @disabled(! $formEnabled)>
                </div>
                <button type="button" class="ocwd-otp-btn" data-ocwd-send-otp="{{ route('user.withdraw.send-otp') }}" @disabled(! $formEnabled)>Send code</button>
            </div>
            <p class="ocwd-hint">Code is sent to <strong>{{ $user->email }}</strong>. Valid for 10 minutes.</p>
            <p class="ocwd-otp-status" data-ocwd-otp-status hidden></p>
            @error('email_otp')<span class="ocwd-error">{{ $message }}</span>@enderror

            @error('kyc')<span class="ocwd-error">{{ $message }}</span>@enderror
            @error('amount')<span class="ocwd-error">{{ $message }}</span>@enderror
            @error('wallet_address')<span class="ocwd-error">{{ $message }}</span>@enderror
            @error('network')<span class="ocwd-error">{{ $message }}</span>@enderror

            <button type="submit" class="ocwd-cta" @disabled(! $formEnabled)>
                <i class="fa-solid fa-paper-plane"></i>
                PROCEED TO WITHDRAW
            </button>
        </form>
    </section>

    <section class="ocwd-secure">
        <div class="ocwd-secure-copy">
            <img src="{{ $img('secure-hero.png') }}" alt="" class="ocwd-secure-art" width="120" height="120">
            <div>
                <strong>Your Security, Our Priority</strong>
                <p>We use advanced security measures and manual review to ensure safe withdrawals.</p>
            </div>
        </div>
        <div class="ocwd-secure-pills">
            <span><i class="fa-solid fa-lock"></i> SSL Encrypted</span>
            <span><i class="fa-solid fa-user-check"></i> Manual Review</span>
            <span><i class="fa-solid fa-shield"></i> Safe &amp; Secure</span>
        </div>
    </section>

    <section class="ocwd-help">
        <div class="ocwd-help-copy">
            <i class="fa-solid fa-headset"></i>
            <div>
                <strong>Need Help?</strong>
                <p>Our support team is here to assist you with your withdrawal.</p>
            </div>
        </div>
        <a href="{{ route('user.support.index') }}" class="ocwd-help-btn">
            <i class="fa-solid fa-headset"></i> Contact Support
        </a>
    </section>

    <section class="ocwd-schemes">
        <div>
            <strong>Scheme Capital</strong>
            <p>Active scheme capital returns to Main Wallet from Investment Schemes (up to 72h), then withdraw externally.</p>
        </div>
        <a href="{{ route('user.clubs.index') }}" class="ocwd-help-btn">Open Schemes</a>
    </section>

    <section class="ocwd-history" id="ocwd-history">
        <header class="ocwd-history-head">
            <h2>Withdrawal History</h2>
        </header>
        <div class="ocwd-history-table">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Fee</th>
                        <th>You Receive</th>
                        <th>Network</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($withdrawals as $item)
                        <tr>
                            <td data-label="Type">{{ $item->type->value === 'reward' ? 'Main Wallet' : ucfirst($item->type->value) }}</td>
                            <td data-label="Amount">{{ number_format($item->amount, 2) }} USDT</td>
                            <td data-label="Fee">{{ number_format($item->fee, 2) }} USDT</td>
                            <td data-label="You Receive">{{ number_format($item->netAmount(), 2) }} USDT</td>
                            <td data-label="Network">{{ $item->network ?? '—' }}</td>
                            <td data-label="Status">@include('user.partials.tx-status', ['status' => $item->status->value])</td>
                            <td data-label="Date">{{ $item->created_at->format('M d, Y h:i A') }}</td>
                            <td data-label="Action" class="table-actions">
                                <a href="{{ route('user.withdraw.show', $item) }}" class="action-btn action-btn--view action-btn--pill">View</a>
                                @if ($item->status->value === 'pending')
                                    <form method="POST" action="{{ route('user.withdraw.cancel', $item) }}" class="inline-form" data-confirm="Cancel this withdrawal?" data-confirm-ok="Yes, Cancel" data-confirm-variant="danger">
                                        @csrf
                                        <button type="submit" class="action-btn action-btn--delete action-btn--pill">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-cell">No withdrawals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $withdrawals->links() }}
        </div>
    </section>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/withdraw-mock.css') }}?v={{ @filemtime(public_path('css/withdraw-mock.css')) ?: time() }}">
@endpush

@push('scripts')
<script src="{{ asset('js/withdraw-mock.js') }}?v={{ @filemtime(public_path('js/withdraw-mock.js')) ?: time() }}" defer></script>
@endpush

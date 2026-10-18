@extends('layouts.dashboard')

@section('title', 'RPSV — Rock Paper Scissors Versus')
@section('page_title', 'RPSV')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home')

@section('content')
@php
    $symbol = function (string $c) {
        $c = strtoupper($c);
        if ($c === 'NGN') {
            return '₦';
        }
        if (in_array($c, ['USD', 'USDT', 'USDC'], true)) {
            return '$';
        }

        return '';
    };
    $formatStake = function (string $c, float $amount) use ($symbol) {
        $prefix = $symbol($c);
        if ($prefix !== '') {
            return $prefix.number_format($amount, 2);
        }

        return rtrim(rtrim(number_format($amount, 8, '.', ''), '0'), '.').' '.strtoupper($c);
    };
    $rpsvAssetVer = @filemtime(public_path('images/rpsv/logo-hex.png')) ?: time();
    $img = fn ($f) => asset('images/rpsv/'.$f).'?v='.$rpsvAssetVer;
    $user = auth()->user();
    $invitePopup = session('rpsv_invite_popup');
    $incomingBoot = $incoming->map(fn ($inv) => [
        'id' => $inv->id,
        'from' => $inv->sender?->name ?: 'Player',
        'label' => $inv->currency.' '.$formatStake((string) $inv->currency, (float) $inv->stake),
    ])->values()->all();
@endphp
<div class="dash-page rpsv-lobby"
     data-rpsv-lobby
     data-queue-status="{{ route('user.rpsv.queue.status') }}"
     data-csrf="{{ csrf_token() }}"
     data-invite-tab="{{ $inviteTab ?? 'send' }}"
>
    <script type="application/json" data-rpsv-stakes-boot>{!! json_encode($stakesByCurrency ?? ['USD' => $stakes, 'NGN' => $stakes], JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/json" data-rpsv-crypto-boot>{!! json_encode($cryptoWallets ?? [], JSON_UNESCAPED_UNICODE) !!}</script>
    @if(is_array($invitePopup))
        <script type="application/json" data-rpsv-invite-popup-boot>{!! json_encode($invitePopup, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}</script>
    @endif
    @if(count($incomingBoot))
        <script type="application/json" data-rpsv-incoming-boot>{!! json_encode($incomingBoot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}</script>
    @endif

    <header class="rpsv-topbar">
        <div class="rpsv-brand">
            <img src="{{ $img('logo-hex.png') }}" alt="" class="rpsv-brand-ico" width="52" height="52">
            <div>
                <strong>RPSV™</strong>
                <span>Rock Paper Scissors Versus</span>
            </div>
        </div>

        @php
            $rpsvCategory = $activeCategory ?? 'crypto';
            $rpsvActive = $activeCurrency ?? 'USD';
            $rpsvIsFiat = $rpsvCategory === 'fiat' || strtoupper($rpsvActive) === 'NGN';
            $rpsvActiveWallet = collect($cryptoWallets ?? [])->firstWhere('match_code', $rpsvActive)
                ?? collect($cryptoWallets ?? [])->firstWhere('code', $rpsvActive)
                ?? collect($cryptoWallets ?? [])->firstWhere('code', 'USDT');
            $rpsvWalletCode = $rpsvIsFiat ? 'NGN' : ($rpsvActiveWallet['code'] ?? 'USDT');
            $rpsvWalletIcon = $rpsvIsFiat
                ? asset('images/wallets/ngn.svg')
                : ($rpsvActiveWallet['icon'] ?? asset('images/wallets/usdt.svg'));
            $rpsvWalletValue = $rpsvIsFiat
                ? ($ngnFormatted ?? ('₦'.number_format((float) ($ngnBalance ?? 0), 2)))
                : ($rpsvActiveWallet['formatted'] ?? '$0.00');
            $rpsvDepositUrl = $rpsvIsFiat
                ? route('user.deposit.index', ['currency' => 'USDT'])
                : route('user.deposit.index', ['currency' => $rpsvWalletCode]);
            $rpsvFormCurrency = $rpsvIsFiat ? 'NGN' : ($rpsvActiveWallet['match_code'] ?? 'USD');
        @endphp
        <div class="rpsv-top-center" data-rpsv-currency-panel data-category="{{ $rpsvIsFiat ? 'fiat' : 'crypto' }}">
            <div class="rpsv-currency-tabs" data-rpsv-category>
                <span class="rpsv-currency-label">Select Currency</span>
                <div class="rpsv-currency-btns">
                    <button type="button" class="{{ $rpsvIsFiat ? '' : 'is-active' }}" data-category="crypto">CRYPTO</button>
                    <button type="button" class="{{ $rpsvIsFiat ? 'is-active' : '' }}" data-category="fiat">FIAT</button>
                </div>
            </div>

            <div class="rpsv-wallet-block" data-rpsv-wallet-block>
                <span class="rpsv-wallet-heading" data-rpsv-wallet-heading>{{ $rpsvIsFiat ? 'FIAT WALLET' : 'CRYPTO WALLET' }}</span>
                <div class="rpsv-wallet-bar">
                    <button type="button" class="rpsv-wallet-coin" data-rpsv-coin-open {{ $rpsvIsFiat ? 'hidden' : '' }} aria-haspopup="listbox" aria-expanded="false">
                        <img src="{{ $rpsvWalletIcon }}" alt="" width="22" height="22" data-rpsv-coin-icon>
                        <strong data-rpsv-coin-code>{{ $rpsvWalletCode }}</strong>
                        <i class="fa-solid fa-caret-down" aria-hidden="true"></i>
                    </button>
                    <div class="rpsv-wallet-coin rpsv-wallet-coin--static" data-rpsv-fiat-coin {{ $rpsvIsFiat ? '' : 'hidden' }}>
                        <img src="{{ asset('images/wallets/ngn.svg') }}" alt="" width="22" height="22" onerror="this.style.display='none'">
                        <strong>NGN</strong>
                    </div>
                    <span class="rpsv-wallet-sep" aria-hidden="true"></span>
                    <strong class="rpsv-wallet-bal" data-rpsv-wallet-value>{{ $rpsvWalletValue }}</strong>
                    <a href="{{ $rpsvDepositUrl }}" class="rpsv-wallet-plus" title="Deposit" data-rpsv-deposit>+</a>
                </div>

                <div class="rpsv-coin-menu" data-rpsv-coin-menu hidden role="listbox">
                    @foreach(($cryptoWallets ?? []) as $wallet)
                        <button
                            type="button"
                            class="rpsv-coin-option {{ ($wallet['match_code'] ?? '') === $rpsvFormCurrency ? 'is-selected' : '' }}"
                            data-code="{{ $wallet['code'] }}"
                            data-match-code="{{ $wallet['match_code'] }}"
                            data-formatted="{{ $wallet['formatted'] }}"
                            data-icon="{{ $wallet['icon'] }}"
                            role="option"
                        >
                            <img src="{{ $wallet['icon'] }}" alt="" width="22" height="22">
                            <span>
                                <strong>{{ $wallet['code'] }}</strong>
                                <small>{{ $wallet['formatted'] }}</small>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <span class="rpsv-wallet-alt" hidden data-usd-bal>${{ number_format($usdBalance, 2) }}</span>
            <span class="rpsv-wallet-alt" hidden data-ngn-bal>{{ $ngnFormatted ?? ('₦'.number_format($ngnBalance, 2)) }}</span>
            <span class="rpsv-wallet-alt" hidden data-active-currency>{{ $rpsvFormCurrency }}</span>
            <span class="rpsv-wallet-alt" hidden data-select-currency-url>{{ route('user.wallets.select-currency.store') }}</span>
        </div>

        <div class="rpsv-userchip">
            <div class="rpsv-userchip-av">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            <div>
                <strong>{{ $user->name }}</strong>
                <span>Member {{ $user->member_id ?? $user->id }}</span>
            </div>
        </div>
    </header>

    @if($queued)
        <div class="rpsv-queue-banner">
            <div>
                <strong>Searching for opponent…</strong>
                <span>{{ $queued->currency }} · stake {{ $formatStake((string) $queued->currency, (float) $queued->stake) }}</span>
            </div>
            <form method="POST" action="{{ route('user.rpsv.queue.cancel') }}">@csrf
                <button type="submit" class="rpsv-btn-ghost">Cancel &amp; refund</button>
            </form>
        </div>
    @endif

    <section class="rpsv-invites" data-incoming-wrap @if($incoming->isEmpty()) hidden @endif>
        <h3>Incoming invitations</h3>
        <div data-incoming-list>
            @foreach($incoming as $inv)
                <div class="rpsv-invite-row">
                    <div>
                        <strong>{{ $inv->sender?->name }}</strong>
                        @if($inv->sender?->member_id)<em>{{ $inv->sender->member_id }}</em>@endif
                        <span>{{ $inv->currency }} {{ $formatStake((string) $inv->currency, (float) $inv->stake) }}</span>
                    </div>
                    <div class="rpsv-invite-actions">
                        <form method="POST" action="{{ route('user.rpsv.invite.accept', $inv) }}" data-rpsv-invite-accept="1">@csrf
                            <button class="rpsv-btn-gold" type="submit">Accept</button>
                        </form>
                        <form method="POST" action="{{ route('user.rpsv.invite.decline', $inv) }}">@csrf
                            <button class="rpsv-btn-ghost">Decline</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="rpsv-grid">
        {{-- Quick Match --}}
        <section class="rpsv-card rpsv-card--quick">
            <div class="rpsv-card-banner">
                <img src="{{ $img('banner-quick.png') }}" alt="" width="1100" height="420" loading="lazy" decoding="async">
                <div class="rpsv-card-banner-copy">
                    <h2>QUICK MATCH</h2>
                    <p>Play against a random opponent</p>
                </div>
            </div>

            <div class="rpsv-steps-bar" aria-label="Match steps">
                <div class="is-active"><i>1</i><span>Select Bet Amount</span></div>
                <div><i>2</i><span>Wait to be Matched</span></div>
                <div><i>3</i><span>Game Starts</span></div>
            </div>

            <form method="POST" action="{{ route('user.rpsv.queue') }}" class="rpsv-quick-form">
                @csrf
                <input type="hidden" name="currency" value="{{ $rpsvFormCurrency }}" data-rpsv-currency-input>
                <div class="rpsv-stakes" data-rpsv-stakes>
                    @foreach(($stakesByCurrency[$rpsvIsFiat ? 'NGN' : 'USD'] ?? $stakes) as $stake)
                        <label class="{{ abs($stake - ($rpsvIsFiat ? 25 : 10)) < 0.001 ? 'is-active' : '' }}" data-stake-currency="{{ $rpsvIsFiat ? 'NGN' : 'USD' }}">
                            <input type="radio" name="stake" value="{{ $stake }}" {{ abs($stake - ($rpsvIsFiat ? 25 : 10)) < 0.001 ? 'checked' : '' }} required>
                            <span>
                                <em data-cur>{{ $rpsvIsFiat ? '₦' : '$' }}</em>{{ $stake < 1 ? number_format($stake, 2) : number_format($stake, 0) }}
                                @if(abs($stake - ($rpsvIsFiat ? 25 : 10)) < 0.001)<b class="rpsv-check">✓</b>@endif
                            </span>
                        </label>
                    @endforeach
                </div>
                <div class="rpsv-calc rpsv-calc--quick">
                    <span>YOU WILL STAKE <strong data-rpsv-stake-show>{{ $rpsvIsFiat ? '₦25.00' : '$10.00' }}</strong></span>
                    <span class="rpsv-calc-arrow">»</span>
                    <span>POTENTIAL WIN <strong class="is-win" data-rpsv-win-show>{{ $rpsvIsFiat ? '₦47.50' : '$19.00' }}</strong></span>
                </div>
                <button type="submit" class="rpsv-btn-gold rpsv-btn-block" @if($queued) disabled @endif>
                    <i class="fa-solid fa-bolt"></i> FIND RANDOM OPPONENT
                </button>
            </form>
        </section>

        {{-- Invite --}}
        <section class="rpsv-card rpsv-card--invite">
            <div class="rpsv-card-banner">
                <img src="{{ $img('banner-invite.png') }}" alt="" width="1100" height="420" loading="lazy" decoding="async">
                <div class="rpsv-card-banner-copy">
                    <h2>INVITE A FRIEND</h2>
                    <p>Challenge a friend to a match</p>
                </div>
            </div>

            <div class="rpsv-invite-tabs" data-invite-tabs>
                <button type="button" class="{{ ($inviteTab ?? 'send') === 'send' ? 'is-active' : '' }}" data-tab="send">INVITE BY USERNAME</button>
                <button type="button" class="{{ ($inviteTab ?? 'send') === 'mine' ? 'is-active' : '' }}" data-tab="mine">MY INVITATIONS</button>
            </div>

            <div data-tab-panel="send" @if(($inviteTab ?? 'send') === 'mine') hidden @endif>
                <form method="POST" action="{{ route('user.rpsv.invite') }}" class="rpsv-invite-form">
                    @csrf
                    <input type="hidden" name="currency" value="{{ $rpsvFormCurrency }}" data-rpsv-currency-input>
                    <label class="rpsv-field">
                        <span>ENTER USERNAME</span>
                        <span class="rpsv-field-wrap {{ $errors->has('username') ? 'is-error' : '' }}">
                            <i class="fa-solid fa-user"></i>
                            <input type="text" name="username" placeholder="Name / Member ID / Email" required maxlength="120" value="{{ old('username') }}">
                        </span>
                        @error('username')
                            <small class="rpsv-field-error">{{ $message }}</small>
                        @enderror
                    </label>
                    <label class="rpsv-field">
                        <span>ENTER CUSTOM AMOUNT (<em data-invite-cur-label>{{ $rpsvIsFiat ? 'NGN' : 'USD' }}</em>)</span>
                        <select name="stake" required data-rpsv-invite-stake>
                            @foreach(($stakesByCurrency[$rpsvIsFiat ? 'NGN' : 'USD'] ?? $stakes) as $stake)
                                <option value="{{ $stake }}" @selected(abs($stake - ($rpsvIsFiat ? 25 : 10)) < 0.001)>
                                    {{ $stake < 1 ? number_format($stake, 2) : number_format($stake, 0) }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <div class="rpsv-calc rpsv-calc--invite">
                        <p>YOU WILL STAKE <strong data-invite-stake>$20.00</strong> = YOUR FRIEND WILL STAKE <strong data-invite-friend>$20.00</strong></p>
                        <div class="rpsv-calc-grid">
                            <div><span>TOTAL POT</span><strong data-invite-pot>$40.00</strong></div>
                            <div><span>YOU CAN WIN</span><strong class="is-win" data-invite-win>$38.00</strong></div>
                            <div><span>PLATFORM FEE (5%)</span><strong data-invite-fee>$2.00</strong></div>
                        </div>
                    </div>
                    <button type="submit" class="rpsv-btn-purple rpsv-btn-block">
                        <i class="fa-solid fa-paper-plane"></i> SEND INVITATION
                    </button>
                    <p class="rpsv-invite-note">Stake refunded if invite is declined or expires.</p>
                </form>
            </div>

            <div data-tab-panel="mine" @if(($inviteTab ?? 'send') !== 'mine') hidden @endif>
                <div class="rpsv-mine-block">
                    <h3>Received</h3>
                    <div data-incoming-mine>
                        @forelse($incoming as $inv)
                            <div class="rpsv-invite-row">
                                <div>
                                    <strong>{{ $inv->sender?->name }}</strong>
                                    <span>{{ $inv->currency }} {{ $formatStake((string) $inv->currency, (float) $inv->stake) }}</span>
                                </div>
                                <div class="rpsv-invite-actions">
                                    <form method="POST" action="{{ route('user.rpsv.invite.accept', $inv) }}" data-rpsv-invite-accept="1">@csrf
                                        <button class="rpsv-btn-gold" type="submit">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('user.rpsv.invite.decline', $inv) }}">@csrf
                                        <button class="rpsv-btn-ghost">Decline</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="rpsv-empty" data-incoming-empty>No incoming invitations.</p>
                        @endforelse
                    </div>
                </div>
                <div class="rpsv-mine-block">
                    <h3>Sent</h3>
                    <div data-outgoing-list>
                        @forelse($outgoing as $inv)
                            <div class="rpsv-invite-row">
                                <div>
                                    <span>To {{ $inv->receiver?->name }}@if($inv->receiver?->member_id) ({{ $inv->receiver->member_id }})@endif</span>
                                    <span>{{ $inv->currency }} {{ $formatStake((string) $inv->currency, (float) $inv->stake) }}</span>
                                </div>
                                <div class="rpsv-invite-actions">
                                    <em>Pending</em>
                                    <form method="POST" action="{{ route('user.rpsv.invite.cancel', $inv) }}">@csrf
                                        <button class="rpsv-btn-ghost" type="submit">Cancel</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="rpsv-empty" data-outgoing-empty>No outgoing invitations.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="rpsv-infobar">
        <div><i class="fa-solid fa-shield-halved"></i><div><strong>Fair &amp; Secure</strong><span>100% fair gameplay</span></div></div>
        <div><i class="fa-solid fa-hand-pointer"></i><div><strong>Turn-by-turn</strong><span>Player 1 chooses · then Player 2 · then reveal</span></div></div>
        <div><i class="fa-solid fa-trophy"></i><div><strong>Match Format</strong><span>Both Start · First to 2 wins · Draws continue</span></div></div>
        <div><i class="fa-solid fa-coins"></i><div><strong>Payout</strong><span>Winner gets 1.90× your stake</span></div></div>
        <div><i class="fa-solid fa-users"></i><div><strong>Real Players Only</strong><span>No bots. No spectators.</span></div></div>
    </footer>

    <div class="rpsv-popup" data-rpsv-popup hidden>
        <div class="rpsv-popup-card">
            <strong data-popup-title></strong>
            <p data-popup-message></p>
        </div>
    </div>
</div>
@endsection

@push('styles')
@vite(['resources/css/rpsv.css'])
@endpush
@push('scripts')
@vite(['resources/js/rpsv.js'])
@endpush

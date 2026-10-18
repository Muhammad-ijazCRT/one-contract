@extends('layouts.dashboard')

@section('title', 'Prediction Duel — One-Contract™')
@section('page_title', 'Prediction Duel')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home pred-mode')
@section('layout_class', 'pred-layout')

@push('styles')
@vite(['resources/css/prediction.css'])
@endpush

@section('content')
@php
    $s = $state;
    $cube = asset('images/azubogu/cube.svg');
    $payout = number_format((float) $s['config']['payout'], 2);
    $trendColumns = $s['trend_road'] ?? \App\Support\PredictionTrendRoad::fromHistory($s['history'] ?? []);
    $betLeft = (int) ($s['round']['betting_seconds_left'] ?? 0);
    $roundLeft = max(0, (int) ($s['round']['round_seconds_left'] ?? 0));
    $isIdle = ! empty($s['idle']);
    $bettingOpen = ! $isIdle && $betLeft > 0;
    $phaseLabel = $isIdle ? 'WAITING' : ($bettingOpen ? 'BETTING CLOSES' : 'RESULT REVEAL');
    $timerSecs = $bettingOpen ? $betLeft : $roundLeft;
    $timerText = $isIdle ? '—' : sprintf('%02d:%02d', intdiv($timerSecs, 60), $timerSecs % 60);
    $revealOutcome = $s['round']['winning_outcome'] ?? null;
@endphp
<div class="pred-page oc-pred" id="pred-app"
     data-state-url="{{ route('user.prediction.state') }}"
     data-bet-url="{{ route('user.prediction.bet') }}"
     data-cancel-url="{{ route('user.prediction.cancel') }}"
     data-csrf="{{ csrf_token() }}">

    {{-- Round stats kept for live JS updates, hidden from UI --}}
    <section class="ocpd-stats" aria-hidden="true" hidden>
        <div class="ocpd-stat-cell">
            <img src="{{ $cube }}" alt="" class="ocpd-stat-cube" width="28" height="28">
            <span>ROUND</span>
            <strong id="pred-round" class="is-gold">#{{ $s['round']['number'] ?? '—' }}</strong>
        </div>
        <div class="ocpd-stat-cell">
            <img src="{{ $cube }}" alt="" class="ocpd-stat-cube" width="28" height="28">
            <span>PLAYERS</span>
            <strong id="pred-players-wrap">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
                <span id="pred-players">{{ $s['stats']['total_players'] ?? 0 }}</span>
            </strong>
        </div>
        <div class="ocpd-stat-cell">
            <img src="{{ $cube }}" alt="" class="ocpd-stat-cube" width="28" height="28">
            <span id="pred-phase-label">{{ $phaseLabel }}</span>
            <strong id="pred-timer" class="is-green">{{ $timerText }}</strong>
            <span class="pred-outcome-reveal {{ $revealOutcome ? 'is-visible outcome-'.$revealOutcome : '' }}" id="pred-outcome-reveal">{{ $revealOutcome }}</span>
        </div>
        <div class="ocpd-stat-cell">
            <img src="{{ $cube }}" alt="" class="ocpd-stat-cube" width="28" height="28">
            <span>TOTAL POOL</span>
            <strong class="is-gold">$<span id="pred-pool">{{ number_format($s['stats']['total_pool'], 2) }}</span></strong>
        </div>
    </section>
    <div id="pred-place-countdown" class="ocpd-countdown-sync" hidden aria-hidden="true"></div>
    <button type="button" class="ocpd-icon-btn" id="pred-copy-round" hidden tabindex="-1" aria-hidden="true"></button>

    {{-- Past trends (pushed up under stats) --}}
    <section class="ocpd-card ocpd-trends-card">
        <div class="ocpd-card-head">
            <h2>PAST TRENDS <em>(Recent)</em></h2>
            <button type="button" class="ocpd-link" id="pred-more-stats-btn" data-ocpd-open="stats">
                <i class="fa-solid fa-chart-column"></i> MORE STATS
            </button>
        </div>
        <div class="pred-trends pred-live-trends ocpd-trends" id="pred-trends">
            @forelse($trendColumns as $col)
                <div class="pred-trend-col">
                    @foreach($col as $h)
                        @if($h === null)
                            <span class="pred-pill-spacer" aria-hidden="true"></span>
                        @else
                            <span class="pred-pill outcome-{{ $h }}">{{ $h }}</span>
                        @endif
                    @endforeach
                </div>
            @empty
                <span class="pred-live-trends-empty">Waiting for results…</span>
            @endforelse
        </div>
    </section>

    {{-- Side pools + VS --}}
    <div class="ocpd-vs-row" aria-live="polite">
        <div class="ocpd-side-pool ocpd-side-pool--1">
            <span class="ocpd-dot ocpd-dot--1">1</span>
            <span class="ocpd-side-label pred-blue">1 POOL</span>
            $<span id="pred-pool-1">{{ number_format($s['stats']['pool_1'] ?? 0, 2) }}</span>
        </div>
        <div class="ocpd-vs" aria-hidden="true"><span>VS</span></div>
        <div class="ocpd-side-pool ocpd-side-pool--2">
            <span class="ocpd-dot ocpd-dot--2">2</span>
            <span class="ocpd-side-label pred-yellow">2 POOL</span>
            $<span id="pred-pool-2">{{ number_format($s['stats']['pool_2'] ?? 0, 2) }}</span>
        </div>
    </div>

    {{-- Bet amount --}}
    <section class="ocpd-card ocpd-bet-card">
        <h3 class="ocpd-section-label">SET YOUR AMOUNT</h3>
        @php
            $currencies = $s['currencies'] ?? [];
            $playCode = $s['play_currency'] ?? 'USDT';
            $playSymbol = $s['play_symbol'] ?? '$';
            $playMeta = collect($currencies)->firstWhere('code', $playCode) ?? [
                'code' => $playCode,
                'symbol' => $playSymbol,
                'decimals' => 2,
                'min_bet' => $s['config']['min_bet'] ?? 0.1,
                'max_bet' => $s['config']['max_bet'] ?? 50000,
                'step' => '0.01',
            ];
        @endphp
        <div class="ocpd-amount-row">
            <div class="ocpd-amount-wrap">
                <div class="ocpd-currency-picker" data-pred-currency-picker>
                    <button
                        type="button"
                        class="ocpd-currency"
                        id="pred-currency-btn"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-controls="pred-currency-menu"
                        title="Select currency"
                    >
                        <span id="pred-currency-symbol">{{ $playSymbol }}</span>
                        <i class="fa-solid fa-caret-down" aria-hidden="true"></i>
                    </button>
                    <ul class="ocpd-currency-menu" id="pred-currency-menu" role="listbox" hidden>
                        @foreach ($currencies as $cur)
                            <li role="option">
                                <button
                                    type="button"
                                    class="ocpd-currency-option {{ ($cur['code'] ?? '') === $playCode ? 'is-selected' : '' }}"
                                    data-currency="{{ $cur['code'] }}"
                                    data-symbol="{{ $cur['symbol'] }}"
                                    data-decimals="{{ $cur['decimals'] }}"
                                    data-min="{{ $cur['min_bet'] }}"
                                    data-max="{{ $cur['max_bet'] }}"
                                    data-step="{{ $cur['step'] }}"
                                    data-balance="{{ $cur['balance'] }}"
                                >
                                    <img src="{{ $cur['icon'] }}" alt="" width="22" height="22">
                                    <span class="ocpd-currency-option-meta">
                                        <strong>{{ $cur['code'] }}</strong>
                                        <small>{{ $cur['symbol'] }} · {{ $cur['formatted'] }}</small>
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <input type="number" id="pred-amount"
                       step="{{ $playMeta['step'] ?? '0.01' }}"
                       min="{{ $playMeta['min_bet'] ?? $s['config']['min_bet'] }}"
                       max="{{ $playMeta['max_bet'] ?? $s['config']['max_bet'] }}"
                       value="{{ number_format(min(50, (float) ($playMeta['max_bet'] ?? $s['config']['max_bet'])), min(2, (int) ($playMeta['decimals'] ?? 2)), '.', '') }}"
                       aria-label="Bet amount"
                       data-play-currency="{{ $playCode }}">
            </div>
            <div class="pred-quick ocpd-quick">
                <button type="button" data-mult="0.5" title="Half bet">1/2</button>
                <button type="button" data-mult="2" title="Double bet">x2</button>
            </div>
        </div>

        <h3 class="ocpd-section-label is-center">CHOOSE YOUR PREDICTION</h3>
        <div class="ocpd-pick-row">
            <button type="button" class="pred-pick pred-pick-1 ocpd-pick ocpd-pick--1" data-outcome="1" id="pred-choice-1">
                <span class="ocpd-pick-num">1</span>
                <span class="ocpd-pick-copy">
                    <strong>AS ONE</strong>
                    <em>PAYOUT <span id="pred-mult-1">{{ $payout }}x</span></em>
                </span>
            </button>
            <button type="button" class="pred-pick pred-pick-2 ocpd-pick ocpd-pick--2" data-outcome="2" id="pred-choice-2">
                <span class="ocpd-pick-num">2</span>
                <span class="ocpd-pick-copy">
                    <strong>AS TWO</strong>
                    <em>PAYOUT <span id="pred-mult-2">{{ $payout }}x</span></em>
                </span>
            </button>
        </div>

        <div class="ocpd-bet-tools">
            <button type="button" class="ocpd-cancel" id="pred-cancel">Cancel Bet</button>
            <p class="pred-status" id="pred-status" hidden></p>
            <p class="pred-userbet" id="pred-userbet">
                @if($s['user_bet'])
                    Your bet: {{ $s['user_bet']['formatted'] ?? (($s['user_bet']['symbol'] ?? '$').number_format($s['user_bet']['amount'], 2)) }} on {{ $s['user_bet']['outcome'] }} ({{ $s['user_bet']['status'] }})
                @endif
            </p>
        </div>
    </section>

    {{-- My bets --}}
    <section class="ocpd-card ocpd-bets-card" id="pred-my-bets">
        <div class="ocpd-card-head">
            <h2>MY BETS</h2>
            <a href="#pred-my-bets" class="ocpd-link">VIEW ALL <i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <div class="ocpd-bets-table-wrap">
            <div class="ocpd-bets-head">
                <span>ROUND</span>
                <span>SELECTION</span>
                <span>AMOUNT</span>
                <span>PAYOUT</span>
                <span>STATUS</span>
                <span>RESULT</span>
                <span></span>
            </div>
            <div class="pred-recent ocpd-bets-body" id="pred-my-bets-list">
                @forelse(($s['my_bets'] ?? []) as $b)
                    <div class="ocpd-bet-row">
                        <span>#{{ $b['round'] }}</span>
                        <span><span class="pred-pill outcome-{{ $b['outcome'] }}">{{ $b['outcome'] }}</span></span>
                        <span>{{ $b['formatted'] ?? ('$'.number_format($b['amount'], 2)) }}</span>
                        <span>{{ number_format($b['payout_mult'] ?? 0, 2) }}x</span>
                        <span>
                            <em class="ocpd-status ocpd-status--{{ $b['status'] }}">{{ strtoupper($b['status']) }}</em>
                        </span>
                        <span class="{{ $b['status'] === 'won' ? 'pred-green' : ($b['status'] === 'lost' ? 'pred-loss' : 'pred-muted') }}">{{ $b['result'] }}</span>
                        <span class="ocpd-row-chevron"><i class="fa-solid fa-chevron-right"></i></span>
                    </div>
                @empty
                    <p class="pred-empty">No bets yet</p>
                @endforelse
            </div>
        </div>
        {{-- Keep mobile list target for JS dual-update --}}
        <div id="pred-my-bets-mobile" class="pred-hidden" aria-hidden="true"></div>
    </section>

    {{-- Footer links --}}
    <div class="ocpd-footer-links">
        <button type="button" class="ocpd-footer-card" data-ocpd-open="about">
            <span class="ocpd-footer-ico is-gold"><i class="fa-solid fa-book-open"></i></span>
            <span>
                <strong>ABOUT GAME</strong>
                <em>How to play &amp; rules</em>
            </span>
            <span class="ocpd-row-chevron"><i class="fa-solid fa-chevron-right"></i></span>
        </button>
        <button type="button" class="ocpd-footer-card" data-ocpd-open="info">
            <span class="ocpd-footer-ico is-green"><i class="fa-solid fa-circle-info"></i></span>
            <span>
                <strong>GAME INFO</strong>
                <em>Seed • Hash • Nonce</em>
            </span>
            <span class="ocpd-row-chevron"><i class="fa-solid fa-chevron-right"></i></span>
        </button>
    </div>

    {{-- Hidden / modal content for JS + details --}}
    <div class="ocpd-modal" id="ocpd-modal" hidden>
        <div class="ocpd-modal-backdrop" data-ocpd-close></div>
        <div class="ocpd-modal-panel" role="dialog" aria-modal="true">
            <button type="button" class="ocpd-modal-close" data-ocpd-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>

            <div class="ocpd-modal-pane" data-ocpd-pane="about" hidden>
                <h3>About Game</h3>
                <p>Prediction Duel is a two-outcome prediction game. The game uses a provably fair system to ensure that every round is transparent, random, and verifiable.</p>
                <p>Players predict one of two possible outcomes: 1 or 2. Each round, the selected outcome pays either {{ $payout }}× the stake, while the other outcome pays 0×.</p>
                <h4>How to Play</h4>
                <ol>
                    <li>Observe the recent outcome trend displayed on the game screen.</li>
                    <li>Choose your preferred number (1 or 2).</li>
                    <li>Enter your stake amount and place your bet before the countdown ends.</li>
                    <li>Wait for the result. If your prediction matches the winning outcome, you receive {{ $payout }}× your stake.</li>
                </ol>
            </div>

            <div class="ocpd-modal-pane" data-ocpd-pane="info" hidden>
                <h3>Game Info</h3>
                <div class="pred-info-table ocpd-info-table">
                    <div><span>Game</span><strong>Prediction Duel</strong></div>
                    <div><span>Pick</span><strong>1 or 2</strong></div>
                    <div><span>Payout</span><strong class="pred-blue">{{ $payout }}x</strong></div>
                    <div><span>Bet Limits</span><strong>${{ number_format($s['config']['min_bet'], 2) }} – ${{ number_format($s['config']['max_bet'], 2) }}</strong></div>
                    <div><span>Winning Probability</span><strong>50% / 50%</strong></div>
                    <div><span>Outcome Source</span><strong>{{ $s['config']['outcome_source'] }}</strong></div>
                    <div><span>Round Time</span><strong>{{ $s['config']['betting_seconds'] + $s['config']['settle_seconds'] }} seconds</strong></div>
                    <div><span>Betting Time</span><strong>{{ $s['config']['betting_seconds'] }} seconds</strong></div>
                    <div><span>Result Time</span><strong>{{ $s['config']['settle_seconds'] }} seconds</strong></div>
                </div>
                <div class="pred-fair-bar ocpd-fair">
                    <div class="pred-fair-item">
                        <span>Server Seed Hash</span>
                        <code id="pred-hash">{{ \Illuminate\Support\Str::limit($s['round']['server_seed_hash'] ?? '—', 16, '…') }}</code>
                        <button type="button" class="ocpd-icon-btn" data-copy="pred-hash-full"><i class="fa-regular fa-copy"></i></button>
                        <span id="pred-hash-full" class="pred-hidden">{{ $s['round']['server_seed_hash'] ?? '' }}</span>
                    </div>
                    <div class="pred-fair-item">
                        <span>Client Seed</span>
                        <code id="pred-client">{{ \Illuminate\Support\Str::limit($s['round']['client_seed'] ?? '—', 12, '…') }}</code>
                        <button type="button" class="ocpd-icon-btn" data-copy="pred-client-full"><i class="fa-regular fa-copy"></i></button>
                        <span id="pred-client-full" class="pred-hidden">{{ $s['round']['client_seed'] ?? '' }}</span>
                    </div>
                    <div class="pred-fair-item">
                        <span>Nonce</span>
                        <code id="pred-nonce">{{ $s['round']['nonce'] ?? '—' }}</code>
                        <button type="button" class="ocpd-icon-btn" data-copy="pred-nonce"><i class="fa-regular fa-copy"></i></button>
                    </div>
                    <div class="pred-fair-item" id="pred-seed-row" style="{{ ! empty($s['round']['server_seed']) ? '' : 'display:none' }}">
                        <span>Server Seed</span>
                        <code id="pred-seed">{{ ! empty($s['round']['server_seed']) ? \Illuminate\Support\Str::limit($s['round']['server_seed'], 12, '…') : '' }}</code>
                    </div>
                </div>
            </div>

            <div class="ocpd-modal-pane" data-ocpd-pane="stats" hidden>
                <h3>Live Stats</h3>
                <div class="ocpd-live-stats">
                    <div>
                        <span>Total Pool</span>
                        <strong class="pred-green">$<span id="pred-pool-mobile">{{ number_format($s['stats']['total_pool'], 2) }}</span></strong>
                    </div>
                    <div>
                        <span>Live Players</span>
                        <strong id="pred-players-mobile" class="pred-blue">{{ $s['stats']['total_players'] }}</strong>
                    </div>
                </div>
                <div class="pred-players-list" id="pred-players-list">
                    @forelse(($s['players'] ?? []) as $p)
                        <div class="pred-player-row">
                            <span class="pred-player-name" title="{{ $p['name'] }}">{{ $p['name'] }}</span>
                            <span class="pred-pill outcome-{{ $p['outcome'] }}">{{ $p['outcome'] }}</span>
                            <span class="pred-player-amt">${{ number_format($p['amount'], 2) }}</span>
                        </div>
                    @empty
                        <p class="pred-empty pred-players-empty">No players yet this round</p>
                    @endforelse
                </div>
                <div id="pred-players-list-mobile" class="pred-hidden" aria-hidden="true"></div>
                <div class="pred-dist-block">
                    <h4 class="pred-dist-title">POOL DISTRIBUTION</h4>
                    <div class="pred-dist-row">
                        <span class="pred-blue">1 Pool</span>
                        <strong id="pred-pct-1" class="pred-blue">{{ $s['stats']['pct_1'] }}%</strong>
                    </div>
                    <div class="pred-dist-row pred-dist-row-bar">
                        <span class="pred-yellow">2 Pool</span>
                        <div class="pred-poolbar" aria-hidden="true">
                            <div class="pred-pool-1" id="pred-bar-1" style="width: {{ $s['stats']['pct_1'] }}%"></div>
                            <div class="pred-pool-2" id="pred-bar-2" style="width: {{ $s['stats']['pct_2'] }}%"></div>
                        </div>
                        <strong id="pred-pct-2" class="pred-yellow">{{ $s['stats']['pct_2'] }}%</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p class="pred-disclaimer">Prediction Duel follows the active trend library. Outcomes remain 50/50 overall. Play responsibly.</p>
    <span id="pred-wallet" class="pred-hidden">${{ number_format($s['wallet'], 2) }}</span>
    <span id="pred-wallet-side" class="pred-hidden">{{ number_format($s['wallet'], 2) }}</span>
</div>
@endsection

@push('scripts')
@vite(['resources/js/prediction.js'])
@endpush

@extends('layouts.dashboard')

@section('title', 'Azubogu Prediction Game — One-Contract™')
@section('page_title', 'Azubogu Game')
@section('hide_page_heading', true)
@section('body_class', 'azu-mode')
@section('layout_class', 'azu-layout')

@section('content')
@php
    $s = $state;
    $cube = asset('images/azubogu/cube.svg');
    $numTone = [
        1 => '045', 4 => '045', 7 => '045',
        2 => '140', 3 => '140', 12 => '140',
        5 => '000', 6 => '000', 9 => '000',
        8 => '190', 10 => '190', 11 => '190',
    ];
@endphp
<div class="azu-page" id="azu-app"
     data-state-url="{{ route('user.azubogu.state') }}"
     data-bet-url="{{ route('user.azubogu.bet') }}"
     data-cancel-url="{{ route('user.azubogu.cancel') }}"
     data-csrf="{{ csrf_token() }}">

    {{-- Top stats --}}
    <section class="azu-stats" aria-label="Round stats">
        <div class="azu-stat">
            <span>ROUND</span>
            <strong id="azu-round" class="is-gold">#{{ $s['round']['round_number'] ?? '—' }}</strong>
        </div>
        <div class="azu-stat">
            <span>PLAYERS</span>
            <strong id="azu-players-wrap">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
                <span id="azu-players">{{ $s['stats']['players'] ?? 0 }}</span>
            </strong>
        </div>
        <div class="azu-stat">
            <span id="azu-phase-label">RESULT REVEAL</span>
            <strong id="azu-timer" class="is-green">00:00</strong>
        </div>
        <div class="azu-stat">
            <span>TOTAL POOL</span>
            <strong id="azu-pool" class="is-gold">${{ number_format($s['stats']['total_pool'] ?? 0, 2) }}</strong>
        </div>
    </section>

    {{-- Pick numbers --}}
    <section class="azu-card azu-pick-card">
        <div class="azu-card-head">
            <h2>PICK 1 TO 3 NUMBERS</h2>
            <span class="azu-sel-count" id="azu-sel-count">0/3 SELECTED</span>
        </div>

        <div class="azu-numbers" id="azu-numbers" role="group" aria-label="Number grid">
            @for ($n = 1; $n <= 12; $n++)
                <button type="button"
                        class="azu-num tone-{{ $numTone[$n] }}"
                        data-num="{{ $n }}"
                        aria-pressed="false">{{ $n }}</button>
            @endfor
        </div>

        <div class="azu-legend" id="azu-legend">
            <div class="azu-leg azu-leg-190" data-group="1.90x">
                <div class="azu-leg-top">
                    <b>1.90x</b>
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                </div>
                <span>3 NUMBERS</span>
                <div class="azu-dots" aria-hidden="true"><i></i><i></i><i></i></div>
                <div class="azu-leg-nums" hidden></div>
            </div>
            <div class="azu-leg azu-leg-140" data-group="1.40x">
                <div class="azu-leg-top">
                    <b>1.40x</b>
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                </div>
                <span>3 NUMBERS</span>
                <div class="azu-dots" aria-hidden="true"><i></i><i></i><i></i></div>
                <div class="azu-leg-nums" hidden></div>
            </div>
            <div class="azu-leg azu-leg-045" data-group="0.45x">
                <div class="azu-leg-top">
                    <b>0.45x</b>
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                </div>
                <span>3 NUMBERS</span>
                <div class="azu-dots" aria-hidden="true"><i></i><i></i><i></i></div>
                <div class="azu-leg-nums" hidden></div>
            </div>
            <div class="azu-leg azu-leg-000" data-group="0x">
                <div class="azu-leg-top">
                    <b>0x</b>
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                </div>
                <span>3 NUMBERS</span>
                <div class="azu-dots" aria-hidden="true"><i></i><i></i><i></i></div>
                <div class="azu-leg-nums" hidden></div>
            </div>
        </div>

        <p class="azu-rule">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            Each round, 3 numbers get 1.90x, 3 numbers get 1.40x, 3 numbers get 0.45x, and 3 numbers get 0x.
        </p>

        <span id="azu-selected" class="azu-hidden" aria-hidden="true"></span>
    </section>

    {{-- Bet amount --}}
    <section class="azu-card azu-bet-card">
        <h3 class="azu-section-label">SET YOUR AMOUNT</h3>
        <div class="azu-amount-row">
            <div class="azu-amount-wrap">
                <span class="azu-currency">$</span>
                <input type="number" id="azu-amount" class="azu-input"
                       min="{{ $s['config']['min_bet'] }}"
                       max="{{ $s['config']['max_bet'] }}"
                       step="0.01"
                       value="1.00"
                       aria-label="Bet amount in USD">
            </div>
            <div class="azu-quick">
                <button type="button" class="azu-chip" data-mult="2" title="Double bet">2x</button>
                <button type="button" class="azu-chip" data-mult="0.5" title="Half bet">1/2</button>
                <button type="button" class="azu-chip azu-chip-free" id="azu-free-btn" title="Play with $0">$0</button>
            </div>
        </div>
        <button type="button" class="azu-btn-cancel" id="azu-cancel" hidden>Cancel Bet</button>
        <p class="azu-bet-status" id="azu-bet-status"></p>
        <p class="azu-error" id="azu-error" hidden></p>
    </section>

    {{-- Round history --}}
    <section class="azu-card azu-history-card">
        <div class="azu-card-head">
            <h2>ROUND HISTORY</h2>
            <a href="{{ route('user.azubogu.history') }}" class="azu-link">VIEW ALL <i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <div class="azu-history-wrap">
            <table class="azu-history">
                <thead>
                    <tr>
                        <th>TIME</th>
                        <th>ROUND</th>
                        <th class="c-190">1.90x</th>
                        <th class="c-140">1.40x</th>
                        <th class="c-045">0.45x</th>
                        <th class="c-0">0x</th>
                    </tr>
                </thead>
                <tbody id="azu-history-body">
                    @forelse (array_slice($s['history'] ?? [], 0, 5) as $h)
                        <tr>
                            <td>{{ $h['time'] }}</td>
                            <td>#{{ $h['round_number'] }}</td>
                            <td class="c-190">{{ implode(', ', $h['groups']['1.90x'] ?? []) }}</td>
                            <td class="c-140">{{ implode(', ', $h['groups']['1.40x'] ?? []) }}</td>
                            <td class="c-045">{{ implode(', ', $h['groups']['0.45x'] ?? []) }}</td>
                            <td class="c-0">{{ implode(', ', $h['groups']['0x'] ?? []) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="azu-empty">No completed rounds yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="azu-fair-note"><i class="fa-solid fa-shield-halved"></i> All results use a provably fair system.</p>
    </section>

    {{-- My bets (compact, below mock primary stack) --}}
    <section class="azu-card azu-mybets-card">
        <div class="azu-card-head">
            <h2>MY BETS</h2>
            <a href="{{ route('user.azubogu.my-bets') }}" class="azu-link">VIEW ALL <i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <div class="azu-history-wrap">
            <table class="azu-history azu-mybets">
                <thead>
                    <tr>
                        <th>ROUND</th>
                        <th>NUMBERS</th>
                        <th>BET</th>
                        <th>RESULT</th>
                        <th>PAYOUT</th>
                    </tr>
                </thead>
                <tbody id="azu-mybets-body">
                    @forelse (array_slice($s['my_bets'] ?? [], 0, 5) as $bet)
                        <tr>
                            <td>#{{ $bet['round'] }}</td>
                            <td>{{ implode(', ', $bet['numbers'] ?? []) }}</td>
                            <td>${{ number_format($bet['amount'], 2) }}</td>
                            <td>
                                @if (($bet['status'] ?? '') === 'won')
                                    <span class="azu-outcome won">Won {{ number_format($bet['multiplier'], 2) }}×</span>
                                @elseif (($bet['status'] ?? '') === 'lost')
                                    <span class="azu-outcome lost">Lost</span>
                                @else
                                    <span class="azu-outcome active">Active</span>
                                @endif
                            </td>
                            <td>
                                @if (($bet['status'] ?? '') === 'active')
                                    —
                                @else
                                    ${{ number_format($bet['payout'], 2) }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="azu-empty">No personal bets yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Footer nav --}}
    <div class="azu-footer-links">
        <button type="button" class="azu-footer-card" data-azu-open="howto">
            <span class="azu-footer-ico is-gold"><i class="fa-solid fa-book-open"></i></span>
            <span>
                <strong>HOW TO PLAY</strong>
            </span>
            <span class="azu-chevron"><i class="fa-solid fa-chevron-right"></i></span>
        </button>
        <button type="button" class="azu-footer-card" data-azu-open="about">
            <span class="azu-footer-ico is-green"><i class="fa-solid fa-circle-info"></i></span>
            <span>
                <strong>ABOUT GAME</strong>
                <em>Seed · Hash · Nonce</em>
            </span>
            <span class="azu-chevron"><i class="fa-solid fa-chevron-right"></i></span>
        </button>
    </div>

    {{-- Modal --}}
    <div class="azu-modal" id="azu-modal" hidden>
        <div class="azu-modal-backdrop" data-azu-close></div>
        <div class="azu-modal-panel" role="dialog" aria-modal="true">
            <button type="button" class="azu-modal-close" data-azu-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>

            <div class="azu-modal-pane" data-azu-pane="howto" hidden>
                <h3>How to play</h3>
                <p>Each round contains the numbers 1–12. The system randomly assigns:</p>
                <ul>
                    <li>3 numbers → <strong>1.90×</strong> multiplier</li>
                    <li>3 numbers → <strong>1.40×</strong> multiplier</li>
                    <li>3 numbers → <strong>0.45×</strong> multiplier</li>
                    <li>3 numbers → <strong>0×</strong> multiplier</li>
                </ul>
                <p>Each number belongs to only one multiplier group per round.</p>
                <p>Set your bet amount, then select 1–3 numbers — your bet is placed automatically when you pick. After betting closes, each number gets one multiplier. Your payout = bet × (multipliers of your picks multiplied together). Any <strong>0×</strong> on a selected number → payout <strong>$0</strong>.</p>
                <ul>
                    <li>$10 × 1.90 = <strong>$19.00</strong></li>
                    <li>$10 × 1.90 × 1.40 = <strong>$26.60</strong></li>
                    <li>$10 × 1.40 × 1.90 × 0.45 = <strong>$11.97</strong></li>
                    <li>$10 × 1.90 × 1.40 × 0 = <strong>$0</strong></li>
                </ul>
            </div>

            <div class="azu-modal-pane" data-azu-pane="about" hidden>
                <h3>About Game</h3>
                <h4 class="azu-about-title">AZUBOGU GAME™</h4>
                <p class="azu-about-subtitle">A Provably Fair Random Number Game</p>
                <p>AZUBOGU GAME™ is a unique, provably fair random number game. Players predict numbers from 1 to 12. Every round, the system randomly assigns one of four multiplier categories to each number. Players may select 1, 2, or 3 numbers, and their payout is determined by multiplying the multipliers assigned to all selected numbers.</p>

                <h5 class="azu-about-heading">Provably Fair</h5>
                <div class="azu-fair-bar">
                    <div class="azu-fair-item">
                        <span>Server Seed Hash</span>
                        <code id="azu-hash">{{ \Illuminate\Support\Str::limit($s['round']['server_seed_hash'] ?? '—', 28, '…') }}</code>
                    </div>
                    <div class="azu-fair-item">
                        <span>Nonce</span>
                        <code id="azu-nonce">{{ $s['round']['nonce'] ?? '—' }}</code>
                    </div>
                    <div class="azu-fair-item" id="azu-seed-wrap" style="{{ !empty($s['round']['server_seed']) ? '' : 'display:none' }}">
                        <span>Server Seed</span>
                        <code id="azu-seed">{{ !empty($s['round']['server_seed']) ? \Illuminate\Support\Str::limit($s['round']['server_seed'], 16, '…') : '' }}</code>
                    </div>
                    <span class="azu-verified" id="azu-verified">
                        <i class="fa-solid fa-circle-check"></i>
                        {{ ($s['round']['status'] ?? '') === 'betting' ? 'LOCKED' : 'VERIFIED' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>

<script type="application/json" id="azu-initial-state">@json($s)</script>
<span id="azu-wallet" class="azu-hidden" hidden aria-hidden="true">{{ number_format($s['wallet'] ?? 0, 2) }}</span>
@endsection

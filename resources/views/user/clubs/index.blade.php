@extends('layouts.dashboard')

@section('title', 'Investment Scheme — One-Contract™')
@section('page_title', 'Investment Scheme')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--invest-scheme')

@php
    $totalInvested = $investments->sum(fn ($inv) => (float) $inv->amount);
    $imgClub = fn ($file) => asset('images/clubs/'.$file);
    $imgInv = fn ($file) => asset('images/invest/'.$file);
    $meta = [
        'binary' => [
            'image' => 'scheme-lion.png',
            'tone' => 'gold',
            'slogan' => 'The King of Returns',
            'title' => 'Lion Investment',
        ],
        'forex' => [
            'image' => 'scheme-man.png',
            'tone' => 'green',
            'slogan' => 'Smart & Steady Growth',
            'title' => 'Man Investment',
        ],
        'casino' => [
            'image' => 'scheme-ox.png',
            'tone' => 'orange',
            'slogan' => 'Strong & Reliable',
            'title' => 'Ox Investment',
        ],
        'crypto' => [
            'image' => 'scheme-eagle.png',
            'tone' => 'blue',
            'slogan' => 'Soar to New Heights',
            'title' => 'Eagle Investment',
        ],
        'polymarket' => [
            'image' => 'scheme-gorilla.png',
            'tone' => 'purple',
            'slogan' => 'Power in Numbers',
            'title' => 'Gorilla Investment',
        ],
    ];
    $order = ['binary', 'forex', 'casino', 'crypto', 'polymarket'];
    $sortedCatalog = $catalog->sortBy(function ($club) use ($order) {
        $slug = $club->slug instanceof \BackedEnum ? $club->slug->value : (string) $club->slug;

        return array_search($slug, $order, true) !== false ? array_search($slug, $order, true) : 99;
    })->values();
@endphp

@section('content')
<div class="dash-page oc-ism" data-oc-ism>

    <section class="ocism-hero" aria-label="Investment Scheme">
        <div class="ocism-hero-copy">
            <h1>Investment<em>Scheme</em></h1>
            <p>Choose a plan that suits you and grow your wealth with smart investments.</p>
        </div>
        <div class="ocism-hero-art">
            <img src="{{ $imgInv('hero-vault.png') }}" alt="" width="460" height="280" loading="eager" decoding="async">
        </div>
    </section>

    <section class="ocism-strip" id="invest-overview" aria-label="Balances">
        <article>
            <span>Main Wallet</span>
            <strong class="is-gold">${{ number_format($mainWalletBalance, 2) }}</strong>
            <a href="{{ route('user.deposit.index') }}">Deposit →</a>
        </article>
        <article>
            <span>Active in Schemes</span>
            <strong>${{ number_format($totalInvested, 2) }}</strong>
        </article>
        <article>
            <span>Pending Returns</span>
            <strong>${{ number_format((float) $pendingReturns->sum('amount'), 2) }}</strong>
        </article>
        <article>
            <span>Total Earned</span>
            <strong class="is-green">${{ number_format($totalEarned, 2) }}</strong>
        </article>
    </section>

    @unless($hasAcceptedTerms)
        @include('user.partials.terms-gate', [
            'title' => 'Investment Schemes Terms & Conditions',
            'action' => route('user.clubs.terms'),
            'documentUrl' => route('legal.show', 'investment-clubs-terms'),
            'documentLabel' => 'Investment Schemes Terms & Conditions',
            'checkboxLabel' => 'I have read and accept the <a href="'.route('legal.show', 'investment-clubs-terms').'" target="_blank" rel="noopener noreferrer">Investment Schemes Terms &amp; Conditions</a>',
            'points' => [
                'Rewards accrue daily at each scheme rate and mature for manual claim.',
                'Platform Fee ('.\App\Services\PlatformSettings::platformFeePercent().'%) is paid from Main Wallet — never from the reward itself.',
                'Referral commissions apply on Investment Scheme rewards and Prediction gaming.',
                'Withdrawing capital stops future rewards but keeps prior matured rewards. Principal return has no Platform Fee.',
                'Investment limits: $1 – $450 per position.',
            ],
        ])
    @else
        <div class="terms-accepted-banner">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            <span class="terms-accepted-text">
                <strong>Investment Schemes Terms accepted.</strong>
                <small>You can invest in any scheme below.</small>
            </span>
            <a href="{{ route('user.rewards.claim') }}" class="btn btn-gold btn-sm">Claim Rewards</a>
            <button type="button" class="btn btn-outline btn-sm" data-open-scheme-info>How it works</button>
        </div>
    @endunless

    <div class="ocism-section-head" id="invest-schemes">
        <h2>— Our <em>Investment</em> Schemes —</h2>
        <p>All investments are simple interest. Returns are paid upon maturity.</p>
    </div>

    <section class="ocism-grid" aria-label="Investment schemes">
        @foreach($sortedCatalog as $club)
            @php
                $slug = $club->slug instanceof \BackedEnum ? $club->slug->value : (string) $club->slug;
                $cfg = $meta[$slug] ?? [
                    'image' => null,
                    'tone' => 'gold',
                    'slogan' => 'Grow with confidence',
                    'title' => $club->name,
                ];
                $clubInvestments = $investments->where('investment_club_id', $club->id);
                $rewardPct = rtrim(rtrim(number_format((float) $club->reward_percent, 2, '.', ''), '0'), '.');
                $minAmt = number_format((float) $club->min_investment, 0);
                $maxAmt = $club->max_investment ? number_format((float) $club->max_investment, 0) : '450';
                $positions = max(0, 100 - $clubInvestments->count());
                $canInvest = $hasAcceptedTerms && $clubInvestments->count() < 100;
                $inputMax = $club->max_investment
                    ? min($mainWalletBalance, (float) $club->max_investment)
                    : max($mainWalletBalance, (float) $club->min_investment);
                $insufficientFunds = $mainWalletBalance < (float) $club->min_investment;
            @endphp
            <article class="ocism-card ocism-card--{{ $cfg['tone'] }}">
                <div class="ocism-card-top">
                    <div class="ocism-card-media">
                        @if(! empty($cfg['image']))
                            <img src="{{ $imgClub($cfg['image']) }}" alt="{{ $cfg['title'] }}" loading="lazy" decoding="async">
                        @else
                            <i class="fa-solid fa-chart-line" style="display:grid;place-items:center;height:100%;color:#64748b"></i>
                        @endif
                    </div>
                    <div class="ocism-card-title">
                        <h3>{{ $cfg['title'] }}</h3>
                        <span class="ocism-slogan">{{ $cfg['slogan'] }}</span>
                        <div class="ocism-daily">
                            <span>Daily Return</span>
                            <strong>{{ $rewardPct }}%</strong>
                        </div>
                    </div>
                </div>

                <ul class="ocism-meta">
                    <li>
                        <span class="ocism-meta-l">
                            <img src="{{ $imgInv('icon-calendar.svg') }}" alt="">
                            Duration
                        </span>
                        <b>{{ (int) $club->cycle_days }} Days</b>
                    </li>
                    <li>
                        <span class="ocism-meta-l">
                            <img src="{{ $imgInv('icon-users.svg') }}" alt="">
                            Positions
                        </span>
                        <b>100 Positions</b>
                    </li>
                    <li>
                        <span class="ocism-meta-l">
                            <img src="{{ $imgInv('icon-wallet.svg') }}" alt="">
                            Max per Position
                        </span>
                        <b>${{ $maxAmt }} Max per Position</b>
                    </li>
                    <li>
                        <span class="ocism-meta-l">
                            <img src="{{ $imgInv('icon-coin.svg') }}" alt="">
                            Minimum Invest
                        </span>
                        <b>${{ $minAmt }}</b>
                    </li>
                </ul>

                <div class="ocism-action">
                    @if($canInvest)
                        <form method="POST" action="{{ route('user.clubs.invest', $club) }}">
                            @csrf
                            <label class="visually-hidden" for="invest-{{ $club->id }}">Invest amount</label>
                            <div class="ocism-amount">
                                <span>$</span>
                                <input
                                    id="invest-{{ $club->id }}"
                                    type="number"
                                    name="amount"
                                    step="0.01"
                                    min="{{ $club->min_investment }}"
                                    max="{{ $inputMax }}"
                                    placeholder="{{ $minAmt }} – {{ $maxAmt }}"
                                    required
                                    {{ $insufficientFunds ? 'disabled' : '' }}
                                >
                            </div>
                            <button type="submit" class="ocism-invest-btn" {{ $insufficientFunds ? 'disabled' : '' }}>
                                Invest Now
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </button>
                            @if($insufficientFunds)
                                <p class="ocism-hint">Insufficient main wallet balance.</p>
                            @endif
                        </form>
                    @elseif(! $hasAcceptedTerms)
                        <p class="ocism-locked">Accept Terms above to unlock investing.</p>
                    @else
                        <p class="ocism-locked">Maximum of 100 active investments reached.</p>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <div class="ocism-tip">
        <div class="ocism-tip-icon">
            <img src="{{ $imgInv('icon-gift.svg') }}" alt="">
        </div>
        <div class="ocism-tip-text">
            <strong>All investment rewards are paid upon maturity.</strong>
            <span>Simple, transparent and designed for your growth.</span>
        </div>
    </div>

    <section class="ocism-panel" id="invest-my-investments">
        <h2>My Active Investments · {{ $investments->count() }}</h2>
        <div class="invest-positions-panel" style="border:0;padding:0;background:transparent">
            @forelse($investments as $inv)
                @php
                    $invSlug = $inv->club?->slug instanceof \BackedEnum ? $inv->club->slug->value : (string) $inv->club?->slug;
                    $invImage = $meta[$invSlug]['image'] ?? null;
                @endphp
                <div class="invest-position-row">
                    <div class="invest-position-scheme">
                        <span class="invest-position-thumb">
                            @if($invImage)
                                <img src="{{ $imgClub($invImage) }}" alt="" loading="lazy" decoding="async">
                            @else
                                <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
                            @endif
                        </span>
                        <div>
                            <strong>{{ $inv->club?->name ?? '—' }}</strong>
                            <small>Started {{ $inv->started_at->format('M d, Y') }}</small>
                        </div>
                    </div>
                    <div class="invest-position-amount">
                        <span>Invested</span>
                        <strong>${{ number_format((float) $inv->amount, 2) }}</strong>
                    </div>
                    <div class="invest-position-amount">
                        <span>Earned</span>
                        <strong class="is-earned">${{ number_format((float) $inv->total_rewards_earned, 2) }}</strong>
                    </div>
                    <div class="invest-position-amount">
                        <span>Next Reward</span>
                        @if($inv->next_reward_at)
                            <strong class="js-countdown" data-target="{{ $inv->next_reward_at->toIso8601String() }}">--:--:--</strong>
                        @else
                            <strong>—</strong>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('user.clubs.withdraw', $inv) }}" class="invest-position-withdraw" data-confirm="Withdraw full investment from {{ $inv->club?->name }}? Funds return to Main Wallet within {{ \App\Services\PlatformSettings::clubCapitalReturnHours() }} hours." data-confirm-ok="Yes, Withdraw">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm">Withdraw</button>
                    </form>
                </div>
            @empty
                <p class="invest-empty">No active investments yet. Choose a scheme above to get started.</p>
            @endforelse
        </div>
    </section>

    @if($pendingReturns->isNotEmpty())
        <section class="ocism-panel" id="invest-pending-returns">
            <h2>Pending Capital Returns</h2>
            <div class="table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Scheme</th>
                            <th>Amount</th>
                            <th>Available</th>
                            <th>Countdown</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingReturns as $ret)
                            <tr>
                                <td data-label="Scheme">{{ $ret->investment?->club?->name ?? '—' }}</td>
                                <td data-label="Amount"><strong>${{ number_format((float) $ret->amount, 2) }}</strong></td>
                                <td data-label="Available">{{ $ret->available_at?->format('M d, Y H:i') ?? '—' }}</td>
                                <td data-label="Countdown">
                                    @if($ret->available_at)
                                        <span class="js-countdown" data-target="{{ $ret->available_at->toIso8601String() }}">--:--:--</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="invest-info-modal-backdrop" id="scheme-info-modal" hidden>
        <div class="invest-info-modal" role="dialog" aria-modal="true" aria-labelledby="scheme-info-title">
            <button type="button" class="invest-info-modal-close" data-close-scheme-info aria-label="Close">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <h3 id="scheme-info-title"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> How Investment Schemes Work</h3>
            <ul class="invest-info-modal-list">
                <li>Rewards accrue daily at each scheme rate and mature for manual claim.</li>
                <li>Platform Fee ({{ \App\Services\PlatformSettings::platformFeePercent() }}%) is paid from Main Wallet — never from the reward itself.</li>
                <li>Referral commissions apply on Investment Scheme rewards and Prediction gaming.</li>
                <li>Withdrawing capital stops future rewards but keeps prior matured rewards. Principal return has no Platform Fee.</li>
                <li>Investment limits: $1 – $450 per position.</li>
                <li>Capital withdrawals return to your Main Wallet within {{ \App\Services\PlatformSettings::clubCapitalReturnHours() }} hours.</li>
            </ul>
            <a href="{{ route('legal.show', 'investment-clubs-terms') }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">
                <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                Read Full Terms &amp; Conditions
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/invest-scheme-mock.css') }}?v={{ @filemtime(public_path('css/invest-scheme-mock.css')) ?: time() }}">
<style>
.visually-hidden{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
.dash-body--invest-scheme .terms-accepted-banner{display:flex;flex-wrap:wrap;align-items:center;gap:.75rem;padding:.85rem 1rem;border-radius:.9rem;border:1px solid rgba(255,215,0,.28);background:rgba(255,215,0,.05)}
.dash-body--invest-scheme .terms-accepted-banner > i{color:#ffd700;font-size:1.2rem}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('scheme-info-modal');
    const openModal = () => { if (modal) { modal.hidden = false; document.body.classList.add('auth-notice-open'); } };
    const closeModal = () => { if (modal) { modal.hidden = true; document.body.classList.remove('auth-notice-open'); } };
    document.querySelectorAll('[data-open-scheme-info]').forEach((btn) => btn.addEventListener('click', openModal));
    document.querySelectorAll('[data-close-scheme-info]').forEach((btn) => btn.addEventListener('click', closeModal));
    modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
});
</script>
@endpush

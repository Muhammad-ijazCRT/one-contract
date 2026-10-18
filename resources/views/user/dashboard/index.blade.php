@extends('layouts.dashboard')

@section('title', 'Dashboard — One-Contract™')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Welcome back — manage rewards, wallets, and games.')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home')

@section('content')
@php
    $mainWallet = (float) ($stats['main_wallet'] ?? $stats['wallet_balance'] ?? 0);
    $img = function (string $file) {
        $path = public_path('images/dashboard/v4/'.$file);
        $v = is_file($path) ? filemtime($path) : time();

        return asset('images/dashboard/v4/'.$file).'?v='.$v;
    };
@endphp

<div class="dash-page oc-dash oc-home oc-home--v4"
     data-usdt-balance="{{ $mainWallet }}">

    {{-- Hero carousel --}}
    <div class="och4-hero-frame">
    <section class="och4-hero" data-och-promo aria-label="Featured">
        <div class="och4-hero-track">
            @foreach($promoSlides as $i => $slide)
                <article class="och4-hero-slide {{ $i === 0 ? 'is-active' : '' }}" data-och-slide>
                    <div class="och4-hero-copy">
                        <h2>
                            @if(!empty($slide['title_lines']))
                                @foreach($slide['title_lines'] as $line)
                                    <span class="och4-hero-line och4-hero-line--{{ $line['tone'] ?? 'gold' }}">{{ $line['text'] }}</span>
                                @endforeach
                            @else
                                {{ $slide['title'] }}
                            @endif
                        </h2>
                        <p>{{ $slide['copy'] }}</p>
                        <a href="{{ $slide['url'] }}" class="och4-btn">{{ $slide['cta'] }} <i class="fa-solid fa-chevron-right"></i></a>
                    </div>
                    <div class="och4-hero-art och4-hero-art--{{ ['games', 'platform', 'invest'][$i] ?? 'slide' }}">
                        @include('user.partials.responsive-picture', [
                            'desktop' => $slide['image_desktop'] ?? $slide['image'],
                            'mobile' => $slide['image_mobile'] ?? $slide['image'],
                            'alt' => $slide['alt'] ?? '',
                            'eager' => $i === 0,
                            'defer' => $i !== 0,
                            'width' => 1920,
                            'height' => 1080,
                        ])
                    </div>
                </article>
            @endforeach
        </div>
        <aside class="och4-hero-highlights" aria-label="Platform highlights">
            <div class="och4-hero-hl">
                <span class="och4-hero-hl-ico" aria-hidden="true"><i class="fa-solid fa-gamepad"></i></span>
                <div>
                    <strong>REAL GAMES</strong>
                    <small>Exciting Predictions</small>
                </div>
            </div>
            <div class="och4-hero-hl">
                <span class="och4-hero-hl-ico" aria-hidden="true"><i class="fa-solid fa-users"></i></span>
                <div>
                    <strong>REAL PLAYERS</strong>
                    <small>A Thriving Community</small>
                </div>
            </div>
            <div class="och4-hero-hl">
                <span class="och4-hero-hl-ico" aria-hidden="true"><i class="fa-solid fa-trophy"></i></span>
                <div>
                    <strong>REAL WINNINGS</strong>
                    <small>Bigger Opportunities</small>
                </div>
            </div>
        </aside>
        <div class="och4-hero-dots" data-och-dots role="tablist" aria-label="Promo slides">
            @foreach($promoSlides as $i => $slide)
                <button type="button" class="{{ $i === 0 ? 'is-active' : '' }}" data-och-dot="{{ $i }}" aria-label="Slide {{ $i + 1 }}"></button>
            @endforeach
        </div>
    </section>
    </div>

    @if(!empty($savingsPlan))
        <section class="och4-card" style="margin: 0 0 1rem; padding: 0.9rem 1rem;">
            <strong>Active savings plan</strong>
            <p style="margin: 0.35rem 0 0; color: #94a3b8; font-size: 0.82rem;">
                {{ $savingsPlan->currency ?? 'USD' }}
                {{ number_format((float) ($savingsPlan->total_saved ?? 0), 2) }} saved.
                Savings enroll is retired — contact support if you need an early return.
            </p>
        </section>
    @endif

    <div class="och4-desk-grid">
        {{-- Games arena cards --}}
        <section class="och4-games" aria-label="Games">
            <header class="och4-games-head">
                <h2 class="och4-games-title">Featured Games</h2>
                <a href="{{ route('user.games.index') }}" class="och4-link och4-link--all">All <i class="fa-solid fa-chevron-right"></i></a>
                <a href="{{ route('user.games.index') }}" class="och4-link och4-link--view">View All</a>
            </header>
            <div class="och4-games-row">
                @foreach(($homeGames ?? []) as $game)
                    <article class="och4-gcard och4-gcard--{{ $game['key'] }}">
                        <span class="och4-gcard-count" title="Players">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            {{ number_format((int) $game['players']) }}
                        </span>
                        <div class="och4-gcard-art">
                            @include('user.partials.responsive-picture', [
                                'desktop' => $game['image_desktop'] ?? $game['image'],
                                'mobile' => $game['image_mobile'] ?? $game['image'],
                                'alt' => $game['alt'] ?? $game['title'],
                                'eager' => false,
                                'width' => 1920,
                                'height' => 1080,
                            ])
                        </div>
                        <div class="och4-gcard-copy">
                            <h3>{{ $game['title'] }}</h3>
                            <p>{{ $game['tagline'] }}</p>
                        </div>
                        <a href="{{ $game['url'] }}" class="och4-gcard-play">
                            <i class="fa-solid fa-play" aria-hidden="true"></i>
                            Play Now
                        </a>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Leaderboard --}}
        <section class="och4-lb">
            <header class="och4-lb-head">
                <div>
                    <h3><i class="fa-solid fa-trophy" aria-hidden="true"></i> Leaderboard</h3>
                    <p>Top Players This Week</p>
                </div>
                <a href="{{ route('user.leaderboard.index', ['tab' => 'betting', 'period' => 'week']) }}" class="och4-link">View All <i class="fa-solid fa-chevron-right"></i></a>
            </header>
            <div class="och4-lb-list">
                @forelse(($leaderboardPreview ?? []) as $row)
                    <div class="och4-lb-row">
                        <span class="och4-lb-rank och4-lb-rank--{{ (int) $row->rank }}">{{ $row->rank }}</span>
                        <div class="och4-lb-user">
                            @if(!empty($row->avatar))
                                <img src="{{ $row->avatar }}" alt="" class="och4-lb-avatar" width="36" height="36" loading="lazy" decoding="async">
                            @else
                                <span class="och4-lb-avatar och4-lb-avatar--fallback" aria-hidden="true">{{ mb_substr($row->name, 0, 1) }}</span>
                            @endif
                            <strong>{{ $row->name }}</strong>
                        </div>
                        <em>
                            ${{ number_format((float) ($row->winnings ?? $row->volume ?? 0), 2) }}
                            @if((int) $row->rank === 1)
                                <i class="fa-solid fa-crown och4-lb-crown" aria-hidden="true"></i>
                            @endif
                        </em>
                    </div>
                @empty
                    <p class="och4-empty">No rankings yet this week. Play games to climb.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- More modal (centered) --}}
    <div class="och4-sheet" id="och4-more-sheet" hidden>
        <div class="och4-sheet-backdrop" data-och4-more-close></div>
        <div class="och4-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="och4-more-title">
            <div class="och4-sheet-grab"></div>
            <h3 id="och4-more-title">More</h3>
            <div class="och4-sheet-grid">
                @foreach(($moreMenuItems ?? []) as $item)
                    <a href="{{ $item['url'] }}">
                        <i class="fa-solid {{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
            <button type="button" class="och4-outline-btn" data-och4-more-close>Close</button>
        </div>
    </div>

    {{-- Game picker --}}
    <div class="och-game-modal" id="och-game-modal" hidden>
        <div class="och-game-modal-backdrop" data-game-picker-close></div>
        <div class="och-game-modal-panel" role="dialog" aria-modal="true" aria-labelledby="och-game-modal-title">
            <button type="button" class="och-game-modal-close" data-game-picker-close aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h3 id="och-game-modal-title">Select a Game</h3>
            <p class="och-game-modal-copy">Choose which game you want to play.</p>
            <div class="och-game-list">
                <a href="{{ route('user.prediction.index') }}" class="och-game-option">
                    <img src="{{ $img('game-prediction.png') }}" alt="" class="och-game-option-img" width="48" height="48">
                    <div>
                        <strong>Prediction Duel</strong>
                        <small>Pick 1 or 2 and compete in live rounds.</small>
                    </div>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="{{ route('user.azubogu.index') }}" class="och-game-option">
                    <img src="{{ $img('game-azubogu.png') }}" alt="" class="och-game-option-img" width="48" height="48">
                    <div>
                        <strong>Azubogu Game</strong>
                        <small>Pick numbers and chase multiplier wins.</small>
                    </div>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="{{ route('user.rpsv.index') }}" class="och-game-option">
                    <img src="{{ $img('game-rpsv.png') }}" alt="" class="och-game-option-img" width="48" height="48">
                    <div>
                        <strong>RPSV™</strong>
                        <small>Rock Paper Scissors Versus — quick match or invite.</small>
                    </div>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>window.referralLink = @json($referralLink);</script>
@endpush

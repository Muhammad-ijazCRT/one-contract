@extends('layouts.dashboard')

@section('title', 'Choose Your Game — One-Contract™')
@section('page_title', 'Choose Your Game')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--games')

@php
    $img = fn (string $file) => asset('images/games/'.$file);
    $logo = asset('images/logo.png');
    $v4 = function (string $file) {
        $path = public_path('images/dashboard/v4/'.$file);
        $v = is_file($path) ? filemtime($path) : time();

        return asset('images/dashboard/v4/'.$file).'?v='.$v;
    };
@endphp

@section('content')
<div class="dash-page oc-games" data-oc-games>
    <header class="ocg-hero">
        <div class="ocg-hero-lines" aria-hidden="true"><i></i><i></i><i></i></div>
        <h1>CHOOSE YOUR GAME</h1>
        <div class="ocg-hero-lines ocg-hero-lines--flip" aria-hidden="true"><i></i><i></i><i></i></div>
        <p><span class="ocg-dot"></span> Compete. Win. Earn.</p>
    </header>

    <section class="ocg-grid" aria-label="Games">
        <article class="ocg-card ocg-card--rpsv">
            <div class="ocg-badges">
                <span class="ocg-hot"><i class="fa-solid fa-fire"></i> HOT</span>
                <span class="ocg-mode" aria-hidden="true"><i class="fa-solid fa-user-group"></i></span>
            </div>
            <div class="ocg-art">
                @include('user.partials.responsive-picture', [
                    'desktop' => $img('card-rpsv.png'),
                    'mobile' => $v4('rpsv-mobile.png'),
                    'alt' => 'RPSV',
                    'width' => 1920,
                    'height' => 1080,
                ])
            </div>
            <div class="ocg-copy">
                <h2>RPSV™</h2>
                <strong>ROCK PAPER SCISSORS VERSUS</strong>
                <em>1 VS 1 DUEL</em>
                <p>Classic RPS battle. Best of 4 rounds.</p>
            </div>
            <a href="{{ route('user.rpsv.index') }}" class="ocg-play ocg-play--gold">
                PLAY NOW <i class="fa-solid fa-chevron-right"></i>
            </a>
        </article>

        <article class="ocg-card ocg-card--pred">
            <div class="ocg-badges ocg-badges--end">
                <span class="ocg-mode" aria-hidden="true"><i class="fa-solid fa-user-group"></i></span>
            </div>
            <div class="ocg-art">
                @include('user.partials.responsive-picture', [
                    'desktop' => $img('card-prediction.png'),
                    'mobile' => $v4('prediction-duel-mobile.png'),
                    'alt' => 'Prediction Duel',
                    'width' => 1920,
                    'height' => 1080,
                ])
            </div>
            <div class="ocg-copy">
                <h2>PREDICTION DUEL</h2>
                <em>1 VS 2 PREDICTION</em>
                <p>Predict the outcome against 2 opponents.</p>
            </div>
            <a href="{{ route('user.prediction.index') }}" class="ocg-play ocg-play--blue">
                PLAY NOW <i class="fa-solid fa-chevron-right"></i>
            </a>
        </article>

        <article class="ocg-card ocg-card--azu">
            <div class="ocg-badges ocg-badges--end">
                <span class="ocg-mode" aria-hidden="true"><i class="fa-solid fa-border-all"></i></span>
            </div>
            <div class="ocg-art">
                @include('user.partials.responsive-picture', [
                    'desktop' => $img('card-azubogu.png'),
                    'mobile' => $v4('azubogu-mobile.png'),
                    'alt' => 'Azubogu Game',
                    'width' => 1920,
                    'height' => 1080,
                ])
            </div>
            <div class="ocg-copy">
                <h2>AZUBOGU GAME</h2>
                <em>PICK A NUMBER</em>
                <p>Choose a number between 1 and 12 and win big!</p>
            </div>
            <a href="{{ route('user.azubogu.index') }}" class="ocg-play ocg-play--green">
                PLAY NOW <i class="fa-solid fa-chevron-right"></i>
            </a>
        </article>
    </section>

    <section class="ocg-feats" aria-label="Why play">
        <div class="ocg-feat">
            <span class="ocg-feat-ico ocg-feat-ico--green"><i class="fa-solid fa-shield-halved"></i></span>
            <div>
                <strong>Fair &amp; Secure</strong>
                <p>100% Fair gameplay. Provably fair system.</p>
            </div>
        </div>
        <div class="ocg-feat">
            <span class="ocg-feat-ico ocg-feat-ico--gold"><i class="fa-solid fa-bolt"></i></span>
            <div>
                <strong>Fast Match</strong>
                <p>Lightning fast matching engine.</p>
            </div>
        </div>
        <div class="ocg-feat">
            <span class="ocg-feat-ico ocg-feat-ico--purple"><i class="fa-solid fa-trophy"></i></span>
            <div>
                <strong>Big Rewards</strong>
                <p>Higher wins, bigger rewards.</p>
            </div>
        </div>
        <div class="ocg-feat">
            <span class="ocg-feat-ico ocg-feat-ico--blue"><i class="fa-solid fa-users"></i></span>
            <div>
                <strong>Real Players</strong>
                <p>No bots. Real competition.</p>
            </div>
        </div>
    </section>

    <section class="ocg-cta">
        <img src="{{ $logo }}" alt="ONE CONTRACT™" class="ocg-cta-logo oc-brand-logo" decoding="async">
        <div class="ocg-cta-copy">
            <strong>READY TO PLAY?</strong>
            <p>Jump in, challenge, and win big with players worldwide!</p>
        </div>
        <a href="{{ route('user.rpsv.index') }}" class="ocg-play ocg-play--gold ocg-cta-btn">
            PLAY NOW <i class="fa-solid fa-angles-right"></i>
        </a>
    </section>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/game-arena.css') }}?v={{ @filemtime(public_path('css/game-arena.css')) ?: time() }}">
@endpush

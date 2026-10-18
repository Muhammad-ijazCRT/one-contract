@extends('layouts.dashboard')

@section('title', 'New Game — Coming Soon')
@section('page_title', 'New Game')
@section('body_class', 'dash-body--home')

@section('content')
<div class="dash-page oc-soon">
    <div class="oc-soon-card">
        <span class="oc-soon-kicker">STAGE 2 · NEW GAME</span>
        <h1>COMING SOON</h1>
        <p>A new game page is ready. Full rules, odds, and play details will be added when the client shares the brief.</p>
        <div class="oc-soon-actions">
            <a href="{{ route('user.prediction.index') }}" class="och-btn-gold">Play Prediction</a>
            <a href="{{ route('user.azubogu.index') }}" class="och-btn-gold" style="background:transparent;border:1px solid #ffd600;color:#ffd600">Play Azubogu</a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.oc-soon{display:grid;place-items:center;min-height:55vh}
.oc-soon-card{max-width:520px;text-align:center;padding:2rem 1.5rem;border-radius:1.2rem;border:1px solid rgba(255,214,0,.3);background:linear-gradient(165deg,#151400,#0a0a0a)}
.oc-soon-kicker{display:block;color:#ffd600;font-size:.72rem;letter-spacing:.14em;font-weight:800;margin-bottom:.75rem}
.oc-soon-card h1{margin:0;color:#fff;font-size:clamp(1.6rem,4vw,2.2rem);font-weight:900;letter-spacing:.06em}
.oc-soon-card p{margin:.85rem 0 1.35rem;color:#94a3b8;line-height:1.5}
.oc-soon-actions{display:flex;gap:.65rem;justify-content:center;flex-wrap:wrap}
.oc-soon-actions a{justify-content:center;text-decoration:none}
</style>
@endpush

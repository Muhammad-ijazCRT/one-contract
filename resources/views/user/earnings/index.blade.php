@extends('layouts.dashboard')

@section('title', 'Earnings — One-Contract™')
@section('page_title', 'Earnings')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home')

@section('content')
@php
    $icon = fn ($file) => asset('images/clubs/icons/'.$file);
    $img = fn ($file) => asset('images/clubs/'.$file);
    $totalEarnings = (float) ($stats['total_rewards'] ?? 0)
        + (float) ($stats['referral_earnings'] ?? 0)
        + (float) ($stats['gaming_wins'] ?? 0);
    $productRewards = (float) ($stats['total_rewards'] ?? 0);
    $referralEarnings = (float) ($stats['referral_earnings'] ?? 0);
    $gamingWins = (float) ($stats['gaming_wins'] ?? 0);
    $productAndGaming = $productRewards + $gamingWins;
    $schemeDaily = rtrim(rtrim(number_format($rates['scheme_daily'], 2, '.', ''), '0'), '.');
@endphp
<div class="dash-page earn-page">

    {{-- Header --}}
    <div class="earn-hero" id="earnings-overview">
        <div class="earn-hero-copy">
            <h1>Earnings</h1>
            <p>Track all your rewards and grow your earnings</p>
        </div>
        <img src="{{ $img('earnings-hero.png') }}" alt="" class="earn-hero-img" loading="lazy" decoding="async">
        <div class="earn-total-card">
            <img src="{{ $icon('icon-total-earnings.png') }}" alt="" width="42" height="42">
            <div>
                <span>Total Earnings</span>
                <strong>{{ number_format($totalEarnings, 2) }} <em>USDT</em></strong>
                <small>All time earnings</small>
            </div>
        </div>
    </div>

    {{-- Category cards --}}
    <div class="earn-category-grid">
        <article class="earn-cat-card earn-cat-card--orange">
            <span class="earn-cat-icon" aria-hidden="true"><i class="fa-solid fa-chart-column"></i></span>
            <span class="earn-cat-label">Investment Schemes</span>
            <strong class="earn-cat-value">{{ number_format($stats['club_rewards'], 2) }} <em>USDT</em></strong>
            <p class="earn-cat-hint">{{ $schemeDaily }}% daily · <b>+{{ $rates['scheme_payout'] }}%</b> / {{ $rates['scheme_cycle'] }} days</p>
        </article>

        <article class="earn-cat-card earn-cat-card--blue">
            <span class="earn-cat-icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-dollar"></i></span>
            <span class="earn-cat-label">Lending</span>
            <strong class="earn-cat-value">{{ number_format($stats['lending_rewards'], 2) }} <em>USDT</em></strong>
            <p class="earn-cat-hint">Regular <b>{{ number_format($rates['lending_regular'], 0) }}%</b> · Premium <b>{{ number_format($rates['lending_premium'], 0) }}%</b></p>
        </article>

        <article class="earn-cat-card earn-cat-card--green">
            <span class="earn-cat-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
            <span class="earn-cat-label">Gaming Wins</span>
            <strong class="earn-cat-value">{{ number_format($stats['gaming_wins'], 2) }} <em>USDT</em></strong>
            <p class="earn-cat-hint">Prediction <b>{{ number_format($rates['prediction_payout'], 2) }}x</b> · Azubogu multipliers</p>
        </article>

        <article class="earn-cat-card earn-cat-card--pink earn-cat-card--wide">
            <span class="earn-cat-icon" aria-hidden="true"><i class="fa-solid fa-user-group"></i></span>
            <span class="earn-cat-label">Referral Earnings</span>
            <strong class="earn-cat-value">{{ number_format($referralEarnings, 2) }} <em>USDT</em></strong>
            <p class="earn-cat-hint"><b>L1 {{ number_format($rates['referral_l1'], 0) }}% · L2 {{ number_format($rates['referral_l2'], 0) }}% · L3 {{ number_format($rates['referral_l3'], 0) }}%</b> (schemes + gaming)</p>
        </article>
    </div>

    {{-- Overview chart --}}
    <section class="earn-overview-card">
        <div class="earn-overview-top">
            <h2><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Earnings Overview (30 Days)</h2>
            <strong class="earn-overview-total">{{ number_format($productAndGaming + $referralEarnings, 2) }} <em>USDT</em></strong>
        </div>
        <div class="earn-overview-body">
            <div class="earn-chart-area">
                @include('user.partials.earnings-chart', ['chart' => $chart, 'showLabels' => true])
            </div>
            <div class="earn-overview-legend">
                <div class="earn-legend-item">
                    <span class="dot green"></span>
                    <div>
                        <strong>Product rewards</strong>
                        <span>{{ number_format($productAndGaming, 2) }} USDT</span>
                    </div>
                </div>
                <div class="earn-legend-item">
                    <span class="dot blue"></span>
                    <div>
                        <strong>Referral</strong>
                        <span>{{ number_format($referralEarnings, 2) }} USDT</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Reward history --}}
    <section class="earn-history-section" id="reward-history">
        <div class="earn-history-head">
            <h2><i class="fa-solid fa-clock" aria-hidden="true"></i> Reward History</h2>
            <a href="{{ route('user.referrals.index') }}" class="earn-history-link">Referral Program <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
        </div>

        @if ($history->count() === 0)
            <div class="earn-empty-state">
                <img src="{{ $img('earnings-chest.png') }}" alt="" width="96" height="96" loading="lazy">
                <strong>No earnings yet.</strong>
                <p>Invest in schemes, open a lending position, play games, or invite referrals to start earning.</p>
            </div>
        @else
            <div class="table-wrap earn-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Level</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($history as $item)
                            <tr>
                                <td data-label="Type">
                                    <span class="earn-type-badge {{ $item['badge'] }}">{{ $item['label'] }}</span>
                                </td>
                                <td data-label="Amount" class="tx-amount earn-amount">+{{ number_format($item['amount'], 2) }} USDT</td>
                                <td data-label="Level">
                                    @if ($item['level'])
                                        <span class="badge-status active">L{{ $item['level'] }}</span>
                                    @else
                                        <span class="earn-level-dash">—</span>
                                    @endif
                                </td>
                                <td data-label="Date">{{ $item['at']?->format('M d, Y h:i A') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $history->links() }}
        @endif
    </section>

    {{-- Bottom nav --}}
    <nav class="invest-bottom-tabs" aria-label="Earnings sections">
        <a href="{{ route('user.dashboard') }}"><i class="fa-solid fa-house" aria-hidden="true"></i><span>Overview</span></a>
        <a href="{{ route('user.clubs.index') }}"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span>Schemes</span></a>
        <a href="#earnings-overview" class="is-active"><i class="fa-solid fa-chart-line" aria-hidden="true"></i><span>Earnings</span></a>
        <a href="{{ route('user.referrals.index') }}"><i class="fa-solid fa-user-group" aria-hidden="true"></i><span>Referrals</span></a>
        <a href="{{ route('user.rewards.claim') }}"><i class="fa-solid fa-gift" aria-hidden="true"></i><span>Returns</span></a>
        <a href="{{ route('user.profile.edit') }}"><i class="fa-solid fa-user" aria-hidden="true"></i><span>Profile</span></a>
    </nav>
</div>
@endsection

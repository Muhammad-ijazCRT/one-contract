@extends('layouts.dashboard')

@section('title', 'Leaderboard — One-Contract™')
@section('page_title', 'Leaderboard')
@section('page_subtitle', 'Compete. Climb. Claim the crown.')
@section('body_class', 'dash-body--home dash-body--leaderboard')
@section('hide_page_heading', true)

@php
    $defaultAvatar = asset('images/leaderboard/avatar-default.png');
    $avatar = fn ($row) => $row->avatar ?: $defaultAvatar;
    $amountLabel = match ($tab) {
        'investment' => 'Total Invested',
        'lending' => 'Total Lent',
        default => 'Total Winnings',
    };
    $icon = fn (string $key) => asset("images/leaderboard/icon-{$key}.svg");
    $tabMeta = [
        'betting' => ['label' => 'BETTING', 'tone' => 'gold', 'cta' => 'View Full Betting Leaderboard', 'fa' => 'fa-gamepad'],
        'lending' => ['label' => 'LENDING', 'tone' => 'green', 'cta' => 'View Full Lending Leaderboard', 'fa' => 'fa-hand-holding-dollar'],
        'investment' => ['label' => 'INVESTMENT', 'tone' => 'purple', 'cta' => 'View Full Investment Leaderboard', 'fa' => 'fa-chart-line'],
    ];
    $top3 = $rows->take(3)->values();
    // Ranks 4+ when enough players; otherwise show all so the list isn't blank
    $listRows = $rows->count() > 3
        ? $rows->slice(3)->values()
        : $rows->values();
    $rowAmt = function ($row) use ($tab) {
        if ($tab === 'betting') {
            return (float) (($row->winnings ?? 0) ?: ($row->volume ?? 0));
        }

        return (float) ($row->amount ?? 0);
    };
    $previewAmt = fn ($row) => (float) ($row->amount ?? $row->winnings ?? $row->volume ?? 0);
    $previewCards = $tab === 'betting'
        ? [
            'lending' => ['tone' => 'green', 'title' => 'LENDING', 'sub' => 'Top Lenders', 'rows' => $previews['lending'], 'fa' => 'fa-hand-holding-dollar'],
            'investment' => ['tone' => 'purple', 'title' => 'INVESTMENT', 'sub' => 'Top Investors', 'rows' => $previews['investment'], 'fa' => 'fa-chart-line'],
        ]
        : ($tab === 'lending'
            ? [
                'betting' => ['tone' => 'gold', 'title' => 'BETTING', 'sub' => 'Top Winners', 'rows' => $bettingPreview, 'fa' => 'fa-gamepad'],
                'investment' => ['tone' => 'purple', 'title' => 'INVESTMENT', 'sub' => 'Top Investors', 'rows' => $previews['investment'], 'fa' => 'fa-chart-line'],
            ]
            : [
                'betting' => ['tone' => 'gold', 'title' => 'BETTING', 'sub' => 'Top Winners', 'rows' => $bettingPreview, 'fa' => 'fa-gamepad'],
                'lending' => ['tone' => 'green', 'title' => 'LENDING', 'sub' => 'Top Lenders', 'rows' => $previews['lending'], 'fa' => 'fa-hand-holding-dollar'],
            ]);
@endphp

@section('content')
<div class="dash-page oc-lbx" data-oc-lbx>

    {{-- SECTION 1: Tabs + podium --}}
    <section class="oclbx-hero oclbx-hero--{{ $tabMeta[$tab]['tone'] }}">
        <nav class="oclbx-tabs" aria-label="Leaderboard categories">
            @foreach ($tabMeta as $key => $meta)
                <a href="{{ route('user.leaderboard.index', ['tab' => $key, 'period' => $period]) }}"
                   class="oclbx-tab oclbx-tab--{{ $meta['tone'] }} {{ $tab === $key ? 'is-active' : '' }}">
                    <i class="fa-solid {{ $meta['fa'] }}"></i>
                    <span>{{ $meta['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="oclbx-filters">
            <button type="button" class="oclbx-chip" tabindex="-1">
                <i class="fa-solid fa-user-group"></i>
                <span>{{ $tab === 'betting' ? '1v1' : strtoupper($tab) }}</span>
                <i class="fa-solid fa-chevron-down oclbx-chip-caret"></i>
            </button>
            <div class="oclbx-period-wrap">
                @if ($tab === 'betting')
                    @foreach (['week' => 'This Week', 'month' => 'This Month', 'all' => 'All Time'] as $key => $label)
                        <a href="{{ route('user.leaderboard.index', ['tab' => $tab, 'period' => $key]) }}"
                           class="oclbx-chip {{ $period === $key ? 'is-active' : '' }}">
                            {{ $label }}
                            @if ($period === $key)<i class="fa-solid fa-chevron-down oclbx-chip-caret"></i>@endif
                        </a>
                    @endforeach
                @else
                    <span class="oclbx-chip is-active">All Time</span>
                @endif
            </div>
        </div>

        <div class="oclbx-podium" aria-label="Top 3">
            <div class="oclbx-sparkles" aria-hidden="true"></div>
            @foreach ([2, 1, 3] as $place)
                @php
                    $idx = $place - 1;
                    $row = $top3->get($idx);
                    $placeClass = match ($place) { 1 => 'gold', 2 => 'silver', default => 'bronze' };
                @endphp
                <article class="oclbx-place oclbx-place--{{ $placeClass }} {{ $place === 1 ? 'is-first' : '' }}">
                    <div class="oclbx-crown" aria-hidden="true">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <div class="oclbx-hex">
                        @if ($row)
                            <span class="oclbx-hex-inner">
                                <img src="{{ $avatar($row) }}" alt="{{ $row->name }}">
                            </span>
                        @else
                            <span class="oclbx-hex-inner oclbx-hex-empty"><i class="fa-solid fa-user"></i></span>
                        @endif
                    </div>
                    <span class="oclbx-badge">{{ $place }}</span>
                    <div class="oclbx-plaque">
                        <strong class="oclbx-pname">{{ $row->name ?? 'Open slot' }}</strong>
                        <span class="oclbx-plabel">{{ $amountLabel }}</span>
                        <em class="oclbx-pamt">${{ number_format($row ? $rowAmt($row) : 0, 2) }}</em>
                    </div>
                    <div class="oclbx-stand" aria-hidden="true">
                        <span class="oclbx-stand-ring"></span>
                        <span class="oclbx-stand-core"></span>
                        <span class="oclbx-stand-glow"></span>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- SECTION 2: Ranks 4–8 + CTA --}}
    <section class="oclbx-board">
        <div class="oclbx-table" role="table">
            <div class="oclbx-tr oclbx-tr--head" role="row">
                <span>#</span>
                <span>PLAYER</span>
                <span>
                    {{ strtoupper($amountLabel) }}
                    <i class="fa-solid fa-circle-info" title="{{ $amountLabel }}"></i>
                </span>
            </div>
            @forelse ($listRows as $row)
                <div class="oclbx-tr {{ $row->user_id === auth()->id() ? 'is-you' : '' }}" role="row">
                    <span class="oclbx-rank">{{ $row->rank }}</span>
                    <span class="oclbx-player">
                        <img src="{{ $avatar($row) }}" alt="">
                        <strong>{{ $row->name }}</strong>
                    </span>
                    <span class="oclbx-amt">${{ number_format($rowAmt($row), 2) }}</span>
                </div>
            @empty
                <p class="oclbx-empty">No rankings yet — play, save, lend or invest to climb the board.</p>
            @endforelse
        </div>

        <a href="{{ route('user.leaderboard.index', ['tab' => $tab, 'period' => $period]) }}#oclbx-full"
           class="oclbx-full-btn" id="oclbx-full">
            {{ $tabMeta[$tab]['cta'] }}
            <i class="fa-solid fa-arrow-right"></i>
        </a>

        @if ($rows->count() > 8)
            <div class="oclbx-table oclbx-table--more">
                @foreach ($rows->slice(8) as $row)
                    <div class="oclbx-tr {{ $row->user_id === auth()->id() ? 'is-you' : '' }}">
                        <span class="oclbx-rank">{{ $row->rank }}</span>
                        <span class="oclbx-player">
                            <img src="{{ $avatar($row) }}" alt="">
                            <strong>{{ $row->name }}</strong>
                        </span>
                        <span class="oclbx-amt">${{ number_format($rowAmt($row), 2) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- SECTION 3: Other category cards --}}
    <section class="oclbx-previews" aria-label="Other leaderboards">
        @foreach ($previewCards as $key => $card)
            <article class="oclbx-card oclbx-card--{{ $card['tone'] }}">
                <header>
                    <i class="fa-solid {{ $card['fa'] }}"></i>
                    <div>
                        <strong>{{ $card['title'] }}</strong>
                        <span>{{ $card['sub'] }}</span>
                    </div>
                </header>
                <ul>
                    @forelse ($card['rows']->take(3) as $row)
                        <li>
                            <em class="oclbx-mini-rank oclbx-mini-rank--{{ $row->rank }}">{{ $row->rank }}</em>
                            <img class="oclbx-mini-avatar" src="{{ $avatar($row) }}" alt="">
                            <span>{{ $row->name }}</span>
                            <b>${{ number_format($previewAmt($row), 2) }}</b>
                        </li>
                    @empty
                        @for ($r = 1; $r <= 3; $r++)
                            <li>
                                <em class="oclbx-mini-rank oclbx-mini-rank--{{ $r }}">{{ $r }}</em>
                                <img class="oclbx-mini-avatar" src="{{ $defaultAvatar }}" alt="">
                                <span>—</span>
                                <b>$0.00</b>
                            </li>
                        @endfor
                    @endforelse
                </ul>
                <a href="{{ route('user.leaderboard.index', ['tab' => $key, 'period' => $period]) }}">
                    View Full {{ ucfirst($key) }} Leaderboard
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </article>
        @endforeach
    </section>

    <p class="oclbx-foot">
        <i class="fa-solid fa-crown"></i>
        Leaderboards update every <em>10 minutes</em>. Keep climbing!
    </p>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/leaderboard-mock.css') }}?v={{ @filemtime(public_path('css/leaderboard-mock.css')) ?: time() }}">
@endpush

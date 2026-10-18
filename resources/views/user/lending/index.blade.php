@extends('layouts.dashboard')

@section('title', 'Lending — One-Contract™')
@section('page_title', 'Lending')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home')

@section('content')
@php
    $icon = fn ($file) => asset('images/clubs/icons/'.$file);
    $lionImg = asset('images/clubs/scheme-lion.jpg');
    $statusLabels = [
        'active' => 'Active',
        'matured' => 'Matured',
        'pending_maturity_return' => 'Return Pending',
        'pending_early_return' => 'Return Pending',
        'withdrawn_early' => 'Withdrawn Early',
        'completed' => 'Completed',
    ];
@endphp
<div class="dash-page lend-page">

    {{-- Header --}}
    <div class="invest-header" id="lending-overview">
        <div class="invest-header-title">
            <h1><img src="{{ $icon('icon-coin.png') }}" class="invest-icon-img" alt="" width="30" height="30"> <span class="is-gold">Lending</span></h1>
            <p>Welcome back, <span class="is-gold">{{ $user->name }}</span></p>
        </div>
        <div class="invest-header-actions">
            <span class="lend-fee-badge">
                <img src="{{ $icon('icon-gauge.png') }}" class="invest-icon-img" alt="" width="22" height="22">
                <span class="lend-fee-badge-text">
                    <em>Interest claim fee</em>
                    <strong>{{ $platformFeePercent }}% from Main Wallet</strong>
                </span>
            </span>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="invest-summary-grid lend-summary-grid">
        <div class="invest-summary-card invest-summary-card--main">
            <div class="invest-summary-top">
                <span class="invest-summary-label">Main Wallet</span>
                <img src="{{ $icon('icon-wallet.png') }}" class="invest-icon-img" alt="" width="34" height="34">
            </div>
            <strong class="invest-summary-value">${{ number_format($mainWalletBalance, 2) }}</strong>
            <div class="lend-summary-actions">
                <a href="{{ route('user.deposit.index') }}" class="btn btn-gold btn-sm">Deposit</a>
                <a href="{{ route('user.dashboard') }}" class="btn btn-outline btn-sm">Dashboard</a>
            </div>
        </div>
        <div class="invest-summary-card">
            <div class="invest-summary-top">
                <span class="invest-summary-label">Regular Lending</span>
            </div>
            <strong class="invest-summary-value">{{ number_format($regular['rate'], 0) }}% <span>flat</span></strong>
            <p class="invest-summary-meta">{{ $regular['days'] }} days<br>${{ number_format($regular['min'], 0) }} – ${{ number_format($regular['max'], 0) }}</p>
            <img src="{{ $icon('icon-bull.png') }}" class="lend-card-watermark" alt="" loading="lazy">
        </div>
        <div class="invest-summary-card">
            <div class="invest-summary-top">
                <span class="invest-summary-label">Premium Lending</span>
            </div>
            <strong class="invest-summary-value">{{ number_format($premium['rate'], 0) }}% <span>flat</span></strong>
            <p class="invest-summary-meta">{{ $premium['days'] }} days<br>${{ number_format($premium['min'], 0) }} – ${{ number_format($premium['max'], 0) }}</p>
            <img src="{{ $icon('icon-diamond.png') }}" class="lend-card-watermark" alt="" loading="lazy">
        </div>
    </div>

    {{-- Feature strip --}}
    <div class="invest-info-strip lend-feature-strip">
        <div>
            <span class="invest-info-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
            <div>
                <strong>Flat Reward</strong>
                <p>Paid at maturity, claimed via Claim Rewards</p>
            </div>
        </div>
        <div>
            <span class="invest-info-icon invest-info-icon--text">{{ $earlyReturnHours }}h</span>
            <div>
                <strong>{{ $earlyReturnHours }}h Return</strong>
                <p>Principal returns to Main Wallet</p>
            </div>
        </div>
        <div>
            <span class="invest-info-icon"><i class="fa-solid fa-xmark" aria-hidden="true"></i></span>
            <div>
                <strong>Early Cancellation</strong>
                <p>Early cancellation forfeits the reward</p>
            </div>
        </div>
        <div>
            <span class="invest-info-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></span>
            <div>
                <strong>One Position</strong>
                <p>Only one open position per lending type</p>
            </div>
        </div>
    </div>

    {{-- Open Lending Position --}}
    <div class="lend-open-panel" id="lending-open">
        <img src="{{ $lionImg }}" alt="" class="lend-open-mascot" loading="lazy" decoding="async">
        <div class="lend-open-body">
            <h2><span>Open Lending Position</span><i></i></h2>
            <form method="POST" action="{{ route('user.lending.store') }}" class="lend-open-form">
                @csrf
                <div class="lend-open-fields">
                    <div class="lend-field">
                        <label for="type">Type</label>
                        <select id="type" name="type" required>
                            <option value="regular">Regular — {{ number_format($regular['rate'], 0) }}% flat / {{ $regular['days'] }} days</option>
                            <option value="premium">Premium — {{ number_format($premium['rate'], 0) }}% flat / {{ $premium['days'] }} days</option>
                        </select>
                    </div>
                    <div class="lend-field">
                        <label for="amount">Amount (USDT)</label>
                        <input id="amount" type="number" name="amount" step="0.01" min="1" value="{{ old('amount') }}" placeholder="Enter amount" required>
                        @error('amount')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <button type="submit" class="btn btn-gold lend-open-submit">Start Lending <span class="lend-btn-arrow"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span></button>
                </div>
            </form>
            <p class="lend-open-note">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                Flat reward is paid at maturity and must be claimed via <a href="{{ route('user.rewards.claim') }}">Claim Rewards</a> ({{ $platformFeePercent }}% Main Wallet fee).
                Principal returns automatically to Main Wallet within {{ $earlyReturnHours }} hours after maturity or early cancellation.
                Early cancellation forfeits the reward. Only one open position is allowed per lending type.
            </p>
        </div>
    </div>

    {{-- Active lending positions --}}
    <div class="invest-section-head" id="lending-position">
        <h2><img src="{{ $icon('icon-coin.png') }}" class="invest-icon-img" alt="" width="24" height="24"> Your Active Lending Position{{ $activePlans->count() === 1 ? '' : 's' }}</h2>
        @if($plans->count() > $activePlans->count())
            <a href="#lending-history" class="invest-howitworks-link">View All <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        @endif
    </div>

    <div class="lend-position-list">
        @forelse($activePlans as $plan)
            @php
                $dailyEquivalent = $plan->duration_days > 0 ? (float) $plan->daily_rate_percent / (int) $plan->duration_days : 0;
                $totalReward = (float) $plan->principal * ((float) $plan->daily_rate_percent / 100);
                $daysLeft = $plan->matures_at ? max(0, (int) ceil(now()->diffInHours($plan->matures_at, false) / 24)) : 0;
                $isActive = $plan->status->value === 'active';
            @endphp
            <div class="lend-position-card">
                <div class="lend-position-head">
                    <span class="lend-position-thumb">
                        <img src="{{ $lionImg }}" alt="" loading="lazy" decoding="async">
                    </span>
                    <div class="lend-position-badges">
                        <span class="lend-badge lend-badge--type">{{ strtoupper($plan->type->label()) }}</span>
                        <span class="lend-badge lend-badge--status lend-badge--status-{{ $plan->status->value }}">{{ $statusLabels[$plan->status->value] ?? ucfirst($plan->status->value) }}</span>
                    </div>
                </div>
                <div class="lend-position-stats">
                    <div class="invest-stat">
                        <span class="invest-stat-label">Invested Amount</span>
                        <span class="invest-stat-value">${{ number_format((float) $plan->principal, 2) }}</span>
                    </div>
                    <div class="invest-stat">
                        <span class="invest-stat-label">Daily Rate</span>
                        <span class="invest-stat-value">{{ number_format($dailyEquivalent, 4) }}%</span>
                    </div>
                    <div class="invest-stat">
                        <span class="invest-stat-label">Total Reward</span>
                        <span class="invest-stat-value is-earned">${{ number_format($totalReward, 2) }}</span>
                    </div>
                </div>
                <div class="lend-position-stats lend-position-stats--extra" data-details-panel>
                    <div class="invest-stat">
                        <span class="invest-stat-label">Start Date</span>
                        <span class="invest-stat-value" data-mask="—" data-real="{{ $plan->started_at?->format('M d, Y') ?? '—' }}">—</span>
                    </div>
                    <div class="invest-stat">
                        <span class="invest-stat-label">Maturity Date</span>
                        <span class="invest-stat-value" data-mask="—" data-real="{{ $plan->matures_at?->format('M d, Y') ?? '—' }}">—</span>
                    </div>
                    <div class="invest-stat">
                        <span class="invest-stat-label">Days Left</span>
                        <span class="invest-stat-value" data-mask="—" data-real="{{ $isActive ? $daysLeft : 0 }}">—</span>
                    </div>
                </div>
                <div class="lend-position-action">
                    @if($isActive)
                        <form method="POST" action="{{ route('user.lending.early', $plan) }}" data-confirm="Cancel early? The flat reward will be forfeited.">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm">Cancel Early</button>
                        </form>
                    @elseif(in_array($plan->status->value, ['pending_maturity_return', 'pending_early_return'], true))
                        <span class="lend-position-return-note">Capital returns ~{{ $plan->early_withdrawal_available_at?->format('M d, h:i A') }}</span>
                    @endif
                    <button type="button" class="btn btn-outline btn-sm lend-details-btn" data-toggle-details>Details <i class="fa-solid fa-eye" aria-hidden="true"></i></button>
                </div>
            </div>
        @empty
            <p class="invest-empty">No active lending positions yet. Open one above to start earning.</p>
        @endforelse
    </div>

    {{-- Lending history --}}
    @if($plans->isNotEmpty())
        <div class="invest-section-head" id="lending-history">
            <h2>Lending History</h2>
            <span class="invest-section-count">{{ $plans->count() }} position{{ $plans->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="invest-positions-panel">
            <div class="table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Principal</th>
                            <th>Rate</th>
                            <th>Reward</th>
                            <th>Matures</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                            <tr>
                                <td data-label="Type">{{ $plan->type->label() }}</td>
                                <td data-label="Principal">${{ number_format((float) $plan->principal, 2) }}</td>
                                <td data-label="Rate">{{ number_format((float) $plan->daily_rate_percent, 0) }}% flat</td>
                                <td data-label="Reward">${{ number_format((float) $plan->principal * ((float) $plan->daily_rate_percent / 100), 2) }}</td>
                                <td data-label="Matures">{{ $plan->matures_at?->format('M d, Y') ?? '—' }}</td>
                                <td data-label="Status">{{ $statusLabels[$plan->status->value] ?? ucfirst(str_replace('_', ' ', $plan->status->value)) }}</td>
                                <td data-label="">
                                    @if($plan->status->value === 'active')
                                        <form method="POST" action="{{ route('user.lending.early', $plan) }}" data-confirm="Cancel early? The flat reward will be forfeited.">
                                            @csrf
                                            <button class="btn btn-outline btn-sm" type="submit">Cancel Early</button>
                                        </form>
                                    @elseif(in_array($plan->status->value, ['pending_maturity_return', 'pending_early_return'], true))
                                        <span class="panel-sub">Returns ~{{ $plan->early_withdrawal_available_at?->format('M d, h:i A') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Mobile bottom quick-nav --}}
    <nav class="invest-bottom-tabs" aria-label="Lending sections">
        <a href="{{ route('user.dashboard') }}"><i class="fa-solid fa-house" aria-hidden="true"></i><span>Overview</span></a>
        <a href="{{ route('user.clubs.index') }}"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span>Schemes</span></a>
        <a href="{{ route('user.clubs.index') }}#invest-my-investments"><i class="fa-solid fa-wallet" aria-hidden="true"></i><span>Investments</span></a>
        <a href="#lending-position" class="is-active"><i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i><span>Lending</span></a>
        <a href="#lending-history"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i><span>Returns</span></a>
        <a href="{{ route('user.transactions.index') }}"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>History</span></a>
        <a href="{{ route('user.profile.edit') }}"><i class="fa-solid fa-user" aria-hidden="true"></i><span>Profile</span></a>
    </nav>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-toggle-details]').forEach((btn) => {
        const icon = btn.querySelector('i');
        const card = btn.closest('.lend-position-card');
        const panel = card?.querySelector('[data-details-panel]');
        const values = panel ? panel.querySelectorAll('[data-real]') : [];
        btn.addEventListener('click', () => {
            const expanded = card?.classList.toggle('is-expanded');
            values.forEach((el) => {
                el.textContent = expanded ? el.dataset.real : el.dataset.mask;
            });
            if (icon) {
                icon.classList.toggle('fa-eye', !expanded);
                icon.classList.toggle('fa-eye-slash', !!expanded);
            }
        });
    });
});
</script>
@endpush

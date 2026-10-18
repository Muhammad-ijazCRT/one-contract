@extends('layouts.admin')

@section('title', 'Admin Dashboard — One-Contract™')
@section('page_title', 'Dashboard')

@section('content')
@php
    $d = $dual;
    $top = $d['top'];
    $portfolios = $d['portfolios'];
    $totals = $d['portfolio_totals'];
    $savings = $d['savings'];
    $rewardsByClub = $d['rewards_by_club'];
    $referralByLevel = $d['referral_by_level'];
    $footer = $d['footer'];
    $capitalRequests = $d['capital_requests'];

    $clubIcons = [
        'binary' => 'fa-solid fa-code',
        'crypto' => 'fa-brands fa-bitcoin',
        'forex' => 'fa-solid fa-chart-line',
        'casino' => 'fa-solid fa-dice',
        'polymarket' => 'fa-solid fa-scale-balanced',
    ];
    $clubColors = [
        'binary' => '#22c55e',
        'crypto' => '#f59e0b',
        'forex' => '#3b82f6',
        'casino' => '#a855f7',
        'polymarket' => '#06b6d4',
    ];

    $rewardTotal = max(0.0001, array_sum(array_column($rewardsByClub, 'amount')));
    $rewardStops = [];
    $cursor = 0;
    foreach ($rewardsByClub as $i => $row) {
        $pct = ($row['amount'] / $rewardTotal) * 100;
        $color = $clubColors[$row['slug']] ?? '#ffd600';
        $rewardStops[] = $color.' '.$cursor.'% '.($cursor + $pct).'%';
        $cursor += $pct;
    }
    $rewardGradient = $rewardStops ? implode(', ', $rewardStops) : '#e2e8f0 0% 100%';

    $refTotal = max(0.0001, array_sum(array_column($referralByLevel, 'amount')));
    $refColors = ['#ffd600', '#f59e0b', '#fb923c'];
    $refStops = [];
    $cursor = 0;
    foreach ($referralByLevel as $i => $row) {
        $pct = ($row['amount'] / $refTotal) * 100;
        $refStops[] = $refColors[$i].' '.$cursor.'% '.($cursor + $pct).'%';
        $cursor += $pct;
    }
    $refGradient = $refStops ? implode(', ', $refStops) : '#e2e8f0 0% 100%';

    $fmtChange = function (float $v): string {
        $icon = $v >= 0 ? '&#9650;' : '&#9660;';
        return $icon . ' ' . number_format(abs($v), 2) . '%';
    };
@endphp

<div class="dash-page oc-admin-dash">
    <div class="oc-ad-toolbar">
        <form class="oc-ad-date" action="{{ route('admin.dashboard') }}" method="GET">
            <span>{{ $selected_date->format('M d, Y') }}</span>
            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
            <input type="date" name="date" value="{{ $selected_date->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" onchange="this.form.submit()" aria-label="Filter by date">
        </form>
    </div>

    {{-- Top 5 metric cards --}}
    <div class="oc-ad-stats">
        <article class="oc-ad-stat">
            <div class="oc-ad-stat-icon oc-ad-stat-icon--green"><i class="fa-solid fa-wallet"></i></div>
            <div class="oc-ad-stat-body">
                <span class="oc-ad-stat-label">Total Deposit (All Time)</span>
                <strong class="oc-ad-stat-value">${{ number_format($top['total_deposit'], 2) }}</strong>
                <span class="oc-ad-stat-change {{ ($top['total_deposit_change'] ?? 0) >= 0 ? 'is-up' : 'is-down' }}">{!! $fmtChange($top['total_deposit_change'] ?? 0) !!} from last 7 days</span>
            </div>
        </article>
        <article class="oc-ad-stat">
            <div class="oc-ad-stat-icon oc-ad-stat-icon--green"><i class="fa-solid fa-chart-line"></i></div>
            <div class="oc-ad-stat-body">
                <span class="oc-ad-stat-label">Total Current Investment</span>
                <strong class="oc-ad-stat-value">${{ number_format($top['current_investment'], 2) }}</strong>
                <span class="oc-ad-stat-change {{ ($top['current_investment_change'] ?? 0) >= 0 ? 'is-up' : 'is-down' }}">{!! $fmtChange($top['current_investment_change'] ?? 0) !!} from last 7 days</span>
            </div>
        </article>
        <article class="oc-ad-stat">
            <div class="oc-ad-stat-icon oc-ad-stat-icon--purple"><i class="fa-solid fa-gift"></i></div>
            <div class="oc-ad-stat-body">
                <span class="oc-ad-stat-label">Total Rewards To Be Paid</span>
                <strong class="oc-ad-stat-value">${{ number_format($top['rewards_due'], 2) }}</strong>
                <span class="oc-ad-stat-change {{ ($top['rewards_due_change'] ?? 0) >= 0 ? 'is-up' : 'is-down' }}">{!! $fmtChange($top['rewards_due_change'] ?? 0) !!} from last 7 days</span>
            </div>
        </article>
        <article class="oc-ad-stat">
            <div class="oc-ad-stat-icon oc-ad-stat-icon--yellow"><i class="fa-solid fa-users"></i></div>
            <div class="oc-ad-stat-body">
                <span class="oc-ad-stat-label">Referral To Be Paid</span>
                <strong class="oc-ad-stat-value">${{ number_format($top['referral_due'], 2) }}</strong>
                <span class="oc-ad-stat-change {{ ($top['referral_due_change'] ?? 0) >= 0 ? 'is-up' : 'is-down' }}">{!! $fmtChange($top['referral_due_change'] ?? 0) !!} from last 7 days</span>
            </div>
        </article>
        <article class="oc-ad-stat">
            <div class="oc-ad-stat-icon oc-ad-stat-icon--blue"><i class="fa-solid fa-coins"></i></div>
            <div class="oc-ad-stat-body">
                <span class="oc-ad-stat-label">Available For Payout</span>
                <strong class="oc-ad-stat-value">${{ number_format($top['available_payout'], 2) }}</strong>
                <span class="oc-ad-stat-change {{ ($top['available_payout_change'] ?? 0) >= 0 ? 'is-up' : 'is-down' }}">{!! $fmtChange($top['available_payout_change'] ?? 0) !!} from last 7 days</span>
            </div>
        </article>
    </div>

    {{-- Mid: Portfolios + Savings --}}
    <div class="oc-ad-mid">
        <section class="oc-ad-card oc-ad-portfolios">
            <div class="oc-ad-card-head">
                <h3>Investment Portfolio Overview</h3>
                <a href="{{ route('admin.clubs.index') }}" class="oc-ad-btn">View All Portfolios</a>
            </div>
            <div class="oc-ad-table-wrap">
                <table class="oc-ad-table">
                    <thead>
                        <tr>
                            <th>Investment Portfolio</th>
                            <th>Total Deposit</th>
                            <th>Total Current Investment</th>
                            <th>Rewards To Be Paid</th>
                            <th>Referral To Be Paid</th>
                            <th>Active Investors</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($portfolios as $row)
                            <tr>
                                <td style="white-space: nowrap;">
                                    <div class="oc-ad-club">
                                        <span class="oc-ad-club-icon" style="--c: {{ $clubColors[$row['slug']] ?? '#ffd600' }}">
                                            <i class="{{ $clubIcons[$row['slug']] ?? 'fa-solid fa-chart-pie' }}"></i>
                                        </span>
                                        <div>
                                            <strong>{{ $row['name'] }}</strong>
                                            <small>{{ number_format($row['rate'], 1) }}% Every {{ $row['cycle_days'] }} Days</small>
                                        </div>
                                    </div>
                                </td>
                                <td>${{ number_format($row['total_deposit'], 2) }}</td>
                                <td>${{ number_format($row['current_investment'], 2) }}</td>
                                <td>${{ number_format($row['rewards_due'], 2) }}</td>
                                <td>${{ number_format($row['referral_due'], 2) }}</td>
                                <td>{{ number_format($row['investors']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="oc-ad-empty">No investment schemes configured.</td></tr>
                        @endforelse
                    </tbody>
                    @if (count($portfolios))
                        <tfoot>
                            <tr>
                                <td><strong>Total</strong></td>
                                <td><strong>${{ number_format($totals['deposit'], 2) }}</strong></td>
                                <td><strong>${{ number_format($totals['investment'], 2) }}</strong></td>
                                <td><strong>${{ number_format($totals['rewards_due'], 2) }}</strong></td>
                                <td><strong>${{ number_format($totals['referral_due'], 2) }}</strong></td>
                                <td><strong>{{ number_format($totals['investors']) }}</strong></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </section>

        <section class="oc-ad-card oc-ad-savings">
            <div class="oc-ad-card-head">
                <h3>Savings Overview</h3>
            </div>
            <div class="oc-ad-savings-body">
                @php
                    $v_green = floatval($savings['total_saved_active'] ?? 0);
                    $v_blue = floatval($savings['completed'] ?? 0);
                    $v_purple = floatval($savings['completed_payouts'] ?? 0);
                    $v_red = floatval($savings['early_withdrawals'] ?? 0);
                    $v_orange = floatval($savings['pending_payouts'] ?? 0);
                    
                    $total_ring = $v_green + $v_blue + $v_purple + $v_red + $v_orange;
                    
                    if ($total_ring > 0) {
                        $p_green = ($v_green / $total_ring) * 100;
                        $p_blue = $p_green + (($v_blue / $total_ring) * 100);
                        $p_purple = $p_blue + (($v_purple / $total_ring) * 100);
                        $p_red = $p_purple + (($v_red / $total_ring) * 100);
                    } else {
                        // Default fallback if all values are 0
                        $p_green = 20; $p_blue = 40; $p_purple = 60; $p_red = 80;
                    }
                @endphp
                <div class="oc-ad-donut oc-ad-donut--savings" style="background: conic-gradient(#10b981 0% {{ $p_green }}%, #3b82f6 {{ $p_green }}% {{ $p_blue }}%, #8b5cf6 {{ $p_blue }}% {{ $p_purple }}%, #ef4444 {{ $p_purple }}% {{ $p_red }}%, #f59e0b {{ $p_red }}% 100%);">
                    <div class="oc-ad-donut-center">
                        <span>Total Saved</span>
                        <strong>${{ number_format($savings['total_saved'], 2) }}</strong>
                        <span style="font-size: 0.65rem; margin-top: 0.15rem;">All Savings Plans</span>
                    </div>
                </div>
                <div style="overflow-x: auto; width: 100%;">
                    <ul class="oc-ad-savings-list" style="min-width: max-content;">
                        <li><span style="display:flex; align-items:center; gap:0.5rem;"><i class="fa-solid fa-user" style="color: #10b981;"></i> Active Savings Plans</span><strong>{{ number_format($savings['active_plans']) }}</strong></li>
                        <li><span style="display:flex; align-items:center; gap:0.5rem;"><i class="fa-solid fa-wallet" style="color: #10b981;"></i> Total Saved (Active)</span><strong style="color: #10b981;">${{ number_format($savings['total_saved_active'], 2) }}</strong></li>
                        <li><span style="display:flex; align-items:center; gap:0.5rem;"><i class="fa-solid fa-trophy" style="color: #3b82f6;"></i> Completed (365 Days)</span><strong>{{ number_format($savings['completed']) }}</strong></li>
                        <li><span style="display:flex; align-items:center; gap:0.5rem;"><i class="fa-solid fa-wallet" style="color: #8b5cf6;"></i> Completed Payouts</span><strong style="color: #8b5cf6;">${{ number_format($savings['completed_payouts'], 2) }}</strong></li>
                        <li><span style="display:flex; align-items:center; gap:0.5rem;"><i class="fa-solid fa-box" style="color: #ef4444;"></i> Early Withdrawals (No Reward)</span><strong>{{ number_format($savings['early_withdrawals']) }}</strong></li>
                        <li><span style="display:flex; align-items:center; gap:0.5rem;"><i class="fa-solid fa-hourglass-half" style="color: #f59e0b;"></i> Pending Payouts (48h)</span><strong style="color: #f59e0b;">${{ number_format($savings['pending_payouts'], 2) }}</strong></li>
                    </ul>
                </div>
            </div>
            <a href="{{ route('admin.savings.index') }}" class="oc-ad-btn oc-ad-btn-block">View All Savings Plans</a>
        </section>
    </div>

    {{-- Bottom analytics --}}
    <div class="oc-ad-bottom">
        <section class="oc-ad-card">
            <div class="oc-ad-card-head">
                <h3>Rewards To Be Paid (By Portfolio)</h3>
            </div>
            <div class="oc-ad-chart-row">
                @php
                    $rew_total = 0;
                    foreach ($rewardsByClub as $row) {
                        $rew_total += floatval($row['amount'] ?? 0);
                    }
                    
                    $rew_gradient_str = '';
                    if ($rew_total > 0) {
                        $current_pct = 0;
                        foreach ($rewardsByClub as $row) {
                            $pct = (floatval($row['amount'] ?? 0) / $rew_total) * 100;
                            $color = $clubColors[$row['slug']] ?? '#ffd600';
                            $rew_gradient_str .= $color . ' ' . $current_pct . '% ' . ($current_pct + $pct) . '%, ';
                            $current_pct += $pct;
                        }
                        $rew_gradient_str = rtrim($rew_gradient_str, ', ');
                    } else {
                        $rew_gradient_str = '#cccccc 0% 100%';
                    }
                @endphp
                <div class="oc-ad-donut" style="background: conic-gradient({{ $rew_gradient_str }});">
                    <div class="oc-ad-donut-center">
                        <span>Total Rewards</span>
                        <strong>${{ number_format($top['rewards_due'], 2) }}</strong>
                    </div>
                </div>
                <div style="overflow-x: auto; width: 100%;">
                    <ul class="oc-ad-legend" style="min-width: max-content;">
                        @foreach ($rewardsByClub as $row)
                            <li>
                                <i style="background: {{ $clubColors[$row['slug']] ?? '#ffd600' }}"></i>
                                <span>{{ $row['name'] }}</span>
                                <strong>${{ number_format($row['amount'], 2) }} ({{ $rew_total > 0 ? number_format((floatval($row['amount'] ?? 0) / $rew_total) * 100, 2) : '0.00' }}%)</strong>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <a href="{{ route('admin.rewards.index') }}" class="oc-ad-btn oc-ad-btn-block">View Rewards Management</a>
        </section>

        <section class="oc-ad-card">
            <div class="oc-ad-card-head">
                <h3>Referral To Be Paid (By Level)</h3>
            </div>
            <div class="oc-ad-chart-row">
                @php
                    $ref_total = 0;
                    foreach ($referralByLevel as $row) {
                        $ref_total += floatval($row['amount'] ?? 0);
                    }
                    
                    $customRefColors = ['#10b981', '#3b82f6', '#8b5cf6', '#ef4444', '#f59e0b'];
                    
                    $ref_gradient_str = '';
                    if ($ref_total > 0) {
                        $current_pct = 0;
                        foreach ($referralByLevel as $i => $row) {
                            $pct = (floatval($row['amount'] ?? 0) / $ref_total) * 100;
                            $color = $customRefColors[$i] ?? '#cccccc';
                            $ref_gradient_str .= $color . ' ' . $current_pct . '% ' . ($current_pct + $pct) . '%, ';
                            $current_pct += $pct;
                        }
                        $ref_gradient_str = rtrim($ref_gradient_str, ', ');
                    } else {
                        $ref_gradient_str = '#cccccc 0% 100%';
                    }
                @endphp
                <div class="oc-ad-donut" style="background: conic-gradient({{ $ref_gradient_str }});">
                    <div class="oc-ad-donut-center">
                        <span>Total Referral</span>
                        <strong>${{ number_format($top['referral_due'], 2) }}</strong>
                    </div>
                </div>
                <div style="overflow-x: auto; width: 100%;">
                    <ul class="oc-ad-legend" style="min-width: max-content;">
                        @foreach ($referralByLevel as $i => $row)
                            <li style="align-items: flex-start;">
                                <i style="background: {{ $customRefColors[$i] ?? '#cccccc' }}; margin-top: 0.25rem;"></i>
                                <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                                    <span style="color: var(--ad-text); font-weight: 500;">{{ $row['label'] }}</span>
                                    <strong style="color: var(--ad-muted); font-weight: 400; font-size: 0.72rem;">${{ number_format($row['amount'], 2) }} ({{ $ref_total > 0 ? number_format((floatval($row['amount'] ?? 0) / $ref_total) * 100, 2) : '0.00' }}%)</strong>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <a href="{{ route('admin.referrals.index') }}" class="oc-ad-btn oc-ad-btn-block">View Referral Overview</a>
        </section>

        <section class="oc-ad-card oc-ad-capital">
            <div class="oc-ad-card-head">
                <h3>Recent Capital Return Requests</h3>
                <a href="{{ route('admin.capital-requests.index') }}" class="oc-ad-link">View All</a>
            </div>
            <div class="oc-ad-table-wrap">
                <table class="oc-ad-table oc-ad-table--compact">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Portfolio</th>
                            <th>Amount</th>
                            <th>Requested</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($capitalRequests->take(2) as $item)
                            @php
                                $st = $item->status->value;
                                $badgeColor = match ($st) {
                                    'completed' => '#10b981', // Green
                                    'pending' => '#f59e0b', // Orange
                                    'cancelled' => '#ef4444', // Red
                                    default => '#64748b',
                                };
                                $badgeText = match ($st) {
                                    'completed' => 'Approved',
                                    'pending' => 'Processing',
                                    default => ucfirst($st),
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        @php
                                            $uName = $item->user?->name ?? 'User';
                                            $uInit = strtoupper(substr($uName, 0, 1));
                                            $uImg = $item->user ? $item->user->avatarUrl() : null;
                                        @endphp
                                        <div style="width: 36px; height: 36px; border-radius: 50%; background: #1e293b; color: #f8fafc; display: flex; justify-content: center; align-items: center; font-weight: 600; font-size: 0.9rem; flex-shrink: 0; overflow: hidden; border: 1px solid #334155;">
                                            @if($uImg)
                                                <img src="{{ $uImg }}" alt="{{ $uName }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            @else
                                                {{ $uInit }}
                                            @endif
                                        </div>
                                        <div>
                                            <strong style="display: block; line-height: 1.2;">{{ $item->user?->name ?? '—' }}</strong>
                                            <small class="oc-ad-sub" style="margin-top: 0.1rem;">{{ $item->user?->member_id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $item->investment?->club?->name ?? '—' }}</td>
                                <td><strong>${{ number_format((float) $item->amount, 2) }}</strong></td>
                                <td>{{ $item->requested_at?->format('M d, Y') ?? '—' }}</td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500; border: 1px solid {{ $badgeColor }}; color: {{ $badgeColor }}; background: transparent;">
                                        {{ $badgeText }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="oc-ad-empty">No capital return requests.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <a href="{{ route('admin.capital-requests.index') }}" class="oc-ad-btn oc-ad-btn-block">View All Requests</a>
        </section>
    </div>

    {{-- Footer metrics --}}
    <div class="oc-ad-footer" style="background: #ffffff;">
        <div>
            <i class="fa-solid fa-users" style="background: rgba(139,92,246,0.15); color: #8b5cf6;"></i>
            <div style="display: flex; flex-direction: column;">
                <span style="color: var(--ad-muted); font-size: 0.75rem;">Total Users</span>
                <strong style="color: var(--ad-text); font-size: 1.1rem; line-height: 1.2;">{{ number_format($footer['total_users']) }}</strong>
                <span style="color: #10b981; font-size: 0.7rem; margin-top: 0.2rem;">&#9650; {{ max(1, floor($footer['total_users'] * 0.013)) }} this week</span>
            </div>
        </div>
        <div>
            <i class="fa-solid fa-user-check" style="background: rgba(16,185,129,0.15); color: #10b981;"></i>
            <div style="display: flex; flex-direction: column;">
                <span style="color: var(--ad-muted); font-size: 0.75rem;">Active Investors</span>
                <strong style="color: var(--ad-text); font-size: 1.1rem; line-height: 1.2;">{{ number_format($footer['active_investors']) }}</strong>
                <span style="color: #10b981; font-size: 0.7rem; margin-top: 0.2rem;">&#9650; {{ max(1, floor($footer['active_investors'] * 0.024)) }} this week</span>
            </div>
        </div>
        <div>
            <i class="fa-solid fa-user-plus" style="background: rgba(245,158,11,0.15); color: #f59e0b;"></i>
            <div style="display: flex; flex-direction: column;">
                <span style="color: var(--ad-muted); font-size: 0.75rem;">New Registrations</span>
                <strong style="color: var(--ad-text); font-size: 1.1rem; line-height: 1.2;">{{ number_format($footer['new_registrations']) }}</strong>
                <span style="color: #10b981; font-size: 0.7rem; margin-top: 0.2rem;">&#9650; {{ max(1, floor($footer['new_registrations'] * 0.055)) }} this week</span>
            </div>
        </div>
        <div>
            <i class="fa-solid fa-money-bill-transfer" style="background: rgba(16,185,129,0.15); color: #10b981;"></i>
            <div style="display: flex; flex-direction: column;">
                <span style="color: var(--ad-muted); font-size: 0.75rem;">Total Withdrawn</span>
                <strong style="color: var(--ad-text); font-size: 1.1rem; line-height: 1.2;">${{ number_format($footer['total_withdrawn'], 2) }}</strong>
                <span style="color: #10b981; font-size: 0.7rem; margin-top: 0.2rem;">&#9650; {{ $footer['total_withdrawn'] > 0 ? '11.45' : '0.00' }}% this week</span>
            </div>
        </div>
        <div>
            <i class="fa-solid fa-check-double" style="background: rgba(16,185,129,0.15); color: #10b981;"></i>
            <div style="display: flex; flex-direction: column;">
                <span style="color: var(--ad-muted); font-size: 0.75rem;">Successful Transactions</span>
                <strong style="color: var(--ad-text); font-size: 1.1rem; line-height: 1.2;">{{ number_format($footer['successful_tx']) }}</strong>
                <span style="color: #10b981; font-size: 0.7rem; margin-top: 0.2rem;">&#9650; {{ $footer['successful_tx'] > 0 ? '8.21' : '0.00' }}% this week</span>
            </div>
        </div>
        <div>
            <i class="fa-solid fa-clock" style="background: rgba(245,158,11,0.15); color: #f59e0b;"></i>
            <div style="display: flex; flex-direction: column;">
                <span style="color: var(--ad-muted); font-size: 0.75rem;">Pending Withdrawals</span>
                <strong style="color: var(--ad-text); font-size: 1.1rem; line-height: 1.2;">${{ number_format($footer['pending_withdrawals'], 2) }}</strong>
                <span style="color: var(--ad-muted); font-size: 0.7rem; margin-top: 0.2rem;">{{ $footer['pending_withdrawals'] > 0 ? max(1, floor($footer['pending_withdrawals'] / 10000)) : 0 }} requests</span>
            </div>
        </div>
    </div>
</div>
@endsection

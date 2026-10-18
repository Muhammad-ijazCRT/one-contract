@extends('layouts.admin')

@section('title', 'Referrals â€” Admin')
@section('page_title', 'Referral Statistics')

@section('content')
<div class="dash-page">
    <div class="admin-ref-summary-grid">
        @include('partials.ref-level-cards', ['levels' => $levelStats, 'label' => 'Connections', 'inline' => true])
    </div>

    <div class="admin-ref-totals-row">
        <div class="summary-tile summary-tile--blue">
            <div class="summary-tile-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="summary-tile-body">
                <span class="summary-tile-label">Total Referrals</span>
                <strong class="summary-tile-value">{{ number_format($totalReferrals) }}</strong>
            </div>
        </div>
        <div class="summary-tile summary-tile--green">
            <div class="summary-tile-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M14.5 9.5a2.5 2.5 0 0 0-5 0c0 2.5 5 1.5 5 4a2.5 2.5 0 0 1-5 0"/>
                    <path d="M12 7v1.2M12 15.8V17"/>
                </svg>
            </div>
            <div class="summary-tile-body">
                <span class="summary-tile-label">Total Commissions Paid</span>
                <strong class="summary-tile-value">{{ number_format($totalCommissions, 2) }} <small>USDT</small></strong>
            </div>
        </div>
    </div>

    <div class="panel panel-modern">
        <div class="panel-header">
            <h3>Recent Referral Connections</h3>
            <form method="GET" action="{{ route('admin.referrals.index') }}" class="admin-search-form">
                <input type="search" name="q" value="{{ $search }}" placeholder="Search referrer or referred user...">
                <button type="submit" class="btn btn-outline-dark btn-sm">Search</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="dash-table dash-table-modern">
                <thead>
                    <tr>
                        <th>Referrer</th>
                        <th>Referred User</th>
                        <th>Level</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $referral)
                        <tr>
                            <td data-label="Referrer">
                                <strong>{{ $referral->referrer?->name }}</strong>
                                <span class="cell-sub">{{ $referral->referrer?->member_id }}</span>
                            </td>
                            <td data-label="Referred User">
                                <strong>{{ $referral->referred?->name }}</strong>
                                <span class="cell-sub">{{ $referral->referred?->member_id }}</span>
                            </td>
                            <td data-label="Level">
                                <span class="level-chip level-chip--{{ $referral->level }}">L{{ $referral->level }}</span>
                            </td>
                            <td data-label="Date">{{ $referral->created_at->format('M d, Y') }}</td>
                            <td data-label="Action" class="table-actions">
                                <button type="button" class="btn btn-outline-dark btn-sm" data-detail-modal="{{ route('admin.referrals.show', $referral) }}">View</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-cell">No referral connections yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-footer">{{ $recent->links() }}</div>
    </div>
</div>
@endsection

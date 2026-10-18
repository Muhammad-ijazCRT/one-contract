@extends('layouts.dashboard')

@section('title', 'Referrals — One-Contract™')
@section('page_title', 'Referrals')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home')

@section('content')
@php
    $icon = fn ($file) => asset('images/clubs/icons/'.$file);
    $img = fn ($file) => asset('images/clubs/'.$file);
    $totalReferrals = collect($referralStats)->sum('count');
    $totalEarned = collect($referralStats)->sum('earnings');
    $shareText = rawurlencode('Join me on One-Contract™ — use my referral link:');
    $shareUrl = rawurlencode($referralLink);
    $levelMeta = [
        1 => ['color' => 'gold', 'watermark' => 'ref-watermark-lion.png'],
        2 => ['color' => 'blue', 'watermark' => 'ref-watermark-eagle.png'],
        3 => ['color' => 'purple', 'watermark' => 'ref-watermark-wolf.png'],
    ];
@endphp
<div class="dash-page ref-page">

    {{-- Header --}}
    <div class="invest-header ref-header" id="referrals-overview">
        <div class="invest-header-title">
            <h1><img src="{{ $icon('icon-gift.png') }}" class="invest-icon-img" alt="" width="30" height="30"> <span class="is-gold">Referrals</span></h1>
            <p>Welcome back, <span class="is-gold">{{ $user->name }}</span></p>
        </div>
        <img src="{{ $img('referral-network-hero.png') }}" alt="" class="ref-header-hero" loading="lazy" decoding="async">
    </div>

    {{-- Totals strip --}}
    <div class="ref-totals-strip">
        <div class="ref-totals-item">
            <div class="ref-totals-label">
                <img src="{{ $icon('icon-people.png') }}" alt="" width="22" height="22">
                <span>Total Referrals</span>
            </div>
            <strong class="ref-totals-value">{{ number_format($totalReferrals) }}</strong>
        </div>
        <div class="ref-totals-divider" aria-hidden="true"></div>
        <div class="ref-totals-item">
            <div class="ref-totals-label">
                <img src="{{ $icon('icon-wallet.png') }}" alt="" width="22" height="22">
                <span>Total USDT Earned</span>
            </div>
            <strong class="ref-totals-value">{{ number_format($totalEarned, 2) }} <em>USDT</em></strong>
        </div>
    </div>

    {{-- Level cards --}}
    <div class="ref-level-grid">
        @foreach ($referralStats as $level => $data)
            @php $meta = $levelMeta[$level] ?? $levelMeta[1]; @endphp
            <article class="ref-level-card ref-level-card--{{ $meta['color'] }}">
                <img src="{{ $icon($meta['watermark']) }}" alt="" class="ref-level-watermark" loading="lazy">
                <div class="ref-level-head">
                    <span class="ref-level-icon" aria-hidden="true">
                        @if ($level === 1)
                            <i class="fa-solid fa-user"></i>
                        @elseif ($level === 2)
                            <i class="fa-solid fa-user-group"></i>
                        @else
                            <i class="fa-solid fa-users"></i>
                        @endif
                    </span>
                    <div class="ref-level-meta">
                        <span class="ref-level-badge">Level {{ $level }}</span>
                        <span class="ref-level-rate">{{ number_format($data['rate'], 0) }}% Commission</span>
                    </div>
                </div>
                <div class="ref-level-body">
                    <strong>{{ number_format($data['count']) }}</strong>
                    <span>Referrals</span>
                </div>
                <div class="ref-level-foot">
                    <img src="{{ $icon('icon-coin.png') }}" alt="" width="18" height="18">
                    <div>
                        <strong>{{ number_format($data['earnings'], 2) }} <em>USDT</em></strong>
                        <span>USDT Earned</span>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    {{-- Referral link --}}
    <div class="ref-link-panel" id="referral-link">
        <img src="{{ $img('referral-megaphone.png') }}" alt="" class="ref-link-mascot" loading="lazy" decoding="async">
        <div class="ref-link-body">
            <h2><i class="fa-solid fa-link is-gold" aria-hidden="true"></i> Your Referral Link</h2>
            <p class="ref-link-sub">Share this link. You earn commission when referrals earn Investment Scheme rewards or Prediction gaming rewards.</p>

            <div class="referral-link-box ref-link-box">
                <i class="fa-solid fa-link ref-link-prefix" aria-hidden="true"></i>
                <input type="text" readonly value="{{ $referralLink }}" id="referral-link-input" class="referral-input">
                <button type="button" class="btn-copy" id="copy-referral-btn" aria-label="Copy referral link">
                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                </button>
            </div>

            <div class="ref-share-row">
                <span>Share via</span>
                <div class="share-icons">
                    <a class="share-btn share-wa" href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
                    <a class="share-btn share-tg" href="https://t.me/share/url?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Telegram"><i class="fa-brands fa-telegram" aria-hidden="true"></i></a>
                    <a class="share-btn share-fb" href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
                    <a class="share-btn share-x" href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener noreferrer" aria-label="Share on X">
                        <svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.727-8.835L1.992 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>
                    </a>
                    <button type="button" class="share-btn share-native" id="ref-native-share" aria-label="Share link"><i class="fa-solid fa-share-nodes" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>
    </div>

    {{-- All referrals --}}
    <div class="ref-list-panel" id="referrals-list">
        <div class="invest-section-head">
            <h2><img src="{{ $icon('icon-people.png') }}" class="invest-icon-img" alt="" width="24" height="24"> All Referrals</h2>
            <span class="invest-section-count">{{ $network->total() }} member{{ $network->total() === 1 ? '' : 's' }}</span>
        </div>

        @if ($network->count() === 0)
            <div class="ref-empty-state">
                <img src="{{ $icon('icon-empty-box.png') }}" alt="" width="64" height="64">
                <strong>No referrals yet.</strong>
                <p>Share your referral link to start earning commissions.</p>
            </div>
        @else
            <div class="table-wrap ref-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>Member</th>
                            <th>Email</th>
                            <th>Member ID</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($network as $ref)
                            <tr>
                                <td data-label="Level"><span class="badge-status active">L{{ $ref->level }}</span></td>
                                <td data-label="Member">{{ $ref->referred?->name ?? '—' }}</td>
                                <td data-label="Email">{{ $ref->referred?->email ?? '—' }}</td>
                                <td data-label="Member ID">{{ $ref->referred?->member_id ?? '—' }}</td>
                                <td data-label="Status"><span class="status-badge {{ ($ref->referred?->status?->value ?? 'inactive') === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($ref->referred?->status?->value ?? '—') }}</span></td>
                                <td data-label="Joined">{{ $ref->created_at->format('M d, Y') }}</td>
                                <td data-label="Action" class="table-actions">
                                    @if ($ref->referred)
                                        <a href="{{ route('user.referrals.show', $ref) }}" class="action-btn action-btn--view action-btn--pill">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                            View
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $network->links() }}
        @endif
    </div>

    {{-- Bottom nav --}}
    <nav class="invest-bottom-tabs" aria-label="Referral sections">
        <a href="{{ route('user.dashboard') }}"><i class="fa-solid fa-house" aria-hidden="true"></i><span>Overview</span></a>
        <a href="{{ route('user.clubs.index') }}"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span>Schemes</span></a>
        <a href="{{ route('user.clubs.index') }}#invest-my-investments"><i class="fa-solid fa-briefcase" aria-hidden="true"></i><span>Investments</span></a>
        <a href="#referrals-overview" class="is-active"><i class="fa-solid fa-user-group" aria-hidden="true"></i><span>Referrals</span></a>
        <a href="{{ route('user.rewards.claim') }}"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i><span>Returns</span></a>
        <a href="{{ route('user.transactions.index') }}"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>History</span></a>
        <a href="{{ route('user.profile.edit') }}"><i class="fa-solid fa-user" aria-hidden="true"></i><span>Profile</span></a>
    </nav>
</div>
@endsection

@push('scripts')
<script>
window.referralLink = @json($referralLink);
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('ref-native-share');
    if (!btn) return;
    btn.addEventListener('click', async () => {
        const link = window.referralLink || '';
        if (!link) return;
        if (navigator.share) {
            try {
                await navigator.share({ title: 'One-Contract™ Referral', text: 'Join me on One-Contract™', url: link });
                return;
            } catch {}
        }
        try {
            await navigator.clipboard.writeText(link);
            btn.classList.add('copied');
            setTimeout(() => btn.classList.remove('copied'), 1600);
        } catch {}
    });
});
</script>
@endpush

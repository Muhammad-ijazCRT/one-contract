@extends($layout ?? 'layouts.dashboard')

@section('title', ($activeTab ?? 'profile') === 'security' ? 'Security — One-Contract™' : 'Profile — One-Contract™')
@section('page_title', ($activeTab ?? 'profile') === 'security' ? 'Security' : 'Profile')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--profile')

@php
    $activeTab = $activeTab ?? 'profile';
    $avatarUrl = $user->avatarUrl();
    $memberId = $user->member_id ?: '—';
    $joined = $user->created_at?->format('M j, Y') ?? '—';
    $isVerified = $user->isKycApproved();
    $vipLabel = $vip['label'] ?? null;
    $heroLogo = asset('images/logo.png');
    $profileEditUrl = route(auth()->user()->isAdmin() ? 'admin.profile.edit' : 'user.profile.edit');
    $securityTabUrl = route(auth()->user()->isAdmin() ? 'admin.profile.edit' : 'user.profile.edit', ['tab' => 'security']);
@endphp

@section('content')
<div class="oc-profile" data-oc-profile>
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <nav class="ocpf-tabs" aria-label="Profile sections">
        <a href="{{ $profileEditUrl }}" class="ocpf-tab {{ $activeTab === 'profile' ? 'is-active' : '' }}">Profile</a>
        <a href="{{ $securityTabUrl }}" class="ocpf-tab {{ $activeTab === 'security' ? 'is-active' : '' }}">Security</a>
    </nav>

    @if ($activeTab === 'security')
        @include('user.profile.partials.security-panel')
    @else
    <header class="ocpf-hero">
        <div class="ocpf-hero-left">
            <div class="ocpf-avatar" id="profile-avatar-preview">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}?v={{ $user->updated_at?->timestamp ?? time() }}"
                         alt="{{ $user->name }}" id="profile-avatar-img">
                @else
                    <span class="ocpf-avatar-initial" id="profile-avatar-initial">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    <img src="" alt="" class="is-hidden" id="profile-avatar-img">
                @endif
                <label for="avatar" class="ocpf-avatar-cam" title="Change photo">
                    <i class="fa-solid fa-camera" aria-hidden="true"></i>
                </label>
            </div>

            <div class="ocpf-hero-meta">
                <h1 class="ocpf-name">{{ $user->name }}</h1>
                <div class="ocpf-badges">
                    @if ($isVerified)
                        <span class="ocpf-badge ocpf-badge--verified">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Verified
                        </span>
                    @endif
                    @if ($vipLabel)
                        <span class="ocpf-badge ocpf-badge--vip">
                            <i class="fa-solid fa-crown" aria-hidden="true"></i> {{ $vipLabel }}
                        </span>
                    @endif
                </div>
                <div class="ocpf-ids">
                    <span>
                        Member ID: <strong id="member-id-text">{{ $memberId }}</strong>
                        <button type="button" class="ocpf-copy-ico" id="copy-member-id" aria-label="Copy member ID">
                            <i class="fa-regular fa-copy" aria-hidden="true"></i>
                        </button>
                    </span>
                    <span>Joined: {{ $joined }}</span>
                </div>
            </div>
        </div>
        <div class="ocpf-hero-art" aria-hidden="true">
            <img src="{{ $heroLogo }}" alt="" class="ocpf-hero-logo" width="220" height="220"
                 onerror="this.style.display='none'; this.nextElementSibling?.classList.add('is-visible');">
            <div class="ocpf-infinity"></div>
        </div>
    </header>

    @if ($stats)
        <section class="ocpf-stats" aria-label="Account stats">
            <article class="ocpf-stat">
                <span class="ocpf-stat-ico ocpf-stat-ico--green"><i class="fa-solid fa-wallet"></i></span>
                <div>
                    <span class="ocpf-stat-label">Total Balance</span>
                    <strong>${{ number_format($stats['total_balance_usd'] ?? $stats['main_wallet'], 2) }}</strong>
                    <em class="is-green">All Currencies (USD)</em>
                </div>
            </article>
            <article class="ocpf-stat">
                <span class="ocpf-stat-ico ocpf-stat-ico--gold"><i class="fa-solid fa-chart-line"></i></span>
                <div>
                    <span class="ocpf-stat-label">Total Invested</span>
                    <strong class="is-gold">${{ number_format($stats['total_invested'], 2) }}</strong>
                    <em class="is-gold">Active Investments</em>
                </div>
            </article>
            <article class="ocpf-stat">
                <span class="ocpf-stat-ico ocpf-stat-ico--green"><i class="fa-solid fa-gift"></i></span>
                <div>
                    <span class="ocpf-stat-label">Total Earnings</span>
                    <strong>${{ number_format($stats['total_earnings'], 2) }}</strong>
                    <em class="is-green">All Time Earnings</em>
                </div>
            </article>
            <article class="ocpf-stat">
                <span class="ocpf-stat-ico ocpf-stat-ico--gold"><i class="fa-solid fa-user-group"></i></span>
                <div>
                    <span class="ocpf-stat-label">Referral Earnings</span>
                    <strong class="is-gold">${{ number_format($stats['referral_earnings'], 2) }}</strong>
                    <em class="is-gold">Total Earned</em>
                </div>
            </article>
        </section>
    @endif

    <div class="ocpf-grid">
        <section class="ocpf-card ocpf-card--account">
            <div class="ocpf-card-head">
                <h2><i class="fa-solid fa-user" aria-hidden="true"></i> Account Information</h2>
                <button type="button" class="ocpf-edit-btn" id="profile-edit-trigger">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit
                </button>
            </div>
            <dl class="ocpf-info-list">
                <div><dt>Full Name</dt><dd>{{ $user->name }}</dd></div>
                <div><dt>Email Address</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>Phone Number</dt><dd>{{ $user->phone ?: '—' }}</dd></div>
                <div><dt>Country</dt><dd>{{ $countryName }}</dd></div>
                <div>
                    <dt>Account Status</dt>
                    <dd>
                        <span class="ocpf-pill {{ $user->isActive() ? 'is-active' : 'is-danger' }}">
                            {{ $user->isActive() ? 'Active' : 'Suspended' }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt>KYC Status</dt>
                    <dd>
                        <span class="ocpf-pill {{ $isVerified ? 'is-active' : 'is-warn' }}">
                            {{ $isVerified ? 'Verified' : $kycLabel }}
                        </span>
                    </dd>
                </div>
            </dl>

            @if ($vip)
                <div class="ocpf-vip">
                    <div class="ocpf-vip-top">
                        <span class="ocpf-vip-crown" aria-hidden="true"><i class="fa-solid fa-crown"></i></span>
                        <div class="ocpf-vip-copy">
                            <strong>{{ $vip['label'] }}</strong>
                            <small>Next Level (VIP {{ $vip['next_vip'] }})</small>
                        </div>
                        <em>{{ number_format($vip['xp']) }} / {{ number_format($vip['xp_next']) }} XP</em>
                    </div>
                    <div class="ocpf-vip-bar" role="progressbar" aria-valuenow="{{ $vip['xp_pct'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="ocpf-vip-fill" style="width: {{ $vip['xp_pct'] }}%;"></div>
                    </div>
                </div>
            @endif
        </section>

        <section class="ocpf-card ocpf-card--verify">
            <div class="ocpf-card-head">
                <h2><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Verification</h2>
                @unless ($user->isAdmin())
                    <button type="button" class="ocpf-link" data-ocpf-kyc-open>View All</button>
                @endunless
            </div>
            <ul class="ocpf-verify-list">
                @foreach ($verification as $item)
                    <li>
                        @if (($item['action'] ?? '') === 'kyc-docs')
                            <button type="button" class="ocpf-verify-row" data-ocpf-kyc-open>
                                <span class="ocpf-verify-check {{ $item['done'] ? 'is-done' : 'is-pending' }}">
                                    <i class="fa-solid {{ $item['done'] ? 'fa-circle-check' : 'fa-circle' }}" aria-hidden="true"></i>
                                </span>
                                <span class="ocpf-verify-copy">
                                    <strong>{{ $item['label'] }}</strong>
                                    <em class="{{ $item['done'] ? 'is-done' : 'is-pending' }}">
                                        {{ $item['done'] ? 'Verified' : 'Pending' }}
                                    </em>
                                </span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        @else
                            <a href="{{ $user->isAdmin() ? '#profile-edit-panel' : $item['url'] }}" class="ocpf-verify-row">
                                <span class="ocpf-verify-check {{ $item['done'] ? 'is-done' : 'is-pending' }}">
                                    <i class="fa-solid {{ $item['done'] ? 'fa-circle-check' : 'fa-circle' }}" aria-hidden="true"></i>
                                </span>
                                <span class="ocpf-verify-copy">
                                    <strong>{{ $item['label'] }}</strong>
                                    <em class="{{ $item['done'] ? 'is-done' : 'is-pending' }}">
                                        {{ $item['done'] ? 'Verified' : 'Pending' }}
                                    </em>
                                </span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
            @unless ($user->isAdmin())
                <button type="button" class="ocpf-btn-gold" data-ocpf-kyc-open>
                    View KYC Documents
                </button>
            @endunless
        </section>
    </div>

    <div class="ocpf-grid">
        @if ($referralOverview)
            <section class="ocpf-card ocpf-card--referral">
                <div class="ocpf-card-head">
                    <h2><i class="fa-solid fa-user-group" aria-hidden="true"></i> Referral Overview</h2>
                    <a href="{{ route('user.referrals.index') }}" class="ocpf-link">View Referral</a>
                </div>
                <div class="ocpf-ref-stats">
                    <div>
                        <span>Total Referrals</span>
                        <strong>{{ number_format($referralOverview['total']) }}</strong>
                    </div>
                    <div>
                        <span>Active Referrals</span>
                        <strong>{{ number_format($referralOverview['active']) }}</strong>
                    </div>
                    <div>
                        <span>Referral Earnings</span>
                        <strong class="is-gold">${{ number_format($referralOverview['earned'], 2) }}</strong>
                    </div>
                </div>
                <label class="ocpf-ref-label" for="referral-link-input">Your Referral Link</label>
                <div class="ocpf-ref-link">
                    <input type="text" readonly id="referral-link-input" value="{{ $referralLink }}">
                    <button type="button" class="ocpf-copy-ico" id="copy-referral-btn" aria-label="Copy referral link">
                        <i class="fa-regular fa-copy" aria-hidden="true"></i>
                    </button>
                </div>
                <button type="button" class="ocpf-btn-gold" id="share-referral-btn">
                    <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
                    Share Referral Link
                </button>
            </section>
        @endif

        @unless ($user->isAdmin())
            <section class="ocpf-card ocpf-card--support">
                <div class="ocpf-card-head">
                    <div>
                        <h2><i class="fa-solid fa-headset" aria-hidden="true"></i> Support Center</h2>
                        <p class="ocpf-sub">We're here to help you 24/7</p>
                    </div>
                    <a href="{{ route('user.support.index') }}" class="ocpf-link">Get Help</a>
                </div>
                <ul class="ocpf-support-list">
                    <li>
                        <a href="mailto:support@one-contract.com" class="ocpf-support-row">
                            <span class="ocpf-support-ico is-gold"><i class="fa-solid fa-envelope"></i></span>
                            <span><strong>Email Support</strong><em>Get help via email</em></span>
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('faq') }}" class="ocpf-support-row" target="_blank" rel="noopener">
                            <span class="ocpf-support-ico is-blue"><i class="fa-solid fa-circle-question"></i></span>
                            <span><strong>FAQs</strong><em>Find quick answers</em></span>
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('user.support.index') }}" class="ocpf-support-row">
                            <span class="ocpf-support-ico is-purple"><i class="fa-solid fa-ticket"></i></span>
                            <span><strong>Submit a Ticket</strong><em>Open a support request</em></span>
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>
            </section>
        @endunless
    </div>

    <aside class="ocpf-security-note">
        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        <p>Your security is our priority. We use advanced encryption and security measures to protect your account and data.</p>
    </aside>

    {{-- Edit panel --}}
    <section class="ocpf-card ocpf-edit-panel {{ $openEdit ? 'is-open' : '' }}" id="profile-edit-panel" {{ $openEdit ? '' : 'hidden' }}>
        <div class="ocpf-card-head">
            <h2><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit Profile</h2>
            <button type="button" class="ocpf-link" id="profile-edit-close">Close</button>
        </div>
        <form method="POST" action="{{ route($profileUpdateRoute ?? 'user.profile.update') }}" enctype="multipart/form-data" class="ocpf-form" id="profile-edit-form">
            @csrf
            @method('PUT')
            <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/jpg,image/png,image/webp" class="ocpf-avatar-input">

            @error('avatar')
                <p class="ocpf-error">{{ $message }}</p>
            @enderror

            <div class="ocpf-form-grid">
                <div class="ocpf-field">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name')<p class="ocpf-error">{{ $message }}</p>@enderror
                </div>
                <div class="ocpf-field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" value="{{ $user->email }}" disabled>
                    <small>Email cannot be changed from here.</small>
                </div>
                <div class="ocpf-field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+1 234 567 8900">
                    @error('phone')<p class="ocpf-error">{{ $message }}</p>@enderror
                </div>
                <div class="ocpf-field">
                    <label>Member ID</label>
                    <input type="text" value="{{ $memberId }}" disabled>
                </div>
            </div>

            <div class="ocpf-form-actions">
                <button type="submit" class="ocpf-btn-gold">Save Changes</button>
                <a href="{{ route($dashboardRoute ?? 'user.dashboard') }}" class="ocpf-btn-outline">Cancel</a>
            </div>
        </form>
    </section>

    @unless ($user->isAdmin())
        @php $kycDocuments = $kycDocuments ?? ['has_docs' => false, 'approved' => false, 'status' => 'Under Verification']; @endphp
        <div class="ocpf-modal {{ ! empty($openKycDocs) ? 'is-open' : '' }}" id="ocpf-kyc-modal" @if(empty($openKycDocs)) hidden @endif aria-hidden="{{ ! empty($openKycDocs) ? 'false' : 'true' }}">
            <div class="ocpf-modal-backdrop" data-ocpf-kyc-close></div>
            <div class="ocpf-modal-panel" role="dialog" aria-modal="true" aria-labelledby="ocpf-kyc-title">
                <div class="ocpf-modal-head">
                    <h2 id="ocpf-kyc-title"><i class="fa-solid fa-id-card" aria-hidden="true"></i> KYC Documents</h2>
                    <button type="button" class="ocpf-modal-close" data-ocpf-kyc-close aria-label="Close">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="ocpf-modal-status">
                    Status:
                    <span class="ocpf-pill {{ ! empty($kycDocuments['approved']) ? 'is-active' : 'is-warn' }}">
                        {{ $kycDocuments['status'] ?? 'Under Verification' }}
                    </span>
                </p>

                @if (! empty($kycDocuments['has_docs']))
                    <div class="ocpf-kyc-grid">
                        @if (! empty($kycDocuments['face_url']))
                            <figure class="ocpf-kyc-doc">
                                <figcaption>Face Selfie</figcaption>
                                <a href="{{ $kycDocuments['face_url'] }}" target="_blank" rel="noopener">
                                    <img src="{{ $kycDocuments['face_url'] }}" alt="Face verification document">
                                </a>
                            </figure>
                        @endif
                        @if (! empty($kycDocuments['id_url']))
                            <figure class="ocpf-kyc-doc">
                                <figcaption>ID Card</figcaption>
                                <a href="{{ $kycDocuments['id_url'] }}" target="_blank" rel="noopener">
                                    <img src="{{ $kycDocuments['id_url'] }}" alt="ID card document">
                                </a>
                            </figure>
                        @endif
                    </div>
                @else
                    <div class="ocpf-kyc-empty">
                        <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                        <p>No KYC documents found on this account yet.</p>
                    </div>
                @endif

                <div class="ocpf-modal-actions">
                    <a href="{{ route('user.support.index') }}" class="ocpf-btn-gold">Request KYC Update</a>
                    <button type="button" class="ocpf-btn-outline" data-ocpf-kyc-close>Close</button>
                </div>
            </div>
        </div>
    @endunless
    @endif
</div>
@endsection

@push('styles')
@vite(['resources/css/profile-mock.css', 'resources/js/profile-avatar-crop.js'])
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('profile-edit-panel');
    const openEdit = () => {
        if (!panel) return;
        panel.hidden = false;
        panel.classList.add('is-open');
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.setTimeout(() => document.getElementById('name')?.focus(), 280);
    };
    const closeEdit = () => {
        if (!panel) return;
        panel.hidden = true;
        panel.classList.remove('is-open');
    };

    document.getElementById('profile-edit-trigger')?.addEventListener('click', openEdit);
    document.getElementById('profile-edit-close')?.addEventListener('click', closeEdit);

    document.querySelectorAll('a[href="#profile-edit-panel"]').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            openEdit();
        });
    });

    const kycModal = document.getElementById('ocpf-kyc-modal');
    const openKyc = () => {
        if (!kycModal) return;
        kycModal.hidden = false;
        kycModal.classList.add('is-open');
        kycModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ocpf-modal-open');
    };
    const closeKyc = () => {
        if (!kycModal) return;
        kycModal.hidden = true;
        kycModal.classList.remove('is-open');
        kycModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ocpf-modal-open');
    };
    document.querySelectorAll('[data-ocpf-kyc-open]').forEach((el) => {
        el.addEventListener('click', openKyc);
    });
    document.querySelectorAll('[data-ocpf-kyc-close]').forEach((el) => {
        el.addEventListener('click', closeKyc);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeKyc();
    });
    if (kycModal?.classList.contains('is-open')) openKyc();

    // Avatar crop handled by profile-avatar-crop.js (circle drag + zoom).

    const flashCopied = (btn) => {
        if (!btn) return;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
        btn.classList.add('is-copied');
        window.setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('is-copied');
        }, 1400);
    };

    const copyText = async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (e) {
            return false;
        }
    };

    document.getElementById('copy-member-id')?.addEventListener('click', async () => {
        const value = document.getElementById('member-id-text')?.textContent?.trim() || '';
        if (await copyText(value)) flashCopied(document.getElementById('copy-member-id'));
    });

    document.getElementById('copy-referral-btn')?.addEventListener('click', async () => {
        const value = document.getElementById('referral-link-input')?.value || '';
        if (await copyText(value)) flashCopied(document.getElementById('copy-referral-btn'));
    });

    document.getElementById('share-referral-btn')?.addEventListener('click', async () => {
        const link = document.getElementById('referral-link-input')?.value || '';
        if (!link) return;
        if (navigator.share) {
            try {
                await navigator.share({ title: 'Join One-Contract', url: link });
                return;
            } catch (e) {}
        }
        if (await copyText(link)) flashCopied(document.getElementById('share-referral-btn'));
    });
});
</script>
@endpush

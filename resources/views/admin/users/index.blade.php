@extends('layouts.admin')

@section('title', 'Users — Admin')
@section('page_title', 'Users')

@section('content')
<div class="dash-page">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <div class="admin-filter-stats">
        <a href="{{ route('admin.users.index') }}" class="admin-filter-pill {{ ! $statusFilter && ! $kycFilter ? 'active' : '' }}">
            All <span>{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['status' => 'active']) }}" class="admin-filter-pill {{ $statusFilter === 'active' ? 'active' : '' }}">
            Active <span>{{ $counts['active'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['status' => 'suspended']) }}" class="admin-filter-pill {{ $statusFilter === 'suspended' ? 'active' : '' }}">
            Suspended <span>{{ $counts['suspended'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['kyc' => 'pending']) }}" class="admin-filter-pill {{ $kycFilter === 'pending' ? 'active' : '' }}">
            KYC Pending <span>{{ $counts['kyc_pending'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['kyc' => 'approved']) }}" class="admin-filter-pill {{ $kycFilter === 'approved' ? 'active' : '' }}">
            KYC Approved <span>{{ $counts['kyc_approved'] }}</span>
        </a>
        <a href="{{ route('admin.users.index', ['kyc' => 'rejected']) }}" class="admin-filter-pill {{ $kycFilter === 'rejected' ? 'active' : '' }}">
            KYC Rejected <span>{{ $counts['kyc_rejected'] }}</span>
        </a>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>All Users</h3>
            <form method="GET" action="{{ route('admin.users.index') }}" class="admin-search-form">
                @if ($statusFilter)
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                @if ($kycFilter)
                    <input type="hidden" name="kyc" value="{{ $kycFilter }}">
                @endif
                <input type="search" name="q" value="{{ $search }}" placeholder="Search name, email, member ID…">
                <button type="submit" class="btn btn-outline-dark btn-sm">Search</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Member ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>KYC</th>
                        <th>Deposits</th>
                        <th>Referrals</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php
                            $kyc = $user->kyc_status?->value ?? 'pending';
                            $kycLabel = $user->kyc_status?->label() ?? 'Under Verification';
                            $kycClass = match ($kyc) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'pending',
                            };
                            $faceUrl = $user->faceImageAdminUrl() ?? '';
                            $selfieUrl = $user->selfieImageAdminUrl() ?? '';
                            $docs = $user->kycDocumentAdminUrls();
                            $canApproveKyc = $user->hasCompleteKycDocuments();
                        @endphp
                        <tr class="{{ $user->status->value === 'suspended' ? 'row-suspended' : '' }}">
                            <td data-label="Member ID"><strong>{{ $user->member_id }}</strong></td>
                            <td data-label="Name">{{ $user->name }}</td>
                            <td data-label="Email">{{ $user->email }}</td>
                            <td data-label="KYC">
                                <span class="status-badge {{ $kycClass }}">{{ $kycLabel }}</span>
                            </td>
                            <td data-label="Deposits">{{ $user->deposits_count }}</td>
                            <td data-label="Referrals">{{ $user->referrals_count }}</td>
                            <td data-label="Status">
                                <span class="status-badge {{ $user->status->value === 'active' ? 'success' : 'danger' }}">
                                    {{ ucfirst($user->status->value) }}
                                </span>
                            </td>
                            <td data-label="Joined">{{ $user->created_at->format('M d, Y') }}</td>
                            <td data-label="Actions" class="table-actions">
                                <button
                                    type="button"
                                    class="btn btn-gold btn-sm action-btn action-btn--kyc"
                                    data-kyc-modal
                                    data-kyc-name="{{ $user->name }}"
                                    data-kyc-member="{{ $user->member_id }}"
                                    data-kyc-email="{{ $user->email }}"
                                    data-kyc-status="{{ $kyc }}"
                                    data-kyc-status-label="{{ $kycLabel }}"
                                    data-kyc-face="{{ $faceUrl }}"
                                    data-kyc-selfie="{{ $selfieUrl }}"
                                    data-kyc-id="{{ $docs['id_front'] ?? '' }}"
                                    data-kyc-id-back="{{ $docs['id_back'] ?? '' }}"
                                    data-kyc-passport="{{ $docs['passport'] ?? '' }}"
                                    data-kyc-license="{{ $docs['license_front'] ?? '' }}"
                                    data-kyc-license-back="{{ $docs['license_back'] ?? '' }}"
                                    data-kyc-can-approve="{{ $canApproveKyc ? '1' : '0' }}"
                                    data-kyc-note="{{ $user->kyc_review_note }}"
                                    data-kyc-approve="{{ route('admin.users.kyc.approve', $user) }}"
                                    data-kyc-reject="{{ route('admin.users.kyc.reject', $user) }}"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"/><path d="M4 20a8 8 0 0 1 16 0"/><path d="M16 4h4v4"/><path d="M20 4 14 10"/></svg>
                                    KYC
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-dark btn-sm action-btn action-btn--view"
                                    data-detail-modal="{{ route('admin.users.show', $user) }}"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View
                                </button>
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-dark btn-sm action-btn action-btn--edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h4l10-10a1.5 1.5 0 0 0-4-4L4 16v4z"/><path d="M13 7l4 4"/></svg>
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline-form" data-confirm="Delete {{ $user->name }} permanently? This action cannot be undone." data-confirm-ok="Yes, Delete" data-confirm-variant="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm action-btn action-btn--delete">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 14h10l1-14"/><path d="M10 11v6M14 11v6"/></svg>
                                        Delete
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="inline-form" data-confirm="{{ $user->status->value === 'active' ? 'Suspend' : 'Activate' }} {{ $user->name }}?" data-confirm-ok="Yes, {{ $user->status->value === 'active' ? 'Suspend' : 'Activate' }}" data-confirm-variant="{{ $user->status->value === 'active' ? 'danger' : '' }}">
                                    @csrf
                                    @if ($user->status->value === 'active')
                                        <button type="submit" class="btn btn-outline-danger btn-sm action-btn">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 4.5h3.4L21 18.5H3z"/></svg>
                                            Suspend
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-outline-success btn-sm action-btn">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l4 4L19 6"/><path d="M12 22A10 10 0 1 0 12 2a10 10 0 0 0 0 20z"/></svg>
                                            Activate
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty-cell">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
</div>

{{-- Dedicated KYC review modal --}}
<div class="kyc-modal-backdrop" id="kyc-modal" hidden>
    <div class="kyc-modal" role="dialog" aria-modal="true" aria-labelledby="kyc-modal-title">
        <button type="button" class="modal-close-btn detail-modal-close kyc-modal-close-btn" data-kyc-close aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>

        <header class="kyc-modal-head admin-detail-hero">
            <div class="admin-detail-hero-main">
                <div class="admin-detail-hero-icon" aria-hidden="true">
                    @include('admin.partials.detail-icon', ['icon' => 'user'])
                </div>
                <div>
                    <p class="admin-detail-label">Identity Review</p>
                    <h3 class="admin-detail-title" id="kyc-modal-title" data-kyc-fill="name">—</h3>
                    <div class="admin-detail-badges">
                        <span class="admin-detail-pill" data-kyc-fill="member">—</span>
                        <span class="status-badge pending" data-kyc-fill="status-badge">Pending</span>
                        <span class="admin-detail-pill" data-kyc-fill="email">—</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="kyc-modal-body">
            <div class="kyc-docs-grid kyc-docs-grid--docs">
                <figure class="kyc-doc-card">
                    <figcaption>
                        <span>ID Card Front</span>
                        <small>National ID front</small>
                    </figcaption>
                    <a class="kyc-doc-frame" data-kyc-fill="id-link" href="#" target="_blank" rel="noopener">
                        <img data-kyc-fill="id" alt="ID card front" hidden loading="lazy" decoding="async">
                        <div class="kyc-doc-empty" data-kyc-fill="id-empty">No ID front uploaded</div>
                        <div class="kyc-doc-broken" data-kyc-fill="id-broken" hidden>Image unavailable</div>
                    </a>
                </figure>
                <figure class="kyc-doc-card">
                    <figcaption>
                        <span>ID Card Back</span>
                        <small>National ID back</small>
                    </figcaption>
                    <a class="kyc-doc-frame" data-kyc-fill="id-back-link" href="#" target="_blank" rel="noopener">
                        <img data-kyc-fill="id-back" alt="ID card back" hidden loading="lazy" decoding="async">
                        <div class="kyc-doc-empty" data-kyc-fill="id-back-empty">No ID back uploaded</div>
                        <div class="kyc-doc-broken" data-kyc-fill="id-back-broken" hidden>Image unavailable</div>
                    </a>
                </figure>
                <figure class="kyc-doc-card">
                    <figcaption>
                        <span>Passport</span>
                        <small>Passport photo page</small>
                    </figcaption>
                    <a class="kyc-doc-frame" data-kyc-fill="passport-link" href="#" target="_blank" rel="noopener">
                        <img data-kyc-fill="passport" alt="Passport" hidden loading="lazy" decoding="async">
                        <div class="kyc-doc-empty" data-kyc-fill="passport-empty">No passport uploaded</div>
                        <div class="kyc-doc-broken" data-kyc-fill="passport-broken" hidden>Image unavailable</div>
                    </a>
                </figure>
                <figure class="kyc-doc-card">
                    <figcaption>
                        <span>License Front</span>
                        <small>Driver's license front</small>
                    </figcaption>
                    <a class="kyc-doc-frame" data-kyc-fill="license-link" href="#" target="_blank" rel="noopener">
                        <img data-kyc-fill="license" alt="License front" hidden loading="lazy" decoding="async">
                        <div class="kyc-doc-empty" data-kyc-fill="license-empty">No license front uploaded</div>
                        <div class="kyc-doc-broken" data-kyc-fill="license-broken" hidden>Image unavailable</div>
                    </a>
                </figure>
                <figure class="kyc-doc-card">
                    <figcaption>
                        <span>License Back</span>
                        <small>Driver's license back</small>
                    </figcaption>
                    <a class="kyc-doc-frame" data-kyc-fill="license-back-link" href="#" target="_blank" rel="noopener">
                        <img data-kyc-fill="license-back" alt="License back" hidden loading="lazy" decoding="async">
                        <div class="kyc-doc-empty" data-kyc-fill="license-back-empty">No license back uploaded</div>
                        <div class="kyc-doc-broken" data-kyc-fill="license-back-broken" hidden>Image unavailable</div>
                    </a>
                </figure>
            </div>
            <div class="kyc-docs-grid kyc-docs-grid--verify">
                <figure class="kyc-doc-card">
                    <figcaption>
                        <span>Selfie</span>
                        <small>Selfie verification</small>
                    </figcaption>
                    <a class="kyc-doc-frame" data-kyc-fill="selfie-link" href="#" target="_blank" rel="noopener">
                        <img data-kyc-fill="selfie" alt="Selfie verification" hidden loading="lazy" decoding="async">
                        <div class="kyc-doc-empty" data-kyc-fill="selfie-empty">No selfie uploaded</div>
                        <div class="kyc-doc-broken" data-kyc-fill="selfie-broken" hidden>Image unavailable</div>
                    </a>
                </figure>
                <figure class="kyc-doc-card">
                    <figcaption>
                        <span>Live Check</span>
                        <small>Liveness capture</small>
                    </figcaption>
                    <a class="kyc-doc-frame" data-kyc-fill="face-link" href="#" target="_blank" rel="noopener">
                        <img data-kyc-fill="face" alt="Live verification" hidden loading="lazy" decoding="async">
                        <div class="kyc-doc-empty" data-kyc-fill="face-empty">No liveness image</div>
                        <div class="kyc-doc-broken" data-kyc-fill="face-broken" hidden>Image unavailable</div>
                    </a>
                </figure>
            </div>

            <p class="kyc-review-note" data-kyc-fill="note-wrap" hidden>
                <strong>Previous note:</strong> <span data-kyc-fill="note"></span>
            </p>
            <p class="kyc-approve-blocked" data-kyc-fill="docs-missing" hidden>
                ID document, selfie, and live verification images are required before KYC can be approved.
            </p>
        </div>

        <footer class="kyc-modal-footer">
            <div class="kyc-footer-note-field">
                <label for="kyc-reject-note">Reject reason <span>(required)</span></label>
                <input id="kyc-reject-note" type="text" name="note" form="kyc-reject-form" placeholder="Reason for rejection…" maxlength="500" data-kyc-fill="note-input">
            </div>
            <div class="kyc-footer-btns">
                <form method="POST" id="kyc-approve-form" data-kyc-fill="approve-form">
                    @csrf
                    <button type="submit" class="btn btn-gold" data-kyc-fill="approve-btn" title="Requires face and ID card images">Approve KYC</button>
                </form>
                <form method="POST" id="kyc-reject-form" data-kyc-fill="reject-form">
                    @csrf
                    <button type="submit" class="btn btn-outline-dark" data-kyc-fill="reject-btn">Reject KYC</button>
                </form>
            </div>
        </footer>
    </div>
</div>
@endsection

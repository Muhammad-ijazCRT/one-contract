@extends('layouts.admin')

@section('title', 'User Details — Admin')
@section('page_title', 'User Details')

@section('content')
@php
    $status = $user->status->value;
    $statusClass = $status === 'active' ? 'status-badge success' : 'status-badge danger';
    $kyc = $user->kyc_status?->value ?? 'pending';
    $kycClass = match ($kyc) {
        'approved' => 'status-badge success',
        'rejected' => 'status-badge danger',
        default => 'status-badge pending',
    };
    $faceUrl = $user->faceImageAdminUrl();
    $selfieUrl = $user->selfieImageAdminUrl();
    $docs = $user->kycDocumentAdminUrls();
@endphp
<div class="dash-page admin-detail-page admin-detail-page--indigo">
    <div class="panel admin-detail-panel">
        @include('admin.partials.detail-hero', [
            'icon' => 'user',
            'label' => 'User Profile',
            'title' => $user->name,
            'backUrl' => route('admin.users.index'),
            'backLabel' => 'Back to Users',
            'badges' => [
                ['text' => $user->member_id, 'class' => 'admin-detail-pill'],
                ['text' => ucfirst($status), 'class' => $statusClass],
                ['text' => 'KYC: '.($user->kyc_status?->label() ?? 'Pending'), 'class' => $kycClass],
            ],
            'actions' => '<a href="'.route('admin.users.edit', $user).'" class="btn btn-outline-dark btn-sm">Edit User</a>',
        ])

        <div class="admin-detail-info-grid">
            @include('admin.partials.detail-info-card', ['icon' => 'user', 'label' => 'Name', 'value' => $user->name])
            @include('admin.partials.detail-info-card', ['icon' => 'email', 'label' => 'Email', 'value' => $user->email, 'href' => 'mailto:'.$user->email])
            @include('admin.partials.detail-info-card', ['icon' => 'subject', 'label' => 'Member ID', 'value' => $user->member_id])
            @include('admin.partials.detail-info-card', ['icon' => 'status', 'label' => 'Status', 'value' => ucfirst($status)])
            @include('admin.partials.detail-info-card', ['icon' => 'status', 'label' => 'KYC', 'value' => $user->kyc_status?->label() ?? 'Pending'])
            @include('admin.partials.detail-info-card', ['icon' => 'deposit', 'label' => 'Deposits', 'value' => (string) $user->deposits_count])
            @include('admin.partials.detail-info-card', ['icon' => 'referral', 'label' => 'Referrals', 'value' => (string) $user->referrals_count])
            @include('admin.partials.detail-info-card', ['icon' => 'withdrawal', 'label' => 'Withdrawals', 'value' => (string) $user->withdrawal_requests_count])
            @include('admin.partials.detail-info-card', ['icon' => 'time', 'label' => 'Joined', 'value' => $user->created_at->format('M d, Y'), 'sub' => $user->created_at->format('h:i A')])
        </div>

        <div class="kyc-detail-section" style="margin-top:1rem;">
            <div class="panel-header" style="padding-left:0;padding-right:0;">
                <h3>Multi-currency wallets</h3>
            </div>
            <div class="admin-detail-info-grid">
                @foreach(($walletCards ?? []) as $card)
                    @include('admin.partials.detail-info-card', [
                        'icon' => 'deposit',
                        'label' => $card['code'].' · '.$card['label'],
                        'value' => $card['formatted'],
                    ])
                @endforeach
            </div>
        </div>

        <div class="kyc-detail-section">
            <div class="panel-header" style="padding-left:0;padding-right:0;">
                <h3>Verification Documents</h3>
            </div>
            @if($user->kyc_review_note)
                <p class="kyc-review-note"><strong>Review note:</strong> {{ $user->kyc_review_note }}</p>
            @endif
            <div class="kyc-docs-grid kyc-docs-grid--docs">
                @foreach ([
                    ['label' => 'ID Card Front', 'hint' => 'National ID front', 'url' => $docs['id_front'] ?? null],
                    ['label' => 'ID Card Back', 'hint' => 'National ID back', 'url' => $docs['id_back'] ?? null],
                    ['label' => 'Passport', 'hint' => 'Passport photo page', 'url' => $docs['passport'] ?? null],
                    ['label' => 'License Front', 'hint' => "Driver's license front", 'url' => $docs['license_front'] ?? null],
                    ['label' => 'License Back', 'hint' => "Driver's license back", 'url' => $docs['license_back'] ?? null],
                ] as $card)
                    <figure class="kyc-doc-card">
                        <figcaption>
                            <span>{{ $card['label'] }}</span>
                            <small>{{ $card['hint'] }}</small>
                        </figcaption>
                        @if($card['url'])
                            <a class="kyc-doc-frame" href="{{ $card['url'] }}" target="_blank" rel="noopener">
                                <img src="{{ $card['url'] }}" alt="{{ $card['label'] }}" loading="lazy" decoding="async">
                            </a>
                        @else
                            <div class="kyc-doc-frame">
                                <div class="kyc-doc-empty">Not uploaded</div>
                            </div>
                        @endif
                    </figure>
                @endforeach
            </div>
            <div class="kyc-docs-grid kyc-docs-grid--verify">
                <figure class="kyc-doc-card">
                    <figcaption>
                        <span>Selfie</span>
                        <small>Selfie verification</small>
                    </figcaption>
                    @if($selfieUrl)
                        <a class="kyc-doc-frame" href="{{ $selfieUrl }}" target="_blank" rel="noopener">
                            <img src="{{ $selfieUrl }}" alt="Selfie verification" loading="lazy" decoding="async">
                        </a>
                    @else
                        <div class="kyc-doc-frame">
                            <div class="kyc-doc-empty">No selfie on file</div>
                        </div>
                    @endif
                </figure>
                <figure class="kyc-doc-card">
                    <figcaption>
                        <span>Live Check</span>
                        <small>Dojah liveness (same selfie we store)</small>
                    </figcaption>
                    @if($faceUrl)
                        <a class="kyc-doc-frame" href="{{ $faceUrl }}" target="_blank" rel="noopener">
                            <img src="{{ $faceUrl }}" alt="Live verification" loading="lazy" decoding="async">
                        </a>
                    @else
                        <div class="kyc-doc-frame">
                            <div class="kyc-doc-empty">No liveness image on file</div>
                        </div>
                    @endif
                </figure>
            </div>
        </div>

        @if(($faceVerificationSessions ?? collect())->isNotEmpty())
            <div class="kyc-detail-section">
                <div class="panel-header" style="padding-left:0;padding-right:0;">
                    <h3>Face Verification Audit</h3>
                </div>
                <div class="table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Liveness</th>
                                <th>Face match</th>
                                <th>Failure</th>
                                <th>Evidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($faceVerificationSessions as $session)
                                <tr>
                                    <td data-label="When">{{ $session->started_at?->format('M d, Y h:i A') ?? '—' }}</td>
                                    <td data-label="Purpose">{{ $session->purpose->label() }}</td>
                                    <td data-label="Status">{{ $session->status->label() }}</td>
                                    <td data-label="Liveness">
                                        @if($session->liveness_passed)
                                            Pass ({{ $session->liveness_confidence ?? '—' }}%)
                                        @else
                                            Fail
                                        @endif
                                    </td>
                                    <td data-label="Face match">
                                        @if($session->face_match_passed === null)
                                            —
                                        @elseif($session->face_match_passed)
                                            Pass ({{ $session->face_match_confidence ?? '—' }}%)
                                            @if($session->purpose->value === 'registration')
                                                <small> vs ID</small>
                                            @endif
                                        @else
                                            Fail
                                        @endif
                                    </td>
                                    <td data-label="Failure">{{ $session->failure_reason ?? '—' }}</td>
                                    <td data-label="Evidence">
                                        @if($session->capture_image_path)
                                            <a href="{{ route('admin.users.face-session.image', [$user, $session]) }}" target="_blank" rel="noopener">View capture</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(($dojahChecks ?? collect())->isNotEmpty())
            <div class="kyc-detail-section">
                <div class="panel-header" style="padding-left:0;padding-right:0;">
                    <h3>Dojah Checks</h3>
                </div>
                <div class="table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Type</th>
                                <th>Purpose</th>
                                <th>Result</th>
                                <th>Score</th>
                                <th>Decision</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dojahChecks as $check)
                                <tr>
                                    <td data-label="When">{{ $check->created_at?->format('M d, Y h:i A') ?? '—' }}</td>
                                    <td data-label="Type">{{ ucfirst($check->type) }}</td>
                                    <td data-label="Purpose">{{ $check->purpose ?: '—' }}</td>
                                    <td data-label="Result">{{ $check->passed ? 'Passed' : 'Failed' }}</td>
                                    <td data-label="Score">{{ $check->probability ?? $check->score ?? '—' }}</td>
                                    <td data-label="Decision">{{ $check->decision ?: '—' }}</td>
                                    <td data-label="Message">{{ $check->message ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

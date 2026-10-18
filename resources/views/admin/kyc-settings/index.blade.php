@extends('layouts.admin')

@section('title', 'KYC Settings — Admin')
@section('page_title', 'KYC Settings')

@section('content')
@php
    $d = $dojah;
    $mode = old('mode', $d['mode']);
@endphp
<div class="dash-page oc-aps">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="dash-alert dash-alert-error">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="dash-alert dash-alert-error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="ocaps-hero">
        <div class="ocaps-hero-copy">
            <h2>Dojah KYC &amp; Monitoring</h2>
            <p>We keep passport/ID and selfie on our servers. Dojah confirms the person is live and scores deposits and withdrawals.</p>
        </div>
        <span class="ocaps-hero-badge ocaps-hero-badge--{{ $d['mode'] === 'live' ? 'live' : 'sandbox' }}">
            <i class="fa-solid {{ $d['mode'] === 'live' ? 'fa-bolt' : 'fa-flask' }}"></i>
            Active · {{ strtoupper($d['mode']) }}
        </span>
    </section>

    <section class="ocaps-status" aria-label="Active status">
        <article class="ocaps-stat">
            <span>Provider</span>
            <strong class="{{ $d['enabled'] ? 'is-ok' : 'is-warn' }}">{{ $d['enabled'] ? 'Enabled' : 'Off' }}</strong>
        </article>
        <article class="ocaps-stat">
            <span>Liveness</span>
            <strong class="{{ $d['liveness_ready'] ? 'is-ok' : 'is-warn' }}">
                {{ $d['liveness_ready'] ? 'Ready' : 'Needs keys' }}
            </strong>
        </article>
        <article class="ocaps-stat">
            <span>Transaction monitoring</span>
            <strong class="{{ $d['monitoring_ready'] ? 'is-ok' : 'is-warn' }}">
                {{ $d['monitoring_ready'] ? 'Ready' : 'Needs ingest key' }}
            </strong>
        </article>
        <article class="ocaps-stat">
            <span>API Base</span>
            <strong><code>{{ $d['active_base_url'] }}</code></strong>
        </article>
    </section>

    <form method="POST" action="{{ route('admin.kyc-settings.update') }}" class="ocaps-form" id="dojah-settings-form">
        @csrf
        @method('PUT')

        <section class="ocaps-card">
            <div class="ocaps-card-head">
                <div class="ocaps-card-icon ocaps-card-icon--gold"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <h3>What we store vs what Dojah does</h3>
                    <p>Document upload and selfie stay in One Contract. Live-person check and transaction monitoring run through Dojah.</p>
                </div>
            </div>
            <div class="ocaps-grid ocaps-grid--checks">
                <label class="ocaps-check">
                    <input type="checkbox" name="enabled" value="1" {{ old('enabled', $d['enabled']) ? 'checked' : '' }}>
                    <span>Enable Dojah</span>
                </label>
                <label class="ocaps-check">
                    <input type="checkbox" name="liveness_enabled" value="1" {{ old('liveness_enabled', $d['liveness_enabled']) ? 'checked' : '' }}>
                    <span>Require live verification on selfie</span>
                </label>
                <label class="ocaps-check">
                    <input type="checkbox" name="transaction_monitoring_enabled" value="1" {{ old('transaction_monitoring_enabled', $d['transaction_monitoring_enabled']) ? 'checked' : '' }}>
                    <span>Send deposits &amp; withdrawals to EasyDetect</span>
                </label>
                <label class="ocaps-check">
                    <input type="checkbox" name="block_risky_withdrawals" value="1" {{ old('block_risky_withdrawals', $d['block_risky_withdrawals']) ? 'checked' : '' }}>
                    <span>Block withdrawals Dojah marks as Block</span>
                </label>
            </div>
        </section>

        <section class="ocaps-card">
            <div class="ocaps-card-head">
                <div class="ocaps-card-icon ocaps-card-icon--gold"><i class="fa-solid fa-toggle-on"></i></div>
                <div>
                    <h3>Environment</h3>
                    <p>Use sandbox with <code>test_</code> keys. Switch to live only after App ID and production keys are in place.</p>
                </div>
            </div>
            <div class="ocaps-modes">
                <label class="ocaps-mode ocaps-mode--sandbox {{ $mode === 'sandbox' ? 'is-active--sandbox' : '' }}">
                    <input type="radio" name="mode" value="sandbox" {{ $mode === 'sandbox' ? 'checked' : '' }} required>
                    <div class="ocaps-mode-top">
                        <strong><i class="fa-solid fa-flask"></i> Sandbox</strong>
                        <span class="ocaps-mode-dot" aria-hidden="true"></span>
                    </div>
                    <small>Test KYC via <code>sandbox.dojah.io</code></small>
                </label>
                <label class="ocaps-mode ocaps-mode--live {{ $mode === 'live' ? 'is-active--live' : '' }}">
                    <input type="radio" name="mode" value="live" {{ $mode === 'live' ? 'checked' : '' }}>
                    <div class="ocaps-mode-top">
                        <strong><i class="fa-solid fa-bolt"></i> Live</strong>
                        <span class="ocaps-mode-dot" aria-hidden="true"></span>
                    </div>
                    <small>Production KYC via <code>api.dojah.io</code></small>
                </label>
            </div>
        </section>

        <section class="ocaps-card">
            <div class="ocaps-card-head">
                <div class="ocaps-card-icon ocaps-card-icon--gold"><i class="fa-solid fa-key"></i></div>
                <div>
                    <h3>Sandbox keys</h3>
                    <p>Public and secret keys came from Dojah. App ID is copied from Developers → Configuration. Ingest key is from EasyDetect flow settings.</p>
                </div>
            </div>
            <div class="ocaps-grid">
                <div class="ocaps-field">
                    <label for="dojah_sandbox_public_key">Sandbox Public Key</label>
                    <div class="ocaps-secret">
                        <input type="password" id="dojah_sandbox_public_key" name="sandbox_public_key" value="{{ old('sandbox_public_key', $d['sandbox_public_key']) }}" placeholder="Sandbox public key" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show public key">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="ocaps-field">
                    <label for="dojah_sandbox_secret_key">Sandbox Secret Key</label>
                    <div class="ocaps-secret">
                        <input type="password" id="dojah_sandbox_secret_key" name="sandbox_secret_key" value="{{ old('sandbox_secret_key', $d['sandbox_secret_key']) }}" placeholder="Sandbox secret key" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show secret key">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="ocaps-field">
                    <label for="dojah_sandbox_app_id">Sandbox App ID</label>
                    <div class="ocaps-secret">
                        <input type="password" id="dojah_sandbox_app_id" name="sandbox_app_id" value="{{ old('sandbox_app_id', $d['sandbox_app_id']) }}" placeholder="From Dojah dashboard" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show App ID">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                    <small>Required for liveness API. Not the same as the public key.</small>
                </div>
                <div class="ocaps-field">
                    <label for="dojah_sandbox_ingest_key">Sandbox Ingest Key</label>
                    <div class="ocaps-secret">
                        <input type="password" id="dojah_sandbox_ingest_key" name="sandbox_ingest_key" value="{{ old('sandbox_ingest_key', $d['sandbox_ingest_key']) }}" placeholder="EasyDetect ingest key" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show ingest key">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="ocaps-field ocaps-field--full">
                    <label for="dojah_sandbox_base_url">Sandbox Base URL</label>
                    <input type="url" id="dojah_sandbox_base_url" name="sandbox_base_url" value="{{ old('sandbox_base_url', $d['sandbox_base_url']) }}" required>
                </div>
            </div>
        </section>

        <section class="ocaps-card">
            <div class="ocaps-card-head">
                <div class="ocaps-card-icon ocaps-card-icon--gold"><i class="fa-solid fa-lock"></i></div>
                <div>
                    <h3>Live keys</h3>
                    <p>Leave blank until you switch to production keys from Dojah.</p>
                </div>
            </div>
            <div class="ocaps-grid">
                <div class="ocaps-field">
                    <label for="dojah_live_public_key">Live Public Key</label>
                    <div class="ocaps-secret">
                        <input type="password" id="dojah_live_public_key" name="live_public_key" value="{{ old('live_public_key', $d['live_public_key']) }}" placeholder="Live public key" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show live public key">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="ocaps-field">
                    <label for="dojah_live_secret_key">Live Secret Key</label>
                    <div class="ocaps-secret">
                        <input type="password" id="dojah_live_secret_key" name="live_secret_key" value="{{ old('live_secret_key', $d['live_secret_key']) }}" placeholder="Live secret key" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show live secret key">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="ocaps-field">
                    <label for="dojah_live_app_id">Live App ID</label>
                    <div class="ocaps-secret">
                        <input type="password" id="dojah_live_app_id" name="live_app_id" value="{{ old('live_app_id', $d['live_app_id']) }}" placeholder="From Dojah dashboard" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show live App ID">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="ocaps-field">
                    <label for="dojah_live_ingest_key">Live Ingest Key</label>
                    <div class="ocaps-secret">
                        <input type="password" id="dojah_live_ingest_key" name="live_ingest_key" value="{{ old('live_ingest_key', $d['live_ingest_key']) }}" placeholder="EasyDetect ingest key" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show live ingest key">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="ocaps-field ocaps-field--full">
                    <label for="dojah_live_base_url">Live Base URL</label>
                    <input type="url" id="dojah_live_base_url" name="live_base_url" value="{{ old('live_base_url', $d['live_base_url']) }}" required>
                </div>
            </div>
        </section>

        <section class="ocaps-card">
            <div class="ocaps-card-head">
                <div class="ocaps-card-icon ocaps-card-icon--gold"><i class="fa-solid fa-sliders"></i></div>
                <div>
                    <h3>Thresholds &amp; webhook</h3>
                    <p>Selfie must pass Dojah liveness at or above this score. Paste the webhook URL in EasyDetect if you use transaction monitoring.</p>
                </div>
            </div>
            <div class="ocaps-grid">
                <div class="ocaps-field">
                    <label for="dojah_min_liveness_probability">Minimum liveness score</label>
                    <input type="number" id="dojah_min_liveness_probability" name="min_liveness_probability" min="0" max="100" step="1" value="{{ old('min_liveness_probability', $d['min_liveness_probability']) }}" required>
                </div>
                <div class="ocaps-field">
                    <label for="dojah_webhook_url">Webhook URL</label>
                    <input type="url" id="dojah_webhook_url" name="webhook_url" value="{{ old('webhook_url', $d['webhook_url']) }}" placeholder="{{ url('/dojah/webhook') }}">
                    <small>Public HTTPS URL Dojah can call after EasyDetect scoring.</small>
                </div>
            </div>
        </section>

        <div class="ocaps-actions">
            <button type="submit" class="ocaps-save">
                <i class="fa-solid fa-floppy-disk"></i>
                Save KYC Settings
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.kyc-settings.test') }}" class="ocaps-form" style="margin-top:0;">
        @csrf
        <button type="submit" class="ocaps-save" style="background:#0f172a;border:1px solid rgba(148,163,184,.25);">
            <i class="fa-solid fa-plug"></i>
            Test Dojah connection
        </button>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/payment-settings-admin.css') }}?v={{ @filemtime(public_path('css/payment-settings-admin.css')) ?: time() }}">
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('dojah-settings-form');
    if (!form) return;

    const syncModes = () => {
        form.querySelectorAll('.ocaps-mode').forEach((card) => {
            const checked = card.querySelector('input[type="radio"]')?.checked;
            card.classList.toggle('is-active--sandbox', checked && card.classList.contains('ocaps-mode--sandbox'));
            card.classList.toggle('is-active--live', checked && card.classList.contains('ocaps-mode--live'));
        });
    };
    form.querySelectorAll('input[name="mode"]').forEach((input) => {
        input.addEventListener('change', syncModes);
    });
    syncModes();

    form.querySelectorAll('[data-toggle-secret]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement?.querySelector('input');
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.classList.toggle('is-visible', show);
            btn.setAttribute('aria-label', show ? 'Hide value' : 'Show value');
        });
    });
});
</script>
@endpush

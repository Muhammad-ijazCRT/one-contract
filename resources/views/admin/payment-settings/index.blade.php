@extends('layouts.admin')

@section('title', 'Payment Settings — Admin')
@section('page_title', 'Payment Settings')

@section('content')
@php
    $s = $settings;
    $mode = old('mode', $s['mode']);
@endphp
<div class="dash-page oc-aps">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <section class="ocaps-hero">
        <div class="ocaps-hero-copy">
            <h2>NOWPayments Gateway</h2>
            <p>Manage sandbox and live credentials from the database. Switching mode instantly changes which API base and keys deposits use.</p>
        </div>
        <span class="ocaps-hero-badge ocaps-hero-badge--{{ $s['mode'] === 'live' ? 'live' : 'sandbox' }}">
            <i class="fa-solid {{ $s['mode'] === 'live' ? 'fa-bolt' : 'fa-flask' }}"></i>
            Active · {{ strtoupper($s['mode']) }}
        </span>
    </section>

    <section class="ocaps-status" aria-label="Active status">
        <article class="ocaps-stat">
            <span>Active Mode</span>
            <strong class="{{ $s['mode'] === 'live' ? 'is-ok' : 'is-warn' }}">{{ strtoupper($s['mode']) }}</strong>
        </article>
        <article class="ocaps-stat">
            <span>API Base URL</span>
            <strong><code>{{ $s['active_base_url'] }}</code></strong>
        </article>
        <article class="ocaps-stat">
            <span>API Key</span>
            <strong class="{{ $s['active_api_key_set'] ? 'is-ok' : 'is-warn' }}">
                {{ $s['active_api_key_set'] ? 'Configured' : 'Missing' }}
            </strong>
        </article>
        <article class="ocaps-stat">
            <span>Custody Login</span>
            <strong class="{{ ! empty($s['active_custody_email_set']) && ! empty($s['active_custody_password_set']) ? 'is-ok' : 'is-warn' }}">
                {{ ! empty($s['active_custody_email_set']) && ! empty($s['active_custody_password_set']) ? 'Configured' : 'Missing' }}
            </strong>
        </article>
    </section>

    <form method="POST" action="{{ route('admin.payment-settings.update') }}" class="ocaps-form" id="payment-settings-form">
        @csrf
        @method('PUT')

        <section class="ocaps-card">
            <div class="ocaps-card-head">
                <div class="ocaps-card-icon ocaps-card-icon--gold"><i class="fa-solid fa-toggle-on"></i></div>
                <div>
                    <h3>Environment Mode</h3>
                    <p>Sandbox for test deposits. Live for real money. Credentials for both stay saved.</p>
                </div>
            </div>
            <div class="ocaps-modes">
                <label class="ocaps-mode ocaps-mode--sandbox {{ $mode === 'sandbox' ? 'is-active--sandbox' : '' }}">
                    <input type="radio" name="mode" value="sandbox" {{ $mode === 'sandbox' ? 'checked' : '' }} required>
                    <div class="ocaps-mode-top">
                        <strong><i class="fa-solid fa-flask"></i> Sandbox</strong>
                        <span class="ocaps-mode-dot" aria-hidden="true"></span>
                    </div>
                    <small>Test payments via <code>api-sandbox.nowpayments.io</code></small>
                </label>
                <label class="ocaps-mode ocaps-mode--live {{ $mode === 'live' ? 'is-active--live' : '' }}">
                    <input type="radio" name="mode" value="live" {{ $mode === 'live' ? 'checked' : '' }}>
                    <div class="ocaps-mode-top">
                        <strong><i class="fa-solid fa-bolt"></i> Live</strong>
                        <span class="ocaps-mode-dot" aria-hidden="true"></span>
                    </div>
                    <small>Real deposits via <code>api.nowpayments.io</code></small>
                </label>
            </div>
            @error('mode')<span class="field-error">{{ $message }}</span>@enderror
        </section>

        <section class="ocaps-card">
            <div class="ocaps-card-head">
                <div class="ocaps-card-icon ocaps-card-icon--amber"><i class="fa-solid fa-key"></i></div>
                <div>
                    <h3>Sandbox Credentials</h3>
                    <p>Used when mode is Sandbox. Keep test keys separate from production.</p>
                </div>
            </div>
            <div class="ocaps-grid">
                <div class="ocaps-field">
                    <label for="sandbox_api_key">Sandbox API Key</label>
                    <div class="ocaps-secret">
                        <input type="password" id="sandbox_api_key" name="sandbox_api_key" value="{{ old('sandbox_api_key', $s['sandbox_api_key']) }}" placeholder="Sandbox API key" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show API key">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('sandbox_api_key')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="ocaps-field">
                    <label for="sandbox_public_key">Sandbox Public Key</label>
                    <div class="ocaps-secret">
                        <input type="password" id="sandbox_public_key" name="sandbox_public_key" value="{{ old('sandbox_public_key', $s['sandbox_public_key']) }}" placeholder="Sandbox public key" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show public key">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('sandbox_public_key')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="ocaps-field">
                    <label for="sandbox_ipn_secret">Sandbox IPN Secret</label>
                    <div class="ocaps-secret">
                        <input type="password" id="sandbox_ipn_secret" name="sandbox_ipn_secret" value="{{ old('sandbox_ipn_secret', $s['sandbox_ipn_secret']) }}" placeholder="IPN HMAC secret" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show IPN secret">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('sandbox_ipn_secret')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="ocaps-field">
                    <label for="sandbox_url">Sandbox Base URL</label>
                    <input type="url" id="sandbox_url" name="sandbox_url" value="{{ old('sandbox_url', $s['sandbox_url']) }}" required>
                    @error('sandbox_url')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="ocaps-card">
            <div class="ocaps-card-head">
                <div class="ocaps-card-icon ocaps-card-icon--green"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <h3>Live Credentials</h3>
                    <p>Used when mode is Live. Only switch after keys and IPN URL are verified.</p>
                </div>
            </div>
            <div class="ocaps-grid">
                <div class="ocaps-field">
                    <label for="live_api_key">Live API Key</label>
                    <div class="ocaps-secret">
                        <input type="password" id="live_api_key" name="live_api_key" value="{{ old('live_api_key', $s['live_api_key']) }}" placeholder="Live API key" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show API key">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('live_api_key')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="ocaps-field">
                    <label for="live_public_key">Live Public Key</label>
                    <div class="ocaps-secret">
                        <input type="password" id="live_public_key" name="live_public_key" value="{{ old('live_public_key', $s['live_public_key']) }}" placeholder="Live public key" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show public key">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('live_public_key')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="ocaps-field">
                    <label for="live_ipn_secret">Live IPN Secret</label>
                    <div class="ocaps-secret">
                        <input type="password" id="live_ipn_secret" name="live_ipn_secret" value="{{ old('live_ipn_secret', $s['live_ipn_secret']) }}" placeholder="IPN HMAC secret" autocomplete="off">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show IPN secret">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('live_ipn_secret')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="ocaps-field">
                    <label for="live_url">Live Base URL</label>
                    <input type="url" id="live_url" name="live_url" value="{{ old('live_url', $s['live_url']) }}" required>
                    @error('live_url')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="ocaps-card">
            <div class="ocaps-card-head">
                <div class="ocaps-card-icon ocaps-card-icon--blue"><i class="fa-solid fa-right-left"></i></div>
                <div>
                    <h3>Custody Conversion (Crypto Swaps)</h3>
                    <p>Required for backend crypto-to-crypto conversions via NOWPayments liquidity. Use your NOWPayments Personal Account email and password (JWT auth).</p>
                </div>
            </div>
            <div class="ocaps-grid">
                <div class="ocaps-field">
                    <label for="sandbox_custody_email">Sandbox Custody Email</label>
                    <input type="email" id="sandbox_custody_email" name="sandbox_custody_email" value="{{ old('sandbox_custody_email', $s['sandbox_custody_email']) }}" placeholder="account@email.com" autocomplete="off">
                    @error('sandbox_custody_email')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="ocaps-field">
                    <label for="sandbox_custody_password">Sandbox Custody Password</label>
                    <div class="ocaps-secret">
                        <input type="password" id="sandbox_custody_password" name="sandbox_custody_password" value="" placeholder="{{ ! empty($s['sandbox_custody_password_set']) ? '•••••••• (leave blank to keep)' : 'Custody password' }}" autocomplete="new-password">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show password">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('sandbox_custody_password')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="ocaps-field">
                    <label for="live_custody_email">Live Custody Email</label>
                    <input type="email" id="live_custody_email" name="live_custody_email" value="{{ old('live_custody_email', $s['live_custody_email']) }}" placeholder="account@email.com" autocomplete="off">
                    @error('live_custody_email')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="ocaps-field">
                    <label for="live_custody_password">Live Custody Password</label>
                    <div class="ocaps-secret">
                        <input type="password" id="live_custody_password" name="live_custody_password" value="" placeholder="{{ ! empty($s['live_custody_password_set']) ? '•••••••• (leave blank to keep)' : 'Custody password' }}" autocomplete="new-password">
                        <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show password">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('live_custody_password')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="ocaps-card">
            <div class="ocaps-card-head">
                <div class="ocaps-card-icon ocaps-card-icon--blue"><i class="fa-solid fa-link"></i></div>
                <div>
                    <h3>Callbacks &amp; Endpoints</h3>
                    <p>Invoice path and return URLs sent to NOWPayments with every deposit.</p>
                </div>
            </div>
            <div class="ocaps-grid">
                <div class="ocaps-field">
                    <label for="checkout_endpoint">Checkout Endpoint</label>
                    <input type="text" id="checkout_endpoint" name="checkout_endpoint" value="{{ old('checkout_endpoint', $s['checkout_endpoint']) }}" required placeholder="/v1/invoice">
                    @error('checkout_endpoint')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="ocaps-field">
                    <label for="ipn_callback_url">IPN Callback URL</label>
                    <input type="url" id="ipn_callback_url" name="ipn_callback_url" value="{{ old('ipn_callback_url', $s['ipn_callback_url']) }}" required placeholder="https://your-domain.com/nowpayments/ipn">
                    @error('ipn_callback_url')<span class="field-error">{{ $message }}</span>@enderror
                    <small>Must be publicly reachable for sandbox and live IPN.</small>
                </div>
                <div class="ocaps-field">
                    <label for="success_url">Success URL (optional)</label>
                    <input type="url" id="success_url" name="success_url" value="{{ old('success_url', $s['success_url']) }}" placeholder="Leave blank for deposit return">
                    @error('success_url')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="ocaps-field">
                    <label for="cancel_url">Cancel URL (optional)</label>
                    <input type="url" id="cancel_url" name="cancel_url" value="{{ old('cancel_url', $s['cancel_url']) }}" placeholder="Leave blank for deposit page">
                    @error('cancel_url')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <div class="ocaps-actions">
            <button type="submit" class="ocaps-save">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Payment Settings
            </button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/payment-settings-admin.css') }}?v={{ @filemtime(public_path('css/payment-settings-admin.css')) ?: time() }}">
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const wirePaymentForm = (form) => {
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
    };

    wirePaymentForm(document.getElementById('payment-settings-form'));
});
</script>
@endpush

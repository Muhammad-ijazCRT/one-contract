<div
    class="two-factor-section"
    @unless($user->hasTwoFactorConnected())
        data-two-factor-setup
        data-setup-url="{{ route($twoFactorSetupRoute ?? 'user.security.two-factor.setup') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-open-on-load="{{ ($errors->has('two_factor_code') && ($twoFactorQrSvg ?? false)) ? 'true' : 'false' }}"
    @endunless
>
    <div class="two-factor-header">
        <div class="two-factor-header-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 5v6c0 5.25 3.4 9.74 8 11 4.6-1.26 8-5.75 8-11V5l-8-3z"/><path d="M9 12l2 2 4-4"/></svg>
        </div>
        <div>
            <h4>Two-Factor Authentication</h4>
            <p>Add an extra layer of security to your account. When enabled, you will need a code from your authenticator app after entering your email and password.</p>
        </div>
    </div>

    @error('two_factor')
        <div class="dash-alert dash-alert-error">{{ $message }}</div>
    @enderror

    @if ($user->hasTwoFactorConnected())
        <div class="two-factor-status-card {{ $user->two_factor_enabled ? 'is-enabled' : 'is-disabled' }}">
            <div class="two-factor-status-icon" aria-hidden="true">
                @if ($user->two_factor_enabled)
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                @endif
            </div>
            <div class="two-factor-status-copy">
                @if ($user->two_factor_enabled)
                    <strong>Connected &amp; Enabled</strong>
                    <span>Login requires your authenticator code after email and password.</span>
                @else
                    <strong>Connected &amp; Disabled</strong>
                    <span>Your authenticator is linked, but login will not ask for a code until you enable it.</span>
                @endif
            </div>
            <span class="auth-status-badge {{ $user->two_factor_enabled ? 'auth-status-badge-success' : 'auth-status-badge-muted' }}">
                {{ $user->two_factor_enabled ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <div class="two-factor-card">
            <div class="two-factor-card-head">
                <h5>Login Protection</h5>
                <p>Turn two-factor authentication on or off for email and password sign in.</p>
            </div>

            <form method="POST" action="{{ route($twoFactorToggleRoute ?? 'user.security.two-factor.toggle') }}" class="profile-form two-factor-toggle-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="enabled" value="0">

                <label class="two-factor-switch">
                    <span class="two-factor-switch-copy">
                        <strong>Require 2FA on login</strong>
                        <span>Use your authenticator app every time you sign in.</span>
                    </span>
                    <span class="two-factor-switch-control">
                        <input
                            type="checkbox"
                            name="enabled"
                            value="1"
                            {{ $user->two_factor_enabled ? 'checked' : '' }}
                        >
                        <span class="two-factor-switch-slider" aria-hidden="true"></span>
                    </span>
                </label>

                <div class="profile-field">
                    <label for="two_factor_toggle_code">Authenticator Code</label>
                    <input
                        type="text"
                        id="two_factor_toggle_code"
                        name="code"
                        class="two-factor-code-input"
                        required
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        placeholder="000000"
                        autocomplete="one-time-code"
                    >
                    <p class="profile-field-hint">Enter the current 6-digit code from your authenticator app.</p>
                    @error('two_factor_code')
                        <p class="profile-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="profile-actions">
                    <button type="submit" class="btn btn-gold">Save Setting</button>
                </div>
            </form>
        </div>

        <div class="two-factor-danger-card">
            <div class="two-factor-danger-head">
                <div class="two-factor-danger-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                </div>
                <div>
                    <h5>Remove Authenticator</h5>
                    <p>To disconnect 2FA, enter your account password and a current authenticator code, then click disconnect.</p>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route($twoFactorDisconnectRoute ?? 'user.security.two-factor.disconnect') }}"
                class="profile-form two-factor-disconnect-form"
                data-confirm="Are you sure you want to disconnect two-factor authentication? Your account will no longer require an authenticator code at login."
                data-confirm-title="Disconnect Two-Factor Authentication"
                data-confirm-ok="Yes, Disconnect"
                data-confirm-variant="danger"
            >
                @csrf
                @method('DELETE')

                <div class="two-factor-disconnect-grid">
                    <div class="profile-field">
                        <label for="two_factor_disconnect_password">Current Password</label>
                        <div class="password-field">
                            <input type="password" id="two_factor_disconnect_password" name="password" required autocomplete="current-password" placeholder="Enter current password">
                            <button type="button" class="password-toggle-btn" aria-label="Show password" data-password-toggle>
                                <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M1 1l22 22"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/></svg>
                            </button>
                        </div>
                        @error('two_factor_password')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="profile-field">
                        <label for="two_factor_disconnect_code">Authenticator Code</label>
                        <input
                            type="text"
                            id="two_factor_disconnect_code"
                            name="code"
                            class="two-factor-code-input"
                            required
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            placeholder="000000"
                            autocomplete="one-time-code"
                        >
                    </div>
                </div>

                @error('two_factor_code')
                    @if ($errors->has('two_factor_password') === false)
                        <p class="profile-field-error two-factor-disconnect-error">{{ $message }}</p>
                    @endif
                @enderror

                <div class="profile-actions">
                    <button type="submit" class="btn btn-outline-danger two-factor-disconnect-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        Disconnect Authenticator
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="two-factor-status-card is-pending">
            <div class="two-factor-status-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div class="two-factor-status-copy">
                <strong>Not connected</strong>
                <span>Scan a QR code with Google Authenticator, Authy, or another TOTP app.</span>
            </div>
            <span class="auth-status-badge auth-status-badge-muted">Setup required</span>
        </div>

        <div class="confirm-modal-backdrop two-factor-modal-backdrop" id="two-factor-setup-modal" role="presentation" hidden>
            <div class="confirm-modal two-factor-setup-modal" role="dialog" aria-modal="true" aria-labelledby="two-factor-setup-title">
                <button type="button" class="modal-close-btn two-factor-modal-close" aria-label="Close">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>

                <div class="two-factor-setup">
                    <div class="two-factor-setup-steps">
                        <div class="two-factor-step">
                            <span class="two-factor-step-num">1</span>
                            <span>Scan this QR code with your authenticator app</span>
                        </div>
                        <div class="two-factor-step">
                            <span class="two-factor-step-num">2</span>
                            <span>Enter the 6-digit code to connect</span>
                        </div>
                    </div>

                    <div class="two-factor-qr" id="two-factor-qr-container" aria-hidden="true">
                        @if ($twoFactorQrSvg ?? false)
                            {!! $twoFactorQrSvg !!}
                        @endif
                    </div>

                    <p class="auth-method-note two-factor-manual-key-wrap" id="two-factor-manual-key-wrap" @unless($twoFactorSetupSecret ?? false) hidden @endunless>
                        Manual setup key: <code id="two-factor-manual-key">{{ $twoFactorSetupSecret ?? '' }}</code>
                    </p>

                    <form method="POST" action="{{ route($twoFactorConfirmRoute ?? 'user.security.two-factor.confirm') }}" class="profile-form two-factor-confirm-form">
                        @csrf

                        <div class="profile-field">
                            <label for="two_factor_confirm_code">Enter 6-digit code</label>
                            <input
                                type="text"
                                id="two_factor_confirm_code"
                                name="code"
                                class="two-factor-code-input"
                                required
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="6"
                                placeholder="000000"
                                autocomplete="one-time-code"
                            >
                            @error('two_factor_code')
                                <p class="profile-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="profile-actions two-factor-setup-actions">
                            <button type="submit" class="btn btn-gold">Connect Authenticator</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="two-factor-card two-factor-start-card">
            <p class="auth-method-note" id="two-factor-start-note">
                @if ($twoFactorQrSvg ?? false)
                    Scan the QR code with your authenticator app, then enter the code to connect.
                @else
                    Generate a QR code to link your authenticator app with this account.
                @endif
            </p>
            <div class="profile-actions">
                <button
                    type="button"
                    class="btn btn-gold"
                    id="two-factor-generate-btn"
                    @if($twoFactorQrSvg ?? false) hidden @endif
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/></svg>
                    Generate QR Code
                </button>
                <button
                    type="button"
                    class="btn btn-gold"
                    id="two-factor-setup-open"
                    @unless($twoFactorQrSvg ?? false) hidden @endunless
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/></svg>
                    Open QR Setup
                </button>
            </div>
        </div>
    @endif
</div>

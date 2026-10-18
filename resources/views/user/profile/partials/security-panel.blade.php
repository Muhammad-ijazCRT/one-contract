@php
    $showStandardAuthPanel = ! $user->usesGoogleAuth()
        || ($twoFactorQrSvg ?? false)
        || $errors->has('two_factor')
        || $errors->has('two_factor_code')
        || $errors->has('two_factor_password')
        || $user->hasTwoFactorConnected();
@endphp

<section class="ocpf-sec-hero ocpf-card">
    <div class="ocpf-sec-hero-copy">
        <h1><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Security</h1>
        <p>Manage how you sign in, update your password, and protect your account with two-factor authentication.</p>
    </div>
    <div class="ocpf-sec-hero-badges">
        <span class="ocpf-pill {{ $user->hasGoogleLinked() ? 'is-active' : 'is-warn' }}">
            Google {{ $user->hasGoogleLinked() ? 'Connected' : 'Not linked' }}
        </span>
        <span class="ocpf-pill {{ $user->hasTwoFactorEnabled() ? 'is-active' : 'is-warn' }}">
            2FA {{ $user->hasTwoFactorEnabled() ? 'On' : 'Off' }}
        </span>
    </div>
</section>

<div class="ocpf-sec security-auth">
    @error('google')
        <div class="dash-alert dash-alert-error">{{ $message }}</div>
    @enderror

    <div class="ocpf-card ocpf-sec-methods">
        <div class="ocpf-card-head">
            <h2><i class="fa-solid fa-key" aria-hidden="true"></i> Sign-in Methods</h2>
        </div>
        <p class="ocpf-sub ocpf-sec-intro">Choose how you sign in. You can use Google authentication or standard email and password.</p>

        <div class="auth-method-options ocpf-auth-options" role="radiogroup" aria-label="Authentication method">
            <button
                type="button"
                class="auth-method-card ocpf-auth-card {{ ! $showStandardAuthPanel ? 'is-active' : '' }}"
                data-auth-method="google"
                role="radio"
                aria-checked="{{ ! $showStandardAuthPanel ? 'true' : 'false' }}"
            >
                <span class="auth-method-icon auth-method-icon-google" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                </span>
                <span class="auth-method-copy">
                    <strong>Google Authentication</strong>
                    <span>Sign in quickly with your Google account.</span>
                </span>
            </button>

            <button
                type="button"
                class="auth-method-card ocpf-auth-card {{ $showStandardAuthPanel ? 'is-active' : '' }}"
                data-auth-method="standard"
                role="radio"
                aria-checked="{{ $showStandardAuthPanel ? 'true' : 'false' }}"
            >
                <span class="auth-method-icon auth-method-icon-standard" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <span class="auth-method-copy">
                    <strong>Standard Authentication</strong>
                    <span>Use your email address and password to sign in.</span>
                </span>
            </button>
        </div>
    </div>

    <div class="auth-method-panel ocpf-card {{ ! $showStandardAuthPanel ? 'is-active' : '' }}" data-auth-panel="google">
        <div class="auth-method-panel-header">
            <h4>Google Authentication</h4>
            <p>Connect your Google account for secure, one-click sign in. The Google email must match your account email ({{ $user->email }}).</p>
        </div>
        <div class="auth-google-status">
            @if ($user->hasGoogleLinked())
                <span class="auth-status-badge auth-status-badge-success">Connected</span>
                <p class="auth-method-note">Your Google account is linked. You can sign in with Google from the login page.</p>
            @else
                <span class="auth-status-badge auth-status-badge-muted">Not connected</span>
                <p class="auth-method-note">Your account is currently using standard email and password sign in.</p>
            @endif
        </div>
        <div class="profile-actions ocpf-form-actions">
            @if ($user->hasGoogleLinked())
                <form method="POST" action="{{ route($googleDisconnectRoute ?? 'user.security.google.disconnect') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ocpf-btn-outline auth-google-btn auth-google-btn-danger">Disconnect Google Account</button>
                </form>
            @elseif ($googleAuthConfigured ?? false)
                <a href="{{ route($googleConnectRoute ?? 'user.security.google.connect') }}" class="ocpf-btn-gold auth-google-btn">
                    Connect Google Account
                </a>
            @elseif ($googleAuthSetupHint ?? false)
                <p class="auth-method-note auth-method-note-warning">Google sign in is not configured locally. Add <code>GOOGLE_CLIENT_ID</code> and <code>GOOGLE_CLIENT_SECRET</code> to your <code>.env</code> file.</p>
            @endif
        </div>
    </div>

    <div class="auth-method-panel ocpf-card {{ $showStandardAuthPanel ? 'is-active' : '' }}" data-auth-panel="standard">
        <div class="auth-method-panel-header">
            <h4>Standard Authentication</h4>
            <p>Update your password to keep your account secure. Use a strong password with at least 8 characters.</p>
        </div>

        <form method="POST" action="{{ route($securityPasswordRoute ?? 'user.security.password') }}" class="profile-form security-form ocpf-form">
            @csrf
            @method('PUT')

            <div class="ocpf-form-grid">
                <div class="ocpf-field profile-field">
                    <label for="current_password">Current Password</label>
                    <div class="password-field">
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password" placeholder="Enter current password">
                        <button type="button" class="password-toggle-btn" aria-label="Show password" data-password-toggle>
                            <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M1 1l22 22"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/></svg>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="ocpf-error profile-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ocpf-field profile-field">
                    <label for="password">New Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="Enter new password">
                        <button type="button" class="password-toggle-btn" aria-label="Show password" data-password-toggle>
                            <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M1 1l22 22"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="ocpf-error profile-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ocpf-field profile-field">
                    <label for="password_confirmation">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm new password">
                        <button type="button" class="password-toggle-btn" aria-label="Show password" data-password-toggle>
                            <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M1 1l22 22"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="ocpf-form-actions profile-actions">
                <button type="submit" class="ocpf-btn-gold">Update Password</button>
                <a href="{{ route($dashboardRoute ?? 'user.dashboard') }}" class="ocpf-btn-outline">Cancel</a>
            </div>
        </form>

        <div class="ocpf-sec-2fa">
            @include('user.profile.partials.two-factor')
        </div>
    </div>
</div>

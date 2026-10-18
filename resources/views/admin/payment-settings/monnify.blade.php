@php($m = $monnify)
@php($monnifyMode = old('mode', $m['mode']))
<section class="ocaps-card ocaps-card--monnify">
    <div class="ocaps-card-head">
        <div class="ocaps-card-icon ocaps-card-icon--gold"><i class="fa-solid fa-naira-sign"></i></div>
        <div>
            <h3>Monnify (Naira)</h3>
            <p>Sandbox/live API keys, contract code, and payout wallet for NGN deposits &amp; withdrawals.</p>
        </div>
        <span class="ocaps-hero-badge ocaps-hero-badge--{{ $m['mode'] === 'live' ? 'live' : 'sandbox' }}">
            {{ strtoupper($m['mode']) }}
        </span>
    </div>

    <form method="POST" action="{{ route('admin.payment-settings.monnify') }}" class="ocaps-form" id="monnify-settings-form">
        @csrf
        @method('PUT')

        <div class="ocaps-grid ocaps-grid--checks">
            <label class="ocaps-check">
                <input type="checkbox" name="enabled" value="1" {{ old('enabled', $m['enabled']) ? 'checked' : '' }}>
                <span>Enable Monnify Naira payments</span>
            </label>
            <label class="ocaps-check">
                <input type="checkbox" name="deposits_enabled" value="1" {{ old('deposits_enabled', $m['deposits_enabled']) ? 'checked' : '' }}>
                <span>Enable NGN deposits</span>
            </label>
            <label class="ocaps-check">
                <input type="checkbox" name="withdrawals_enabled" value="1" {{ old('withdrawals_enabled', $m['withdrawals_enabled']) ? 'checked' : '' }}>
                <span>Enable NGN withdrawals</span>
            </label>
        </div>

        <div class="ocaps-modes">
            <label class="ocaps-mode ocaps-mode--sandbox {{ $monnifyMode === 'sandbox' ? 'is-active--sandbox' : '' }}">
                <input type="radio" name="mode" value="sandbox" {{ $monnifyMode === 'sandbox' ? 'checked' : '' }} required>
                <div class="ocaps-mode-top">
                    <strong><i class="fa-solid fa-flask"></i> Sandbox</strong>
                    <span class="ocaps-mode-dot" aria-hidden="true"></span>
                </div>
                <small>Test NGN payments via <code>sandbox.monnify.com</code></small>
            </label>
            <label class="ocaps-mode ocaps-mode--live {{ $monnifyMode === 'live' ? 'is-active--live' : '' }}">
                <input type="radio" name="mode" value="live" {{ $monnifyMode === 'live' ? 'checked' : '' }}>
                <div class="ocaps-mode-top">
                    <strong><i class="fa-solid fa-bolt"></i> Live</strong>
                    <span class="ocaps-mode-dot" aria-hidden="true"></span>
                </div>
                <small>Real NGN payments via <code>api.monnify.com</code></small>
            </label>
        </div>

        <div class="ocaps-grid" style="margin-top: 1rem;">
            <div class="ocaps-field">
                <label for="monnify_sandbox_api_key">Sandbox API Key</label>
                <input type="text" id="monnify_sandbox_api_key" name="sandbox_api_key" value="{{ old('sandbox_api_key', $m['sandbox_api_key']) }}" autocomplete="off">
            </div>
            <div class="ocaps-field">
                <label for="monnify_sandbox_secret_key">Sandbox Secret Key</label>
                <div class="ocaps-secret">
                    <input type="password" id="monnify_sandbox_secret_key" name="sandbox_secret_key" value="{{ old('sandbox_secret_key', $m['sandbox_secret_key']) }}" placeholder="Sandbox secret key" autocomplete="off">
                    <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show secret key">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                        <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="ocaps-field">
                <label for="monnify_sandbox_base_url">Sandbox Base URL</label>
                <input type="url" id="monnify_sandbox_base_url" name="sandbox_base_url" value="{{ old('sandbox_base_url', $m['sandbox_base_url']) }}" required>
            </div>
            <div class="ocaps-field">
                <label for="monnify_sandbox_contract_code">Sandbox Contract Code</label>
                <input type="text" id="monnify_sandbox_contract_code" name="sandbox_contract_code" value="{{ old('sandbox_contract_code', $m['sandbox_contract_code']) }}" placeholder="From Monnify dashboard">
            </div>
            <div class="ocaps-field ocaps-field--full">
                <label for="monnify_sandbox_wallet_account">Sandbox Wallet Account</label>
                <input type="text" id="monnify_sandbox_wallet_account" name="sandbox_wallet_account" value="{{ old('sandbox_wallet_account', $m['sandbox_wallet_account']) }}" placeholder="Payout wallet account number">
            </div>
        </div>

        <div class="ocaps-grid" style="margin-top: 1rem;">
            <div class="ocaps-field">
                <label for="monnify_live_api_key">Live API Key</label>
                <input type="text" id="monnify_live_api_key" name="live_api_key" value="{{ old('live_api_key', $m['live_api_key']) }}" autocomplete="off">
            </div>
            <div class="ocaps-field">
                <label for="monnify_live_secret_key">Live Secret Key</label>
                <div class="ocaps-secret">
                    <input type="password" id="monnify_live_secret_key" name="live_secret_key" value="{{ old('live_secret_key', $m['live_secret_key']) }}" placeholder="Live secret key" autocomplete="off">
                    <button type="button" class="ocaps-eye" data-toggle-secret aria-label="Show secret key">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                        <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="ocaps-field">
                <label for="monnify_live_base_url">Live Base URL</label>
                <input type="url" id="monnify_live_base_url" name="live_base_url" value="{{ old('live_base_url', $m['live_base_url']) }}" required>
            </div>
            <div class="ocaps-field">
                <label for="monnify_live_contract_code">Live Contract Code</label>
                <input type="text" id="monnify_live_contract_code" name="live_contract_code" value="{{ old('live_contract_code', $m['live_contract_code']) }}" placeholder="From Monnify dashboard">
            </div>
            <div class="ocaps-field ocaps-field--full">
                <label for="monnify_live_wallet_account">Live Wallet Account</label>
                <input type="text" id="monnify_live_wallet_account" name="live_wallet_account" value="{{ old('live_wallet_account', $m['live_wallet_account']) }}" placeholder="Payout wallet account number">
            </div>
        </div>

        <div class="ocaps-grid" style="margin-top: 1rem;">
            <div class="ocaps-field">
                <label for="monnify_redirect_url">Redirect URL</label>
                <input type="url" id="monnify_redirect_url" name="redirect_url" value="{{ old('redirect_url', $m['redirect_url']) }}" placeholder="{{ url('/monnify/callback') }}">
                <small>User returns here after Monnify checkout.</small>
            </div>
            <div class="ocaps-field">
                <label for="monnify_webhook_url">Webhook URL</label>
                <input type="url" id="monnify_webhook_url" name="webhook_url" value="{{ old('webhook_url', $m['webhook_url']) }}" placeholder="{{ url('/monnify/webhook') }}">
                <small>Must be publicly reachable for payment confirmation.</small>
            </div>
        </div>

        <p class="ocaps-field" style="margin-top: 0.75rem;">
            <small>
                API key: <strong>{{ $m['active_api_key_set'] ? 'Saved' : 'Missing' }}</strong> ·
                Secret: <strong>{{ $m['active_secret_set'] ? 'Saved' : 'Missing' }}</strong> ·
                Contract: <strong>{{ $m['active_contract_set'] ? 'Saved' : 'Missing' }}</strong> ·
                Wallet: <strong>{{ $m['active_wallet_set'] ? 'Saved' : 'Missing' }}</strong>
                <br>
                Active base: <code>{{ $m['active_base_url'] }}</code> ·
                Deposits ready: <strong>{{ $m['is_deposit_ready'] ? 'Yes' : 'No' }}</strong>
            </small>
        </p>

        <div class="ocaps-actions" style="position: static; margin-top: 1rem; box-shadow: none;">
            <button type="submit" class="ocaps-save">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Monnify Settings
            </button>
        </div>
    </form>
</section>

@extends('layouts.admin')

@section('title', 'Settings — Admin')
@section('page_title', 'Platform Settings')

@section('content')
@php
    $platformSection = 'rewards';
    if ($errors->hasAny(['referral_level_1', 'referral_level_2', 'referral_level_3'])) {
        $platformSection = 'referrals';
    } elseif ($errors->hasAny(['min_deposit', 'min_reward_withdrawal', 'withdrawal_fee_rate', 'platform_wallet_network', 'platform_wallet_address'])) {
        $platformSection = 'deposits';
    } elseif ($errors->hasAny([
        'club_capital_return_hours',
        'savings_early_return_hours',
        'savings_min_daily',
        'savings_max_daily',
        'savings_target_days',
        'savings_reward_percent',
    ])) {
        $platformSection = 'dual-wallet';
    }
@endphp

<div class="dash-page settings-page">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <div class="panel settings-panel">
        <div class="settings-tab-intro">
            <div>
                <h3>Platform Configuration</h3>
                <p class="panel-sub">Manage reward rates, referral commissions, and deposit settings for the entire platform.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="dash-form admin-settings-form" id="platform-settings-form">
            @csrf
            @method('PUT')

            <ul class="settings-subtabs" role="tablist">
                <li class="settings-subtabs-item" role="presentation">
                    <button type="button"
                            class="settings-subtab {{ $platformSection === 'rewards' ? 'active' : '' }}"
                            data-tab-target="rewards"
                            role="tab"
                            aria-selected="{{ $platformSection === 'rewards' ? 'true' : 'false' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Daily Rewards
                    </button>
                </li>
                <li class="settings-subtabs-item" role="presentation">
                    <button type="button"
                            class="settings-subtab {{ $platformSection === 'referrals' ? 'active' : '' }}"
                            data-tab-target="referrals"
                            role="tab"
                            aria-selected="{{ $platformSection === 'referrals' ? 'true' : 'false' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Referral Commissions
                    </button>
                </li>
                <li class="settings-subtabs-item" role="presentation">
                    <button type="button"
                            class="settings-subtab {{ $platformSection === 'deposits' ? 'active' : '' }}"
                            data-tab-target="deposits"
                            role="tab"
                            aria-selected="{{ $platformSection === 'deposits' ? 'true' : 'false' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        Deposits &amp; Wallet
                    </button>
                </li>
                <li class="settings-subtabs-item" role="presentation">
                    <button type="button"
                            class="settings-subtab {{ $platformSection === 'dual-wallet' ? 'active' : '' }}"
                            data-tab-target="dual-wallet"
                            role="tab"
                            aria-selected="{{ $platformSection === 'dual-wallet' ? 'true' : 'false' }}">
                        <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                        Schemes &amp; Savings
                    </button>
                </li>
            </ul>

            <div class="settings-subtab-content">
                <div class="tab-pane {{ $platformSection === 'rewards' ? 'active' : '' }}" data-tab-pane="rewards" role="tabpanel">
                    <div class="settings-section-card">
                        <div class="settings-section-head">
                            <span class="settings-section-icon settings-section-icon--gold" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </span>
                            <div>
                                <h4>Daily Rewards</h4>
                                <p>Set the daily earning rate and how often users can withdraw rewards.</p>
                            </div>
                        </div>
                        <div class="admin-settings-grid">
                            <div class="dash-field">
                                <label for="daily_reward_rate">Daily Reward Rate (decimal)</label>
                                <input type="number" step="0.0001" min="0" max="1" id="daily_reward_rate" name="daily_reward_rate" value="{{ old('daily_reward_rate', $daily_reward_rate) }}" required>
                                <small class="field-hint">0.01 = 1% per day</small>
                                @error('daily_reward_rate')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="withdrawal_interval_days">Withdrawal Interval (days)</label>
                                <input type="number" min="1" max="90" id="withdrawal_interval_days" name="withdrawal_interval_days" value="{{ old('withdrawal_interval_days', $withdrawal_interval_days) }}" required>
                                @error('withdrawal_interval_days')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane {{ $platformSection === 'referrals' ? 'active' : '' }}" data-tab-pane="referrals" role="tabpanel">
                    <div class="settings-section-card">
                        <div class="settings-section-head">
                            <span class="settings-section-icon settings-section-icon--blue" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </span>
                            <div>
                                <h4>Referral Commissions</h4>
                                <p>Configure commission percentages for each referral level.</p>
                            </div>
                        </div>
                        <div class="admin-settings-grid admin-settings-grid-3">
                            <div class="dash-field">
                                <label for="referral_level_1">Level 1 (decimal)</label>
                                <input type="number" step="0.01" min="0" max="1" id="referral_level_1" name="referral_level_1" value="{{ old('referral_level_1', $referral_level_1) }}" required>
                                <small class="field-hint">0.05 = 5%</small>
                                @error('referral_level_1')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="referral_level_2">Level 2 (decimal)</label>
                                <input type="number" step="0.01" min="0" max="1" id="referral_level_2" name="referral_level_2" value="{{ old('referral_level_2', $referral_level_2) }}" required>
                                <small class="field-hint">0.03 = 3%</small>
                                @error('referral_level_2')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="referral_level_3">Level 3 (decimal)</label>
                                <input type="number" step="0.01" min="0" max="1" id="referral_level_3" name="referral_level_3" value="{{ old('referral_level_3', $referral_level_3) }}" required>
                                <small class="field-hint">0.02 = 2%</small>
                                @error('referral_level_3')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane {{ $platformSection === 'deposits' ? 'active' : '' }}" data-tab-pane="deposits" role="tabpanel">
                    <div class="settings-section-card">
                        <div class="settings-section-head">
                            <span class="settings-section-icon settings-section-icon--green" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                            </span>
                            <div>
                                <h4>Deposits &amp; Withdrawals</h4>
                                <p>Control minimum amounts, fees, wallet details, and deposit availability.</p>
                            </div>
                        </div>
                        <div class="admin-settings-grid">
                            <div class="dash-field">
                                <label for="min_deposit">Minimum Deposit (USDT)</label>
                                <input type="number" step="0.01" min="1" id="min_deposit" name="min_deposit" value="{{ old('min_deposit', $min_deposit) }}" required>
                                @error('min_deposit')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="min_reward_withdrawal">Min Reward Withdrawal (USDT)</label>
                                <input type="number" step="0.01" min="0.01" id="min_reward_withdrawal" name="min_reward_withdrawal" value="{{ old('min_reward_withdrawal', $min_reward_withdrawal) }}" required>
                                @error('min_reward_withdrawal')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="withdrawal_fee_rate">Withdrawal Fee Rate (decimal)</label>
                                <input type="number" step="0.0001" min="0" max="1" id="withdrawal_fee_rate" name="withdrawal_fee_rate" value="{{ old('withdrawal_fee_rate', $withdrawal_fee_rate) }}" required>
                                <small class="field-hint">0.005 = 0.5% fee on each withdrawal amount</small>
                                @error('withdrawal_fee_rate')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="platform_wallet_network">Platform Wallet Network / Chain</label>
                                <input type="text" id="platform_wallet_network" name="platform_wallet_network" value="{{ old('platform_wallet_network', $platform_wallet_network) }}" placeholder="TRC20, ERC20, BEP20..." required>
                                <small class="field-hint">Select the blockchain network before saving the wallet address.</small>
                                @error('platform_wallet_network')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field admin-settings-full">
                                <label for="platform_wallet_address">Platform Wallet Address</label>
                                <input type="text" id="platform_wallet_address" name="platform_wallet_address" value="{{ old('platform_wallet_address', $platform_wallet_address) }}" required>
                                <small class="field-hint">Enter the address that matches the selected network or chain.</small>
                                @error('platform_wallet_address')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="settings-section-card settings-section-card--toggles">
                        <div class="settings-section-head">
                            <span class="settings-section-icon settings-section-icon--amber" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 6v6l4 2"/></svg>
                            </span>
                            <div>
                                <h4>Deposit Controls</h4>
                                <p>Toggle automatic approval and platform-wide deposit availability.</p>
                            </div>
                        </div>
                        <div class="settings-toggle-list">
                            <div class="settings-toggle-item">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="auto_approve_deposits" value="1" {{ old('auto_approve_deposits', $auto_approve_deposits) ? 'checked' : '' }}>
                                    <span class="checkbox-mark"></span>
                                    <span class="checkbox-text">
                                        <strong>Auto-approve deposits</strong>
                                        <small>Automatically confirm incoming deposits without manual review.</small>
                                    </span>
                                </label>
                            </div>
                            <div class="settings-toggle-item">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="deposits_enabled" value="1" {{ old('deposits_enabled', $deposits_enabled) ? 'checked' : '' }}>
                                    <span class="checkbox-mark"></span>
                                    <span class="checkbox-text">
                                        <strong>Enable deposits platform-wide</strong>
                                        <small>Turn off to temporarily stop new user deposits without affecting other site features.</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane {{ $platformSection === 'dual-wallet' ? 'active' : '' }}" data-tab-pane="dual-wallet" role="tabpanel">
                    <div class="settings-section-card">
                        <div class="settings-section-head">
                            <span class="settings-section-icon settings-section-icon--gold" aria-hidden="true">
                                <i class="fa-solid fa-layer-group"></i>
                            </span>
                            <div>
                                <h4>Schemes &amp; Savings</h4>
                                <p>Configure capital return timers and savings plan limits.</p>
                            </div>
                        </div>
                        <div class="admin-settings-grid">
                            <div class="dash-field">
                                <label for="club_capital_return_hours">Scheme Capital Return (hours)</label>
                                <input type="number" min="1" max="720" id="club_capital_return_hours" name="club_capital_return_hours" value="{{ old('club_capital_return_hours', $club_capital_return_hours) }}" required>
                                <small class="field-hint">Default 72 hours before reduced capital returns to Main Wallet</small>
                                @error('club_capital_return_hours')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="savings_early_return_hours">Savings Early Return (hours)</label>
                                <input type="number" min="1" max="720" id="savings_early_return_hours" name="savings_early_return_hours" value="{{ old('savings_early_return_hours', $savings_early_return_hours) }}" required>
                                @error('savings_early_return_hours')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="savings_min_daily">Savings Min Daily (USDT)</label>
                                <input type="number" step="0.01" min="0.01" id="savings_min_daily" name="savings_min_daily" value="{{ old('savings_min_daily', $savings_min_daily) }}" required>
                                @error('savings_min_daily')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="savings_max_daily">Savings Max Daily (USDT)</label>
                                <input type="number" step="0.01" min="0.01" id="savings_max_daily" name="savings_max_daily" value="{{ old('savings_max_daily', $savings_max_daily) }}" required>
                                @error('savings_max_daily')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="savings_target_days">Savings Target Days</label>
                                <input type="number" min="1" max="3650" id="savings_target_days" name="savings_target_days" value="{{ old('savings_target_days', $savings_target_days) }}" required>
                                @error('savings_target_days')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="savings_reward_percent">Savings Reward Percent (%)</label>
                                <input type="number" step="0.1" min="0" max="100" id="savings_reward_percent" name="savings_reward_percent" value="{{ old('savings_reward_percent', $savings_reward_percent) }}" required>
                                <small class="field-hint">Flat % of total saved at maturity — claim via Claim Rewards</small>
                                @error('savings_reward_percent')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="platform_fee_rate">Platform Fee Rate (decimal)</label>
                                <input type="number" step="0.01" min="0" max="1" id="platform_fee_rate" name="platform_fee_rate" value="{{ old('platform_fee_rate', $platform_fee_rate) }}" required>
                                <small class="field-hint">0.10 = 10% paid from Main Wallet when claiming rewards</small>
                                @error('platform_fee_rate')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="savings_daily_rate_percent">Savings Daily Rate %</label>
                                <input type="number" step="0.001" min="0" max="100" id="savings_daily_rate_percent" name="savings_daily_rate_percent" value="{{ old('savings_daily_rate_percent', $savings_daily_rate_percent) }}" required>
                                @error('savings_daily_rate_percent')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="dash-field">
                                <label for="savings_min_principal">Savings Min Principal</label>
                                <input type="number" step="0.01" min="0.01" id="savings_min_principal" name="savings_min_principal" value="{{ old('savings_min_principal', $savings_min_principal) }}" required>
                            </div>
                            <div class="dash-field">
                                <label for="savings_max_principal">Savings Max Principal</label>
                                <input type="number" step="0.01" min="0.01" id="savings_max_principal" name="savings_max_principal" value="{{ old('savings_max_principal', $savings_max_principal) }}" required>
                            </div>
                            <div class="dash-field">
                                <label for="regular_lending_daily_rate_percent">Regular Lending Flat Reward %</label>
                                <input type="number" step="0.001" min="0" id="regular_lending_daily_rate_percent" name="regular_lending_daily_rate_percent" value="{{ old('regular_lending_daily_rate_percent', $regular_lending_daily_rate_percent) }}" required>
                            </div>
                            <div class="dash-field">
                                <label for="regular_lending_duration_days">Regular Lending Days</label>
                                <input type="number" min="1" id="regular_lending_duration_days" name="regular_lending_duration_days" value="{{ old('regular_lending_duration_days', $regular_lending_duration_days) }}" required>
                            </div>
                            <div class="dash-field">
                                <label for="regular_lending_min">Regular Lending Min</label>
                                <input type="number" step="0.01" id="regular_lending_min" name="regular_lending_min" value="{{ old('regular_lending_min', $regular_lending_min) }}" required>
                            </div>
                            <div class="dash-field">
                                <label for="regular_lending_max">Regular Lending Max</label>
                                <input type="number" step="0.01" id="regular_lending_max" name="regular_lending_max" value="{{ old('regular_lending_max', $regular_lending_max) }}" required>
                            </div>
                            <div class="dash-field">
                                <label for="premium_lending_daily_rate_percent">Premium Lending Flat Reward %</label>
                                <input type="number" step="0.001" min="0" id="premium_lending_daily_rate_percent" name="premium_lending_daily_rate_percent" value="{{ old('premium_lending_daily_rate_percent', $premium_lending_daily_rate_percent) }}" required>
                            </div>
                            <div class="dash-field">
                                <label for="premium_lending_duration_days">Premium Lending Days</label>
                                <input type="number" min="1" id="premium_lending_duration_days" name="premium_lending_duration_days" value="{{ old('premium_lending_duration_days', $premium_lending_duration_days) }}" required>
                            </div>
                            <div class="dash-field">
                                <label for="premium_lending_min">Premium Lending Min</label>
                                <input type="number" step="0.01" id="premium_lending_min" name="premium_lending_min" value="{{ old('premium_lending_min', $premium_lending_min) }}" required>
                            </div>
                            <div class="dash-field">
                                <label for="premium_lending_max">Premium Lending Max</label>
                                <input type="number" step="0.01" id="premium_lending_max" name="premium_lending_max" value="{{ old('premium_lending_max', $premium_lending_max) }}" required>
                            </div>
                            <div class="dash-field">
                                <label for="lending_early_return_hours">Lending Early Return (hours)</label>
                                <input type="number" min="1" max="720" id="lending_early_return_hours" name="lending_early_return_hours" value="{{ old('lending_early_return_hours', $lending_early_return_hours) }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-save-bar">
                <p class="settings-save-hint">Changes apply to all users immediately after saving.</p>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

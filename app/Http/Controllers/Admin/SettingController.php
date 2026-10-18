<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminLogService;
use App\Services\PlatformSettings;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(): View
    {
        return view('admin.settings.index', [
            'daily_reward_rate' => PlatformSettings::dailyRewardRate(),
            'withdrawal_interval_days' => PlatformSettings::withdrawalIntervalDays(),
            'referral_level_1' => PlatformSettings::referralRate(1),
            'referral_level_2' => PlatformSettings::referralRate(2),
            'referral_level_3' => PlatformSettings::referralRate(3),
            'min_deposit' => PlatformSettings::minDeposit(),
            'min_reward_withdrawal' => PlatformSettings::minRewardWithdrawal(),
            'withdrawal_fee_rate' => PlatformSettings::withdrawalFeeRate(),
            'platform_wallet_network' => PlatformSettings::platformWalletNetwork(),
            'platform_wallet_address' => PlatformSettings::platformWalletAddress(),
            'auto_approve_deposits' => PlatformSettings::autoApproveDeposits(),
            'deposits_enabled' => PlatformSettings::depositsEnabled(),
            'club_capital_return_hours' => PlatformSettings::clubCapitalReturnHours(),
            'savings_early_return_hours' => PlatformSettings::savingsEarlyReturnHours(),
            'savings_min_daily' => PlatformSettings::savingsMinDaily(),
            'savings_max_daily' => PlatformSettings::savingsMaxDaily(),
            'savings_target_days' => PlatformSettings::savingsTargetDays(),
            'savings_reward_percent' => PlatformSettings::savingsRewardPercent(),
            'platform_fee_rate' => PlatformSettings::platformFeeRate(),
            'savings_daily_rate_percent' => PlatformSettings::savingsDailyRatePercent(),
            'savings_min_principal' => PlatformSettings::savingsMinPrincipal(),
            'savings_max_principal' => PlatformSettings::savingsMaxPrincipal(),
            'regular_lending_daily_rate_percent' => PlatformSettings::regularLendingDailyRatePercent(),
            'regular_lending_duration_days' => PlatformSettings::regularLendingDurationDays(),
            'regular_lending_min' => PlatformSettings::regularLendingMin(),
            'regular_lending_max' => PlatformSettings::regularLendingMax(),
            'premium_lending_daily_rate_percent' => PlatformSettings::premiumLendingDailyRatePercent(),
            'premium_lending_duration_days' => PlatformSettings::premiumLendingDurationDays(),
            'premium_lending_min' => PlatformSettings::premiumLendingMin(),
            'premium_lending_max' => PlatformSettings::premiumLendingMax(),
            'lending_early_return_hours' => PlatformSettings::lendingEarlyReturnHours(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'daily_reward_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'withdrawal_interval_days' => ['required', 'integer', 'min:1', 'max:90'],
            'referral_level_1' => ['required', 'numeric', 'min:0', 'max:1'],
            'referral_level_2' => ['required', 'numeric', 'min:0', 'max:1'],
            'referral_level_3' => ['required', 'numeric', 'min:0', 'max:1'],
            'min_deposit' => ['required', 'numeric', 'min:1'],
            'min_reward_withdrawal' => ['required', 'numeric', 'min:0.01'],
            'withdrawal_fee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'platform_wallet_network' => ['required', 'string', 'max:60'],
            'platform_wallet_address' => ['required', 'string', 'max:255'],
            'auto_approve_deposits' => ['nullable', 'boolean'],
            'deposits_enabled' => ['nullable', 'boolean'],
            'club_capital_return_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'savings_early_return_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'savings_min_daily' => ['required', 'numeric', 'min:0.01'],
            'savings_max_daily' => ['required', 'numeric', 'min:0.01'],
            'savings_target_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'savings_reward_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'platform_fee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'savings_daily_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'savings_min_principal' => ['required', 'numeric', 'min:0.01'],
            'savings_max_principal' => ['required', 'numeric', 'min:0.01'],
            'regular_lending_daily_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'regular_lending_duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'regular_lending_min' => ['required', 'numeric', 'min:0.01'],
            'regular_lending_max' => ['required', 'numeric', 'min:0.01'],
            'premium_lending_daily_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'premium_lending_duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'premium_lending_min' => ['required', 'numeric', 'min:0.01'],
            'premium_lending_max' => ['required', 'numeric', 'min:0.01'],
            'lending_early_return_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ]);

        if ($data['savings_max_daily'] < $data['savings_min_daily']) {
            return back()->withErrors(['savings_max_daily' => 'Max daily must be greater than or equal to min daily.'])->withInput();
        }

        $data['auto_approve_deposits'] = $request->boolean('auto_approve_deposits') ? '1' : '0';
        $data['deposits_enabled'] = $request->boolean('deposits_enabled') ? '1' : '0';

        $this->settings->update($data);

        $this->adminLog->log(
            auth()->user(),
            'settings_updated',
            null,
            'Platform settings updated',
        );

        return back()->with('success', 'Platform settings saved successfully.');
    }
}

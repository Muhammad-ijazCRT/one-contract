<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class PlatformSettings
{
    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("setting.{$key}", 300, function () use ($key, $default) {
            return Setting::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function dailyRewardRate(): float
    {
        return (float) self::get('daily_reward_rate', '0.01');
    }

    public static function withdrawalIntervalDays(): int
    {
        return (int) self::get('withdrawal_interval_days', '7');
    }

    public static function referralRate(int $level): float
    {
        return (float) self::get("referral_level_{$level}", '0');
    }

    public static function currencySymbol(): string
    {
        return self::get('currency_symbol', '$') ?: '$';
    }

    public static function currencyCode(): string
    {
        return self::get('currency_code', 'USDT') ?: 'USDT';
    }

    public static function minDeposit(): float
    {
        return (float) self::get('min_deposit', '5');
    }

    public static function gatewayMinDeposit(): float
    {
        return max(self::minDeposit(), 5.0);
    }

    public static function minRewardWithdrawal(): float
    {
        return (float) self::get('min_reward_withdrawal', '10');
    }

    public static function withdrawalFeeRate(): float
    {
        return max(0, (float) self::get('withdrawal_fee_rate', '0.005'));
    }

    public static function withdrawalFeePercent(): string
    {
        return rtrim(rtrim(number_format(self::withdrawalFeeRate() * 100, 2), '0'), '.');
    }

    public static function calculateWithdrawalFee(float $amount): float
    {
        $rate = self::withdrawalFeeRate();

        if ($rate <= 0 || $amount <= 0) {
            return 0.0;
        }

        return round($amount * $rate, 8);
    }

    public static function withdrawalNetAmount(float $amount): float
    {
        return round(max(0, $amount - self::calculateWithdrawalFee($amount)), 8);
    }

    public static function platformWalletAddress(): string
    {
        return self::get('platform_wallet_address', '') ?: '';
    }

    public static function platformWalletNetwork(): string
    {
        return self::get('platform_wallet_network', '') ?: '';
    }

    public static function autoApproveDeposits(): bool
    {
        return filter_var(self::get('auto_approve_deposits', '0'), FILTER_VALIDATE_BOOL);
    }

    public static function depositsEnabled(): bool
    {
        return filter_var(self::get('deposits_enabled', '1'), FILTER_VALIDATE_BOOL);
    }

    public static function clubCapitalReturnHours(): int
    {
        return max(1, (int) self::get('club_capital_return_hours', '72'));
    }

    public static function savingsEarlyReturnHours(): int
    {
        return max(1, (int) self::get('savings_early_return_hours', '72'));
    }

    public static function savingsMinDaily(): float
    {
        return (float) self::get('savings_min_daily', '0.10');
    }

    public static function savingsMaxDaily(): float
    {
        return (float) self::get('savings_max_daily', '125');
    }

    public static function savingsTargetDays(): int
    {
        return max(1, (int) self::get('savings_target_days', '360'));
    }

    public static function savingsRewardPercent(): float
    {
        return (float) self::get('savings_reward_percent', '18');
    }

    public static function dailyRewardPercent(): string
    {
        return number_format(self::dailyRewardRate() * 100, 1);
    }

    public static function savingsRewardMultiplier(): float
    {
        return 1 + (self::savingsRewardPercent() / 100);
    }

    public static function platformFeeRate(): float
    {
        return max(0, (float) self::get('platform_fee_rate', '0.10'));
    }

    public static function platformFeePercent(): string
    {
        return rtrim(rtrim(number_format(self::platformFeeRate() * 100, 2), '0'), '.');
    }

    public static function savingsDailyRatePercent(): float
    {
        return (float) self::get('savings_daily_rate_percent', '0.139');
    }

    public static function savingsMinPrincipal(): float
    {
        return (float) self::get('savings_min_principal', (string) self::savingsMinDaily());
    }

    public static function savingsMaxPrincipal(): float
    {
        return (float) self::get('savings_max_principal', (string) self::savingsMaxDaily());
    }

    public static function regularLendingDailyRatePercent(): float
    {
        return (float) self::get('regular_lending_daily_rate_percent', '18');
    }

    public static function regularLendingDurationDays(): int
    {
        return max(1, (int) self::get('regular_lending_duration_days', '365'));
    }

    public static function regularLendingMin(): float
    {
        return (float) self::get('regular_lending_min', '1000');
    }

    public static function regularLendingMax(): float
    {
        return (float) self::get('regular_lending_max', '45000');
    }

    public static function premiumLendingDailyRatePercent(): float
    {
        return (float) self::get('premium_lending_daily_rate_percent', '50');
    }

    public static function premiumLendingDurationDays(): int
    {
        return max(1, (int) self::get('premium_lending_duration_days', '730'));
    }

    public static function premiumLendingMin(): float
    {
        return (float) self::get('premium_lending_min', '1000');
    }

    public static function premiumLendingMax(): float
    {
        return (float) self::get('premium_lending_max', '45000');
    }

    public static function lendingEarlyReturnHours(): int
    {
        return max(1, (int) self::get('lending_early_return_hours', '72'));
    }

    public static function predictionMinBet(): float
    {
        return (float) self::get('prediction_min_bet', '0');
    }

    public static function predictionMaxBet(): float
    {
        return (float) self::get('prediction_max_bet', '1000');
    }

    public static function predictionPayoutMultiplier(): float
    {
        return (float) self::get('prediction_payout_multiplier', '1.92');
    }

    public static function predictionBettingSeconds(): int
    {
        return max(1, (int) self::get('prediction_betting_seconds', '10'));
    }

    public static function predictionSettleSeconds(): int
    {
        return max(1, (int) self::get('prediction_settle_seconds', '6'));
    }

    public static function azuboguMinBet(): float
    {
        return (float) self::get('azubogu_min_bet', '0');
    }

    public static function azuboguMaxBet(): float
    {
        return (float) self::get('azubogu_max_bet', '50000');
    }

    public static function azuboguBettingSeconds(): int
    {
        return max(1, (int) self::get('azubogu_betting_seconds', '20'));
    }

    public static function azuboguSettleSeconds(): int
    {
        return max(1, (int) self::get('azubogu_settle_seconds', '10'));
    }
}

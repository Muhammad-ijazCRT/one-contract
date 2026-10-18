<?php

namespace App\Providers;

use App\Enums\ClubCapitalRequestStatus;
use App\Enums\DepositStatus;
use App\Enums\KycStatus;
use App\Enums\SavingsPlanStatus;
use App\Enums\UserRole;
use App\Enums\WalletCurrency;
use App\Enums\WithdrawalStatus;
use App\Enums\WithdrawalType;
use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Models\ClubCapitalRequest;
use App\Models\ContactSetting;
use App\Models\Deposit;
use App\Models\SavingsPlan;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\MultiWalletService;
use App\Services\NotificationService;
use App\Services\TreasuryStatsService;
use App\Services\WalletLiveRateService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.dash');
        Paginator::defaultSimpleView('vendor.pagination.dash');

        View::composer(['layouts.admin', 'admin.partials.sidebar', 'admin.partials.topbar'], function ($view) {
            $view->with('treasury', app(TreasuryStatsService::class)->getStats());

            $adminNotifications = app(NotificationService::class)->forAdmin();
            $view->with('notifications', $adminNotifications);
            $view->with('notification_count', $adminNotifications['count']);
            $pendingWithdrawals = WithdrawalRequest::query()->where('status', WithdrawalStatus::Pending);
            $withdrawalReward = (clone $pendingWithdrawals)->where('type', WithdrawalType::Reward)->count();
            $withdrawalCapital = (clone $pendingWithdrawals)->where('type', WithdrawalType::Capital)->count();
            $capitalReturns = ClubCapitalRequest::query()->where('status', ClubCapitalRequestStatus::Pending)->count();
            $savingsReturns = SavingsPlan::query()->where('status', SavingsPlanStatus::PendingEarlyReturn)->count();

            $view->with('admin_nav_counts', [
                'users' => User::query()
                    ->where('role', UserRole::User)
                    ->where('kyc_status', KycStatus::Pending)
                    ->count(),
                'deposits' => Deposit::query()->where('status', DepositStatus::Pending)->count(),
                'withdrawals' => $withdrawalReward + $withdrawalCapital,
                'withdrawals_reward' => $withdrawalReward,
                'withdrawals_capital' => $withdrawalCapital,
                'support' => SupportTicket::query()->where('status', SupportTicket::STATUS_OPEN)->count(),
                'capital_returns' => $capitalReturns,
                'savings' => $savingsReturns,
                'dual_wallet' => $capitalReturns + $savingsReturns,
            ]);
        });

        View::composer(['layouts.dashboard', 'user.partials.topbar'], function ($view) {
            $user = Auth::user();
            $userNotifications = $user
                ? app(NotificationService::class)->forUser($user)
                : ['count' => 0, 'items' => collect()];

            $view->with('notifications', $userNotifications);
            $view->with('notification_count', $userNotifications['count']);
            $view->with(
                'chat_unread_count',
                $user ? app(\App\Services\ChatService::class)->unreadCountFor($user) : 0
            );

            if ($user) {
                try {
                    $wallets = app(MultiWalletService::class);
                    $rates = app(WalletLiveRateService::class);
                    $currency = $user->preferredWalletCurrency();
                    $balances = $wallets->balances($user);
                    $totalUsd = $rates->portfolioUsd($balances);

                    $picker = collect(WalletCurrency::pickerOrder())->map(function (WalletCurrency $item) use ($balances, $currency, $rates) {
                        $amount = (float) ($balances[$item->value] ?? 0);

                        return [
                            'code' => $item->value,
                            'label' => $item->label(),
                            'balance' => $amount,
                            'formatted' => $item->format($amount),
                            'usd_formatted' => '$'.number_format($rates->amountToUsd($item, $amount), 2),
                            'accent' => $item->accentColor(),
                            'icon' => $item->iconUrl(),
                            'recommended' => $item === WalletCurrency::Usdt,
                            'selected' => $item === $currency,
                        ];
                    })->values()->all();

                    $balance = (float) ($balances[$currency->value] ?? 0);
                    $usdOfSelected = ($currency === WalletCurrency::Usdt || $currency === WalletCurrency::Usdc)
                        ? $balance
                        : $rates->amountToUsd($currency, $balance);
                    $headerFormatted = '$'.number_format($usdOfSelected, 2);

                    $view->with([
                        'header_wallet_currency' => $currency,
                        'header_wallet_balance' => $balance,
                        'header_wallet_formatted' => $headerFormatted,
                        'header_wallet_total_usd' => $totalUsd,
                        'header_currency_picker' => $picker,
                    ]);
                } catch (\Throwable) {
                    $view->with([
                        'header_wallet_currency' => null,
                        'header_wallet_balance' => 0,
                        'header_wallet_formatted' => '$0.00',
                        'header_currency_picker' => [],
                    ]);
                }
            }
        });

        View::composer('partials.footer', function ($view) {
            $contactSettings = null;

            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('contact_settings')) {
                    $contactSettings = ContactSetting::query()->first();
                }
            } catch (\Throwable) {
                $contactSettings = null;
            }

            $view->with('contactSettings', $contactSettings);
        });

        VerifyEmail::createUrlUsing(function (object $notifiable) {
            return app(\App\Services\EmailVerificationService::class)->verificationUrl($notifiable);
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new VerifyEmailMail(
                name: $notifiable->name,
                url: $url,
            ))->to($notifiable->getEmailForVerification());
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new ResetPasswordMail(
                name: $notifiable->name,
                url: $url,
            ))->to($notifiable->getEmailForPasswordReset());
        });
    }
}

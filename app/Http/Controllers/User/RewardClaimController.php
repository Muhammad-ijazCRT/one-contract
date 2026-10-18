<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\InvestmentClub;
use App\Services\MainWalletService;
use App\Services\PlatformSettings;
use App\Services\RewardClaimService;
use App\Services\UserWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RewardClaimController extends Controller
{
    public function __construct(
        private readonly RewardClaimService $claims,
        private readonly MainWalletService $mainWallet,
        private readonly UserWalletService $wallet,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $categories = $this->claims->dashboardCategories($user);
        $total = $this->claims->unclaimedTotal($user);
        $fee = $this->claims->requiredFee($user);
        $stats = $this->wallet->stats($user);

        $scheme = InvestmentClub::query()->where('is_active', true)->orderBy('sort_order')->first();
        $schemeDaily = $scheme ? (float) $scheme->reward_percent : 0.1;
        $schemeCycle = $scheme ? (int) $scheme->cycle_days : 35;

        $rates = [
            'scheme_daily' => $schemeDaily,
            'scheme_cycle' => $schemeCycle,
            'scheme_payout' => round($schemeDaily * $schemeCycle, 2),
            'savings' => PlatformSettings::savingsRewardPercent(),
            'lending_regular' => PlatformSettings::regularLendingDailyRatePercent(),
            'lending_premium' => PlatformSettings::premiumLendingDailyRatePercent(),
        ];

        $productRewards = (float) ($stats['total_rewards'] ?? 0) + (float) ($stats['gaming_wins'] ?? 0);
        $referralEarnings = (float) ($stats['referral_earnings'] ?? 0);

        return view('user.rewards.claim', [
            'user' => $user,
            'categories' => $categories,
            'rewards' => $this->claims->unclaimedFor($user),
            'total' => $total,
            'fee' => $fee,
            'mainWalletBalance' => $this->mainWallet->balance($user),
            'platformFeePercent' => PlatformSettings::platformFeePercent(),
            'rates' => $rates,
            'chart' => $this->wallet->earningsChart($user, 30),
            'productRewards' => $productRewards,
            'referralEarnings' => $referralEarnings,
            'history' => $user->rewards()
                ->with(['clubInvestment.club', 'deposit'])
                ->whereNotIn('type', RewardClaimService::retiredRewardTypeValues())
                ->latest('credited_at')
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }

    public function claim(Request $request): RedirectResponse
    {
        $result = $this->claims->claimAll($request->user());

        return back()->with(
            'success',
            'Claimed '.number_format($result['reward_total'], 2).' USDT. Platform Fee '
                .number_format($result['platform_fee'], 2).' USDT paid from Main Wallet.'
        );
    }

    public function claimCategory(Request $request, string $category): RedirectResponse
    {
        $result = $this->claims->claimCategory($request->user(), $category);

        return back()->with(
            'success',
            'Claimed '.number_format($result['reward_total'], 2).' USDT from '.$result['label']
                .'. Platform Fee '.number_format($result['platform_fee'], 2).' USDT paid from Main Wallet.'
        );
    }
}

<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\EnrollSavingsPlanRequest;
use App\Models\SavingsPlan;
use App\Services\MainWalletService;
use App\Services\PlatformSettings;
use App\Services\RewardClaimService;
use App\Services\SavingsPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavingsPlanController extends Controller
{
    public function __construct(
        private readonly SavingsPlanService $savings,
        private readonly MainWalletService $mainWallet,
        private readonly RewardClaimService $rewardClaims,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->savings->catchUpUser($user);
        $plans = $user->savingsPlans()->latest('id')->get();
        $plans->each(fn (SavingsPlan $plan) => $plan->syncProgressFromDeductions());
        $plans = $user->savingsPlans()->latest('id')->get();

        return view('user.savings.index', [
            'user' => $user,
            'plans' => $plans,
            'mainWalletBalance' => $this->mainWallet->balance($user),
            'hasAcceptedTerms' => $user->hasAcceptedTerms(\App\Enums\TermsDocument::SavingsPlan),
            'minDaily' => PlatformSettings::savingsMinDaily(),
            'maxDaily' => PlatformSettings::savingsMaxDaily(),
            'targetDays' => PlatformSettings::savingsTargetDays(),
            'rewardPercent' => PlatformSettings::savingsRewardPercent(),
            'platformFeePercent' => PlatformSettings::platformFeePercent(),
            'unclaimedRewards' => $this->rewardClaims->unclaimedTotal($user),
            'earlyReturnHours' => PlatformSettings::savingsEarlyReturnHours(),
            'maxActivePlans' => 100,
        ]);
    }

    public function acceptTerms(Request $request): RedirectResponse
    {
        $request->validate([
            'terms' => ['accepted'],
        ]);

        $this->savings->acceptTerms(
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return back()->with('success', 'Savings Plan Terms & Conditions accepted.');
    }

    public function enroll(EnrollSavingsPlanRequest $request): RedirectResponse
    {
        $this->savings->enroll(
            $request->user(),
            (float) $request->validated('amount'),
            $request->validated('name'),
            $request->ip(),
            $request->userAgent(),
        );

        return back()->with('success', 'Savings Plan started — daily deductions will come from Main Wallet.');
    }

    public function earlyWithdraw(Request $request, SavingsPlan $plan): RedirectResponse
    {
        if ((int) $plan->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $this->savings->requestEarlyWithdrawal($request->user(), $plan);

        return back()->with('success', 'Early withdrawal requested. Saved funds return within 72 hours; reward cancelled.');
    }

    public function returnPrincipal(Request $request, SavingsPlan $plan): RedirectResponse
    {
        if ((int) $plan->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $this->savings->returnPrincipal($request->user(), $plan);

        return back()->with('success', 'Saved funds returned to Main Wallet (no Platform Fee).');
    }
}

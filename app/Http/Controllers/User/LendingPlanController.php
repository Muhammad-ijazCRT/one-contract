<?php

namespace App\Http\Controllers\User;

use App\Enums\LendingPlanType;
use App\Http\Controllers\Controller;
use App\Models\LendingPlan;
use App\Services\LendingPlanService;
use App\Services\MainWalletService;
use App\Services\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LendingPlanController extends Controller
{
    public function __construct(
        private readonly LendingPlanService $lending,
        private readonly MainWalletService $mainWallet,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $plans = $user->lendingPlans()->latest('id')->get();

        return view('user.lending.index', [
            'user' => $user,
            'plans' => $plans,
            'activePlans' => $plans->whereIn('status', [
                \App\Enums\LendingPlanStatus::Active,
                \App\Enums\LendingPlanStatus::PendingEarlyReturn,
                \App\Enums\LendingPlanStatus::PendingMaturityReturn,
                \App\Enums\LendingPlanStatus::Matured,
            ]),
            'mainWalletBalance' => $this->mainWallet->balance($user),
            'regular' => [
                'rate' => PlatformSettings::regularLendingDailyRatePercent(),
                'days' => PlatformSettings::regularLendingDurationDays(),
                'min' => PlatformSettings::regularLendingMin(),
                'max' => PlatformSettings::regularLendingMax(),
            ],
            'premium' => [
                'rate' => PlatformSettings::premiumLendingDailyRatePercent(),
                'days' => PlatformSettings::premiumLendingDurationDays(),
                'min' => PlatformSettings::premiumLendingMin(),
                'max' => PlatformSettings::premiumLendingMax(),
            ],
            'platformFeePercent' => PlatformSettings::platformFeePercent(),
            'earlyReturnHours' => PlatformSettings::lendingEarlyReturnHours(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['regular', 'premium'])],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $type = LendingPlanType::from($data['type']);

        $this->lending->open($request->user(), $type, (float) $data['amount']);

        return back()->with('success', $type->label().' started successfully.');
    }

    public function earlyWithdraw(Request $request, LendingPlan $plan): RedirectResponse
    {
        if ((int) $plan->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $this->lending->requestEarlyWithdrawal($request->user(), $plan);

        return back()->with('success', 'Early cancellation requested. Interest forfeited; principal returns within 72 hours.');
    }

    public function returnPrincipal(Request $request, LendingPlan $plan): RedirectResponse
    {
        if ((int) $plan->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $this->lending->returnPrincipal($request->user(), $plan);

        return back()->with('success', 'Lending principal returned to Main Wallet (no Platform Fee).');
    }
}

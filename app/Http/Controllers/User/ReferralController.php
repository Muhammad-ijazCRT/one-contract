<?php

namespace App\Http\Controllers\User;

use App\Enums\RewardType;
use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\Reward;
use App\Services\UserWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function __construct(
        private readonly UserWalletService $wallet,
    ) {}

    public function index(): View
    {
        $user = auth()->user();

        return view('user.referrals.index', [
            'user' => $user,
            'referralStats' => $this->wallet->referralStats($user),
            'referralLink' => url('/register?ref='.$user->referral_code),
            'network' => $user->referrals()
                ->with('referred')
                ->orderBy('level')
                ->orderByDesc('created_at')
                ->paginate(20),
        ]);
    }

    public function network(): RedirectResponse
    {
        return redirect()->route('user.referrals.index');
    }

    public function show(
        Referral $referral,
    ): View {
        $user = auth()->user();

        abort_unless($referral->referrer_id === $user->id, 403);

        $referral->loadMissing('referred');
        $activePrincipal = $referral->referred?->activePrincipal() ?? 0;

        $commissionEarned = (float) Reward::query()
            ->where('user_id', $user->id)
            ->where('referred_user_id', $referral->referred_id)
            ->whereIn('type', RewardType::referralTypeValues())
            ->sum('amount');

        $lastCommission = Reward::query()
            ->where('user_id', $user->id)
            ->where('referred_user_id', $referral->referred_id)
            ->whereIn('type', RewardType::referralTypeValues())
            ->latest('credited_at')
            ->first();

        return view('user.referrals.show', [
            'referral' => $referral,
            'referralStats' => $this->wallet->referralStats($user),
            'commissionEarned' => $commissionEarned,
            'lastCommission' => $lastCommission,
            'activePrincipal' => $activePrincipal,
        ]);
    }
}

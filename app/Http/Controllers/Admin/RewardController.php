<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RewardType;
use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Services\PlatformSettings;
use App\Services\RewardCalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RewardController extends Controller
{
    public function __construct(
        private readonly RewardCalculationService $rewards,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $typeFilter = $request->string('type')->trim()->toString();
        $query = Reward::query()->with(['user', 'deposit', 'clubInvestment.club'])->latest('credited_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('member_id', 'like', "%{$search}%");
                })
                ->orWhere('amount', 'like', "%{$search}%")
                ->orWhere('credited_at', 'like', "%{$search}%")
                ->orWhere('referral_level', 'like', "%{$search}%");
            });
        }

        if (in_array($typeFilter, ['daily_reward', 'referral_commission', 'club_reward'], true)) {
            $query->where('type', $typeFilter);
        }

        return view('admin.rewards.index', [
            'dailyRate' => PlatformSettings::dailyRewardPercent(),
            'totalDailyPaid' => (float) Reward::query()
                ->where('type', RewardType::DailyReward)
                ->sum('amount'),
            'totalReferralPaid' => (float) Reward::query()
                ->where('type', RewardType::ReferralCommission)
                ->sum('amount'),
            'totalClubPaid' => (float) Reward::query()
                ->where('type', RewardType::ClubReward)
                ->sum('amount'),
            'dailyOwedToday' => $this->rewards->totalDailyRewardsOwed(),
            'rewards' => $query->paginate(25)->withQueryString(),
            'search' => $search,
            'typeFilter' => $typeFilter,
        ]);
    }

    public function show(Reward $reward): View
    {
        $reward->load(['user', 'deposit', 'referredUser']);

        return view('admin.rewards.show', compact('reward'));
    }
}

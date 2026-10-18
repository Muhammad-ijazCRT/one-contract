<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RewardType;
use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\Reward;
use App\Models\User;
use App\Services\PlatformSettings;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $levelStats = [];

        foreach ([1, 2, 3] as $level) {
            $levelStats[$level] = [
                'count' => Referral::query()->where('level', $level)->count(),
                'earnings' => (float) Reward::query()
                    ->where('type', RewardType::ReferralCommission)
                    ->where('referral_level', $level)
                    ->sum('amount'),
                'rate' => PlatformSettings::referralRate($level) * 100,
            ];
        }

        $recentQuery = Referral::query()->with(['referrer', 'referred'])->latest();

        if ($search !== '') {
            $recentQuery->where(function ($q) use ($search) {
                $q->whereHas('referrer', function ($referrerQuery) use ($search) {
                    $referrerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('member_id', 'like', "%{$search}%");
                })->orWhereHas('referred', function ($referredQuery) use ($search) {
                    $referredQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('member_id', 'like', "%{$search}%");
                })->orWhere('level', 'like', "%{$search}%");
            });
        }

        return view('admin.referrals.index', [
            'levelStats' => $levelStats,
            'totalReferrals' => Referral::query()->count(),
            'totalCommissions' => (float) Reward::query()
                ->where('type', RewardType::ReferralCommission)
                ->sum('amount'),
            'recent' => $recentQuery->paginate(20)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function show(Referral $referral): View
    {
        $referral->load(['referrer', 'referred']);

        return view('admin.referrals.show', compact('referral'));
    }
}

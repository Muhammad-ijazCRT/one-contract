<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RewardType;
use App\Enums\WithdrawalStatus;
use App\Enums\WithdrawalType;
use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\WithdrawalRequest;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function earnings(): View
    {
        $daily = (float) Reward::query()->where('type', RewardType::DailyReward)->sum('amount');
        $referral = (float) Reward::query()->where('type', RewardType::ReferralCommission)->sum('amount');

        return view('admin.reports.earnings', [
            'totalDaily' => $daily,
            'totalReferral' => $referral,
            'total' => $daily + $referral,
            'rewards' => Reward::query()
                ->with('user')
                ->latest('credited_at')
                ->paginate(25),
        ]);
    }

    public function withdrawals(): View
    {
        return view('admin.reports.withdrawals', [
            'totalCompleted' => (float) WithdrawalRequest::query()
                ->where('status', WithdrawalStatus::Completed)
                ->sum('amount'),
            'totalReward' => (float) WithdrawalRequest::query()
                ->where('type', WithdrawalType::Reward)
                ->where('status', WithdrawalStatus::Completed)
                ->sum('amount'),
            'totalCapital' => (float) WithdrawalRequest::query()
                ->where('type', WithdrawalType::Capital)
                ->where('status', WithdrawalStatus::Completed)
                ->sum('amount'),
            'withdrawals' => WithdrawalRequest::query()
                ->with('user')
                ->latest()
                ->paginate(25),
        ]);
    }
}

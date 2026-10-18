<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->string('type')->toString();
        $search = $request->string('q')->trim()->toString();

        $query = Transaction::query()
            ->with('user')
            ->latest();

        if ($type && in_array($type, array_column(TransactionType::cases(), 'value'), true)) {
            $query->where('type', $type);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('member_id', 'like', "%{$search}%");
                })
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        return view('admin.transactions.index', [
            'transactions' => $query->paginate(25)->withQueryString(),
            'typeFilter' => $type,
            'search' => $search,
            'counts' => [
                'all' => Transaction::query()->count(),
                'deposit' => Transaction::query()->where('type', TransactionType::Deposit)->count(),
                'daily_reward' => Transaction::query()->where('type', TransactionType::DailyReward)->count(),
                'referral_commission' => Transaction::query()->where('type', TransactionType::ReferralCommission)->count(),
                'reward_withdrawal' => Transaction::query()->where('type', TransactionType::RewardWithdrawal)->count(),
                'capital_withdrawal' => Transaction::query()->where('type', TransactionType::CapitalWithdrawal)->count(),
            ],
        ]);
    }

    public function show(Transaction $transaction): View
    {
        return view('admin.transactions.show', [
            'tx' => $transaction->load('user'),
        ]);
    }
}

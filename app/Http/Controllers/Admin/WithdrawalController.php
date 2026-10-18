<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WithdrawalStatus;
use App\Enums\WithdrawalType;
use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\AdminLogService;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawals,
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $type = $request->string('type')->toString();
        $search = $request->string('q')->trim()->toString();

        $baseQuery = WithdrawalRequest::query();

        if ($type === 'reward') {
            $baseQuery->where('type', WithdrawalType::Reward);
        } elseif ($type === 'capital') {
            $baseQuery->where('type', WithdrawalType::Capital);
        }

        $query = (clone $baseQuery)
            ->with(['user', 'deposit'])
            ->latest();

        if ($status && in_array($status, ['pending', 'approved', 'completed', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('member_id', 'like', "%{$search}%");
                })
                ->orWhere('wallet_address', 'like', "%{$search}%")
                ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        return view('admin.withdrawals.index', [
            'withdrawals' => $query->paginate(20)->withQueryString(),
            'statusFilter' => $status,
            'typeFilter' => $type,
            'search' => $search,
            'pageTitle' => match ($type) {
                'reward' => 'Main Wallet Withdrawals',
                'capital' => 'Capital Withdrawals',
                default => 'Withdrawals',
            },
            'counts' => [
                'all' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved])->count(),
                'completed' => (clone $baseQuery)->where('status', WithdrawalStatus::Completed)->count(),
                'rejected' => (clone $baseQuery)->where('status', WithdrawalStatus::Rejected)->count(),
            ],
        ]);
    }

    public function approve(WithdrawalRequest $withdrawal): RedirectResponse
    {
        $this->withdrawals->approve($withdrawal, auth()->user());

        $this->adminLog->log(
            auth()->user(),
            'withdrawal_approved',
            $withdrawal,
            'Approved capital withdrawal of '.number_format((float) $withdrawal->amount, 2).' USDT',
        );

        return back()->with('success', 'Capital withdrawal approved. User has been notified.');
    }

    public function complete(WithdrawalRequest $withdrawal): RedirectResponse
    {
        $this->withdrawals->complete($withdrawal, auth()->user());

        $this->adminLog->log(
            auth()->user(),
            'withdrawal_completed',
            $withdrawal,
            'Completed '.($withdrawal->type->value).' withdrawal of '.number_format((float) $withdrawal->amount, 2).' USDT',
        );

        return back()->with('success', 'Withdrawal marked as completed.');
    }

    public function reject(Request $request, WithdrawalRequest $withdrawal): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $notes = $validated['notes'] ?? null;
        $this->withdrawals->reject($withdrawal, auth()->user(), $notes);

        $this->adminLog->log(
            auth()->user(),
            'withdrawal_rejected',
            $withdrawal,
            'Rejected '.($withdrawal->type->value).' withdrawal of '.number_format((float) $withdrawal->amount, 2).' USDT',
            $notes ? ['notes' => $notes] : [],
        );

        return back()->with('success', 'Withdrawal rejected.');
    }

    public function show(WithdrawalRequest $withdrawal): View
    {
        $withdrawal->load(['user', 'deposit', 'processor']);

        return view('admin.withdrawals.show', compact('withdrawal'));
    }

    private function renderIndex(Request $request, ?string $type = null): View
    {
        if ($type !== null) {
            $request->merge(['type' => $type]);
        }

        return $this->index($request);
    }

    public function reward(Request $request): View
    {
        return $this->renderIndex($request, 'reward');
    }

    public function capital(Request $request): View
    {
        return $this->renderIndex($request, 'capital');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepositStatus;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\AdminLogService;
use App\Services\DepositService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function __construct(
        private readonly DepositService $deposits,
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $search = $request->string('q')->trim()->toString();

        $query = Deposit::query()
            ->with(['user', 'approver'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->latest();

        if ($status && in_array($status, ['pending', 'active', 'rejected', 'withdrawn'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('member_id', 'like', "%{$search}%");
                })
                ->orWhere('amount', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%");
            });
        }

        return view('admin.deposits.index', [
            'deposits' => $query->paginate(20)->withQueryString(),
            'statusFilter' => $status,
            'search' => $search,
            'counts' => [
                'all' => Deposit::query()->count(),
                'pending' => Deposit::query()->where('status', DepositStatus::Pending)->count(),
                'active' => Deposit::query()->whereIn('status', [DepositStatus::Active, DepositStatus::Completed])->count(),
                'rejected' => Deposit::query()->where('status', DepositStatus::Rejected)->count(),
                'withdrawn' => Deposit::query()->where('status', DepositStatus::Withdrawn)->count(),
            ],
        ]);
    }

    public function show(Deposit $deposit): View
    {
        return view('admin.deposits.show', [
            'deposit' => $deposit->load(['user', 'approver']),
        ]);
    }

    public function approve(Deposit $deposit): RedirectResponse
    {
        $this->deposits->approve($deposit, auth()->user());

        $this->adminLog->log(
            auth()->user(),
            'deposit_approved',
            $deposit,
            'Approved deposit of '.number_format((float) $deposit->amount, 2).' USDT for '.$deposit->user?->name,
        );

        return back()->with('success', 'Deposit approved. Investment is now active and daily rewards will begin.');
    }

    public function reject(Request $request, Deposit $deposit): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $notes = $validated['notes'] ?? null;
        $this->deposits->reject($deposit, auth()->user(), $notes);

        $this->adminLog->log(
            auth()->user(),
            'deposit_rejected',
            $deposit,
            'Rejected deposit of '.number_format((float) $deposit->amount, 2).' USDT for '.$deposit->user?->name,
            $notes ? ['notes' => $notes] : [],
        );

        return back()->with('success', 'Deposit rejected.');
    }
}

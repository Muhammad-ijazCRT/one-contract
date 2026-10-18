<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SavingsPlanStatus;
use App\Http\Controllers\Controller;
use App\Models\SavingsPlan;
use App\Services\AdminLogService;
use App\Services\SavingsPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavingsPlanController extends Controller
{
    public function __construct(
        private readonly SavingsPlanService $savings,
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(Request $request): View
    {
        $statusFilter = $request->string('status')->trim()->toString();
        $search = $request->string('q')->trim()->toString();

        $query = SavingsPlan::query()
            ->with('user')
            ->latest('id');

        $validStatuses = array_column(SavingsPlanStatus::cases(), 'value');

        if (in_array($statusFilter, $validStatuses, true)) {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('member_id', 'like', "%{$search}%");
                })
                    ->orWhere('daily_amount', 'like', "%{$search}%")
                    ->orWhere('total_saved', 'like', "%{$search}%");
            });
        }

        return view('admin.savings.index', [
            'plans' => $query->paginate(25)->withQueryString(),
            'statusFilter' => $statusFilter,
            'search' => $search,
            'counts' => [
                'all' => SavingsPlan::query()->count(),
                'active' => SavingsPlan::query()->where('status', SavingsPlanStatus::Active)->count(),
                'pending_early_return' => SavingsPlan::query()->where('status', SavingsPlanStatus::PendingEarlyReturn)->count(),
                'completed' => SavingsPlan::query()->where('status', SavingsPlanStatus::Completed)->count(),
                'withdrawn_early' => SavingsPlan::query()->where('status', SavingsPlanStatus::WithdrawnEarly)->count(),
            ],
            'activeSaved' => (float) SavingsPlan::query()
                ->whereIn('status', [SavingsPlanStatus::Active, SavingsPlanStatus::PendingEarlyReturn])
                ->sum('total_saved'),
        ]);
    }

    public function completeEarlyReturn(SavingsPlan $plan): RedirectResponse
    {
        if ($plan->status !== SavingsPlanStatus::PendingEarlyReturn) {
            return back()->withErrors(['status' => 'Only pending early returns can be completed.']);
        }

        $this->savings->completeEarlyReturn($plan);

        $this->adminLog->log(
            auth()->user(),
            'savings_early_return_completed',
            $plan,
            'Admin forced savings early return to Main Wallet',
            ['amount' => $plan->total_saved],
        );

        return back()->with('success', 'Savings early return credited to Main Wallet.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClubCapitalRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ClubCapitalRequest;
use App\Services\AdminLogService;
use App\Services\ClubInvestmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubCapitalRequestController extends Controller
{
    public function __construct(
        private readonly ClubInvestmentService $clubs,
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(Request $request): View
    {
        $statusFilter = $request->string('status')->trim()->toString();
        $search = $request->string('q')->trim()->toString();

        $query = ClubCapitalRequest::query()
            ->with(['user', 'investment.club'])
            ->latest('id');

        if (in_array($statusFilter, ['pending', 'completed', 'cancelled'], true)) {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('member_id', 'like', "%{$search}%");
                })
                    ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        return view('admin.capital-requests.index', [
            'requests' => $query->paginate(25)->withQueryString(),
            'statusFilter' => $statusFilter,
            'search' => $search,
            'counts' => [
                'all' => ClubCapitalRequest::query()->count(),
                'pending' => ClubCapitalRequest::query()->where('status', ClubCapitalRequestStatus::Pending)->count(),
                'completed' => ClubCapitalRequest::query()->where('status', ClubCapitalRequestStatus::Completed)->count(),
                'cancelled' => ClubCapitalRequest::query()->where('status', ClubCapitalRequestStatus::Cancelled)->count(),
            ],
            'pendingAmount' => (float) ClubCapitalRequest::query()
                ->where('status', ClubCapitalRequestStatus::Pending)
                ->sum('amount'),
        ]);
    }

    public function complete(ClubCapitalRequest $capitalRequest): RedirectResponse
    {
        if ($capitalRequest->status !== ClubCapitalRequestStatus::Pending) {
            return back()->withErrors(['status' => 'Only pending capital returns can be completed.']);
        }

        $this->clubs->completeCapitalReturn($capitalRequest);

        $this->adminLog->log(
            auth()->user(),
            'club_capital_return_completed',
            $capitalRequest,
            'Admin forced club capital return to Main Wallet',
            ['amount' => $capitalRequest->amount],
        );

        return back()->with('success', 'Capital return credited to Main Wallet.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClubInvestmentStatus;
use App\Http\Controllers\Controller;
use App\Models\ClubInvestment;
use App\Models\InvestmentClub;
use App\Services\AdminLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvestmentClubController extends Controller
{
    public function __construct(
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        $clubs = InvestmentClub::query()
            ->orderBy('sort_order')
            ->get()
            ->map(function (InvestmentClub $club) {
                $club->active_capital = (float) ClubInvestment::query()
                    ->where('investment_club_id', $club->id)
                    ->where('status', ClubInvestmentStatus::Active)
                    ->sum('amount');

                $club->investor_count = (int) ClubInvestment::query()
                    ->where('investment_club_id', $club->id)
                    ->where('status', ClubInvestmentStatus::Active)
                    ->where('amount', '>', 0)
                    ->count();

                return $club;
            });

        $investmentsQuery = ClubInvestment::query()
            ->with(['user', 'club'])
            ->latest('id');

        if ($search !== '') {
            $investmentsQuery->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('member_id', 'like', "%{$search}%");
                })
                    ->orWhereHas('club', function ($clubQuery) use ($search) {
                        $clubQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        return view('admin.clubs.index', [
            'clubs' => $clubs,
            'investments' => $investmentsQuery->paginate(25)->withQueryString(),
            'search' => $search,
            'totalActiveCapital' => (float) ClubInvestment::query()
                ->where('status', ClubInvestmentStatus::Active)
                ->sum('amount'),
            'totalInvestors' => (int) ClubInvestment::query()
                ->where('status', ClubInvestmentStatus::Active)
                ->where('amount', '>', 0)
                ->selectRaw('count(distinct user_id) as aggregate')
                ->value('aggregate'),
        ]);
    }

    public function update(Request $request, InvestmentClub $club): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'reward_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'cycle_days' => ['required', 'integer', 'min:1', 'max:365'],
            'min_investment' => ['required', 'numeric', 'min:0.01'],
            'max_investment' => ['required', 'numeric', 'min:0.01'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($data['max_investment'] < $data['min_investment']) {
            return back()
                ->withErrors(['max_investment' => 'Max investment must be greater than or equal to min investment.'])
                ->withInput($request->except(['_token', '_method']) + ['_club_id' => $club->id]);
        }

        $club->update([
            'name' => $data['name'],
            'reward_percent' => $data['reward_percent'],
            'cycle_days' => $data['cycle_days'],
            'min_investment' => $data['min_investment'],
            'max_investment' => $data['max_investment'],
            'sort_order' => $data['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->adminLog->log(
            auth()->user(),
            'investment_club_updated',
            $club,
            "Updated investment club: {$club->name}",
            $data,
        );

        return back()->with('success', "{$club->name} settings saved.");
    }
}

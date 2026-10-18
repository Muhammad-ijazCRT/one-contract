<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\InvestClubRequest;
use App\Http\Requests\User\ReduceClubInvestmentRequest;
use App\Models\ClubInvestment;
use App\Models\InvestmentClub;
use App\Services\ClubInvestmentService;
use App\Services\MainWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubInvestmentController extends Controller
{
    public function __construct(
        private readonly ClubInvestmentService $clubs,
        private readonly MainWalletService $mainWallet,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $catalog = InvestmentClub::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $investments = ClubInvestment::query()
            ->with('club')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        $totalEarned = (float) ClubInvestment::query()
            ->where('user_id', $user->id)
            ->sum('total_rewards_earned');

        $totalParticipants = (int) ClubInvestment::query()->distinct('user_id')->count('user_id');

        $participantsByClub = ClubInvestment::query()
            ->where('status', 'active')
            ->selectRaw('investment_club_id, count(distinct user_id) as participants')
            ->groupBy('investment_club_id')
            ->pluck('participants', 'investment_club_id');

        $mostPopularClubId = $participantsByClub->isNotEmpty()
            ? $participantsByClub->sortDesc()->keys()->first()
            : optional($catalog->first())->id;

        return view('user.clubs.index', [
            'user' => $user,
            'catalog' => $catalog,
            'investments' => $investments,
            'mainWalletBalance' => $this->mainWallet->balance($user),
            'hasAcceptedTerms' => $user->hasAcceptedTerms(\App\Enums\TermsDocument::InvestmentClubs),
            'pendingReturns' => $user->clubCapitalRequests()
                ->with('investment.club')
                ->where('status', \App\Enums\ClubCapitalRequestStatus::Pending)
                ->latest()
                ->get(),
            'totalEarned' => $totalEarned,
            'totalParticipants' => $totalParticipants,
            'participantsByClub' => $participantsByClub,
            'mostPopularClubId' => $mostPopularClubId,
        ]);
    }

    public function acceptTerms(Request $request): RedirectResponse
    {
        $request->validate([
            'terms' => ['accepted'],
        ]);

        $this->clubs->acceptTerms(
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return back()->with('success', 'Investment Schemes Terms & Conditions accepted.');
    }

    public function invest(InvestClubRequest $request, InvestmentClub $club): RedirectResponse
    {
        $this->clubs->invest(
            $request->user(),
            $club,
            (float) $request->validated('amount'),
            $request->ip(),
            $request->userAgent(),
        );

        return back()->with('success', 'Funds invested in '.$club->name.' successfully.');
    }

    public function withdraw(Request $request, ClubInvestment $investment): RedirectResponse
    {
        $this->clubs->withdrawInvestment(
            $request->user(),
            $investment
        );

        return back()->with('success', 'Investment withdrawal requested. Funds return to Main Wallet within 72 hours.');
    }
}

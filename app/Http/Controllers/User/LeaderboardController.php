<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
    ) {}

    public function index(Request $request): View
    {
        $period = $request->string('period')->toString();
        if (! in_array($period, ['all', 'week', 'month'], true)) {
            $period = 'month';
        }

        $tab = $request->string('tab')->toString();
        if (in_array($tab, ['games', 'referrals', ''], true)) {
            $tab = 'betting';
        }
        if (in_array($tab, ['saving', 'savings'], true)) {
            $tab = 'investment';
        }
        if (! in_array($tab, ['betting', 'lending', 'investment'], true)) {
            $tab = 'betting';
        }

        $user = $request->user();
        $games = $this->leaderboard->gameLeaders($period, 50, $user);
        $lending = $this->leaderboard->lendingLeaders(50, $user);
        $investment = $this->leaderboard->investmentLeaders(50, $user);

        $boards = [
            'betting' => $games,
            'lending' => $lending,
            'investment' => $investment,
        ];

        $active = $boards[$tab];

        return view('user.leaderboard.index', [
            'period' => $period,
            'tab' => $tab,
            'rows' => $active['rows'],
            'myRank' => $active['my_rank'],
            'previews' => [
                'lending' => $lending['rows']->take(3),
                'investment' => $investment['rows']->take(3),
            ],
            'bettingPreview' => $games['rows']->take(3),
        ]);
    }
}

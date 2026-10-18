<?php

namespace App\Http\Controllers\User;

use App\Enums\AzuboguBetStatus;
use App\Enums\AzuboguRoundStatus;
use App\Http\Controllers\Controller;
use App\Models\AzuboguBet;
use App\Models\AzuboguRound;
use App\Services\AzuboguGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AzuboguGameController extends Controller
{
    public function __construct(
        private readonly AzuboguGameService $game,
    ) {}

    public function index(Request $request): View
    {
        return view('user.azubogu.index', [
            'state' => $this->game->stateFor($request->user()),
        ]);
    }

    public function state(Request $request): JsonResponse
    {
        return response()->json($this->game->stateFor($request->user()));
    }

    public function history(Request $request): View
    {
        $history = AzuboguRound::query()
            ->whereNotNull('multipliers')
            ->whereIn('status', [AzuboguRoundStatus::Settling, AzuboguRoundStatus::Completed])
            ->orderByDesc('id')
            ->paginate(30)
            ->through(function (AzuboguRound $round): array {
                return [
                    'round_number' => (int) $round->round_number,
                    'time' => $round->settled_at?->timezone(config('app.timezone'))->format('d M Y, g:i A') ?? '—',
                    'groups' => $round->groupedMultipliers(),
                ];
            });

        return view('user.azubogu.history', [
            'history' => $history,
        ]);
    }

    public function myBets(Request $request): View
    {
        $bets = AzuboguBet::query()
            ->with('round:id,round_number,settled_at')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', [
                AzuboguBetStatus::Won,
                AzuboguBetStatus::Lost,
                AzuboguBetStatus::Active,
            ])
            ->latest('id')
            ->paginate(30)
            ->through(function (AzuboguBet $bet): array {
                return [
                    'round' => (int) ($bet->round?->round_number ?? 0),
                    'time' => $bet->round?->settled_at?->timezone(config('app.timezone'))->format('d M Y, g:i A')
                        ?? $bet->created_at?->timezone(config('app.timezone'))->format('d M Y, g:i A')
                        ?? '—',
                    'numbers' => $bet->numbers ?? [],
                    'amount' => (float) $bet->amount,
                    'payout' => (float) ($bet->payout ?? 0),
                    'multiplier' => (float) ($bet->combined_multiplier ?? 0),
                    'status' => $bet->status->value,
                ];
            });

        return view('user.azubogu.my-bets', [
            'bets' => $bets,
        ]);
    }

    public function bet(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numbers' => ['required', 'array', 'min:1', 'max:3'],
            'numbers.*' => ['integer', 'min:1', 'max:12'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $bet = $this->game->placeBet(
            $request->user(),
            $data['numbers'],
            (float) $data['amount'],
        );

        // Skip tick/advance — return fast so Place Bet feels instant.
        return response()->json([
            'ok' => true,
            'bet' => [
                'numbers' => $bet->numbers,
                'amount' => (float) $bet->amount,
                'status' => $bet->status->value,
            ],
            'state' => $this->game->stateFor($request->user(), advance: false),
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $this->game->cancelBet($request->user());

        return response()->json([
            'ok' => true,
            'state' => $this->game->stateFor($request->user(), advance: false),
        ]);
    }
}

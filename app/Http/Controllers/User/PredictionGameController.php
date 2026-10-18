<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\PredictionGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PredictionGameController extends Controller
{
    public function __construct(
        private readonly PredictionGameService $game,
    ) {}

    public function index(Request $request): View
    {
        $state = $this->game->stateFor($request->user());

        return view('user.prediction.index', [
            'state' => $state,
        ]);
    }

    public function state(Request $request): JsonResponse
    {
        return response()->json($this->game->stateFor($request->user()));
    }

    public function bet(Request $request): JsonResponse
    {
        $data = $request->validate([
            'outcome' => ['required', 'integer', 'in:1,2'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:16'],
        ]);

        $user = $request->user();
        if (! empty($data['currency'])) {
            $resolved = \App\Enums\WalletCurrency::tryNormalize($data['currency']);
            if ($resolved) {
                $user->setPreferredWalletCurrency($resolved);
            }
        }

        $bet = $this->game->placeBet(
            $user,
            (int) $data['outcome'],
            (float) $data['amount'],
            $user->preferredWalletCurrency(),
        );

        $currency = $bet->walletCurrency();

        return response()->json([
            'ok' => true,
            'bet' => [
                'outcome' => (int) $bet->outcome,
                'amount' => (float) $bet->amount,
                'currency' => $currency->value,
                'symbol' => $currency->symbol(),
                'formatted' => $currency->format((float) $bet->amount),
                'status' => $bet->status->value,
            ],
            'state' => $this->game->stateFor($request->user()),
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $this->game->cancelBet($request->user());

        return response()->json([
            'ok' => true,
            'state' => $this->game->stateFor($request->user()),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\MonnifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MonnifyController extends Controller
{
    public function __construct(
        private readonly MonnifyService $monnify,
    ) {}

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        try {
            $this->monnify->handleWebhook(is_array($payload) ? $payload : []);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false], 500);
        }

        return response()->json(['ok' => true]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $reference = (string) $request->query('paymentReference', $request->query('reference', ''));

        if ($reference !== '') {
            $this->monnify->syncDepositFromCallback($reference);
        }

        return redirect()
            ->route('user.deposit.index', ['method' => 'fiat'])
            ->with('success', 'Payment received. Your NGN wallet will update once Monnify confirms the transaction.');
    }
}

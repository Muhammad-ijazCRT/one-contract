<?php

namespace App\Http\Controllers;

use App\Services\DepositService;
use App\Services\NowPaymentsSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NowPaymentsController extends Controller
{
    public function __construct(
        private readonly DepositService $deposits,
    ) {}

    public function ipn(Request $request): JsonResponse
    {
        $secret = (string) NowPaymentsSettings::ipnSecret();
        $signature = (string) $request->header('x-nowpayments-sig', '');
        $data = $request->all();

        if ($secret !== '' && ! $this->signatureIsValid($data, $signature, $secret)) {
            return response()->json(['message' => 'Invalid signature'], Response::HTTP_FORBIDDEN);
        }

        $deposit = $this->deposits->handlePaymentNotification($data);

        if (! $deposit) {
            return response()->json(['message' => 'Payment not found']);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function signatureIsValid(array $data, string $signature, string $secret): bool
    {
        if ($signature === '') {
            return false;
        }

        ksort($data);
        $sortedPayload = json_encode($data, JSON_UNESCAPED_SLASHES);

        if (! is_string($sortedPayload)) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $sortedPayload, $secret), $signature);
    }
}

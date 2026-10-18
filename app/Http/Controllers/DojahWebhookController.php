<?php

namespace App\Http\Controllers;

use App\Models\DojahCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DojahWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        DojahCheck::query()->create([
            'type' => DojahCheck::TYPE_TRANSACTION,
            'purpose' => 'webhook',
            'reference' => (string) ($payload['reference'] ?? $payload['reference_id'] ?? Str::uuid()->toString()),
            'passed' => ! in_array(strtolower((string) data_get($payload, 'decision.action', '')), ['block', 'deny'], true),
            'decision' => data_get($payload, 'decision.action'),
            'score' => is_numeric(data_get($payload, 'score')) ? data_get($payload, 'score') : null,
            'message' => 'Webhook received from Dojah.',
            'request_meta' => [
                'ip' => $request->ip(),
            ],
            'response' => $payload,
        ]);

        return response()->json(['ok' => true]);
    }
}

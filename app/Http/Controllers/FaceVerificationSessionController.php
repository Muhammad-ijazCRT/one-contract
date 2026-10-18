<?php

namespace App\Http\Controllers;

use App\Enums\FaceVerificationPurpose;
use App\Services\FaceVerificationSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FaceVerificationSessionController extends Controller
{
    public function start(Request $request, FaceVerificationSessionService $sessions): JsonResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', Rule::enum(FaceVerificationPurpose::class)],
            'id_staging_token' => ['nullable', 'string', 'min:32', 'max:64'],
            'selfie_staging_token' => ['nullable', 'string', 'min:32', 'max:64'],
        ]);

        $purpose = FaceVerificationPurpose::from($validated['purpose']);

        if ($purpose === FaceVerificationPurpose::Withdrawal && ! $request->user()) {
            abort(401);
        }

        $session = $sessions->start($purpose, $request->user(), $request);

        return response()->json([
            'session_token' => $session->session_token,
            'challenges' => $session->challengesWithLabels(),
            'expires_at' => $session->expires_at?->toIso8601String(),
        ]);
    }

    public function challenge(Request $request, FaceVerificationSessionService $sessions): JsonResponse
    {
        $validated = $request->validate([
            'session_token' => ['required', 'string', 'min:32', 'max:64'],
            'challenge' => ['required', 'string', 'in:center,blink,nod,shake'],
        ]);

        $session = $sessions->recordChallenge(
            $validated['session_token'],
            $validated['challenge'],
        );

        return response()->json([
            'completed' => count($session->challenges_completed ?? []),
            'total' => count($session->challenges ?? []),
        ]);
    }

    public function complete(Request $request, FaceVerificationSessionService $sessions): JsonResponse
    {
        $validated = $request->validate([
            'session_token' => ['required', 'string', 'min:32', 'max:64'],
            'face_image' => ['required', 'string', 'max:12000000', 'regex:/^data:image\/(jpeg|jpg|png|webp);base64,/i'],
            'metrics' => ['required', 'array'],
            'metrics.duration_seconds' => ['required', 'numeric', 'min:0'],
            'metrics.challenge_flags' => ['required', 'array'],
        ]);

        $session = $sessions->complete(
            $validated['session_token'],
            $validated['face_image'],
            $validated['metrics'],
        );

        return response()->json([
            'session_token' => $session->session_token,
            'status' => $session->status->value,
            'liveness_passed' => $session->liveness_passed,
            'face_match_passed' => $session->face_match_passed,
            'liveness_confidence' => $session->liveness_confidence,
            'face_match_confidence' => $session->face_match_confidence,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\IdCardStagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdCardStagingController extends Controller
{
    public function store(Request $request, IdCardStagingService $staging): JsonResponse
    {
        $imageRule = ['nullable', 'string', 'max:12000000', 'regex:/^data:image\/(jpeg|jpg|png|webp);base64,/i'];

        $validated = $request->validate([
            'id_card_image' => ['required', 'string', 'max:12000000', 'regex:/^data:image\/(jpeg|jpg|png|webp);base64,/i'],
            'id_card_back_image' => $imageRule,
            'id_document_type' => ['nullable', 'string', 'in:national_id,passport,license'],
            'passport_image' => $imageRule,
            'license_image' => $imageRule,
            'license_back_image' => $imageRule,
        ]);

        $type = (string) ($validated['id_document_type'] ?? '');
        $needsBack = $type !== 'passport';
        $back = $validated['id_card_back_image'] ?? null;

        if ($needsBack && ! $back) {
            return response()->json([
                'message' => 'Please upload the back of your ID or license.',
                'errors' => ['id_card_back_image' => ['Please upload the back of your ID or license.']],
            ], 422);
        }

        $result = $staging->stage(
            $validated['id_card_image'],
            $needsBack ? $back : null,
            $type !== '' ? $type : null,
            [
                'passport_front' => $validated['passport_image'] ?? null,
                'license_front' => $validated['license_image'] ?? null,
                'license_back' => $validated['license_back_image'] ?? null,
            ]
        );

        return response()->json($result);
    }
}

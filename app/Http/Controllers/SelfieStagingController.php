<?php

namespace App\Http\Controllers;

use App\Services\SelfieStagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SelfieStagingController extends Controller
{
    public function store(Request $request, SelfieStagingService $staging): JsonResponse
    {
        $validated = $request->validate([
            'selfie_image' => ['required', 'string', 'max:12000000', 'regex:/^data:image\/(jpeg|jpg|png|webp);base64,/i'],
            'id_staging_token' => ['required', 'string', 'size:64'],
        ]);

        $result = $staging->stage(
            $validated['selfie_image'],
            $validated['id_staging_token'],
        );

        return response()->json($result);
    }
}

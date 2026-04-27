<?php

namespace App\Http\Controllers\Profile\Telegram;

use App\Http\Controllers\Controller;
use App\Services\TelegramLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenerateTokenController extends Controller
{
    public function __invoke(Request $request, TelegramLinkService $linkService): JsonResponse
    {
        $payload = $linkService->generateForUser($request->user());

        return response()->json([
            'link' => $payload['link'],
            'expires_in' => $payload['expires_in'],
        ]);
    }
}

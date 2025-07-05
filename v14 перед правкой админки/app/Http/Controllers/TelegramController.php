<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TelegramController extends Controller
{
    /**
     * Обработка webhook от Telegram
     * POST /api/telegram/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        // TODO: Реализовать обработку Telegram webhook
        return response()->json(['status' => 'ok']);
    }
}
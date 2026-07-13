<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Plant;
use App\Services\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function chat(Request $request, AiAssistantService $ai): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'nullable|array',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string',
        ]);

        $response = $ai->chat(
            $request->user(),
            $request->input('message'),
            $request->input('history', [])
        );

        return response()->json(['data' => ['response' => $response]]);
    }

    public function analyzePlant(Request $request, AiAssistantService $ai, Plant $plant): JsonResponse
    {
        $analysis = $ai->analyzePlantPerformance($plant);
        return response()->json(['data' => ['analysis' => $analysis]]);
    }

    public function analyzeInvoice(Request $request, AiAssistantService $ai, Invoice $invoice): JsonResponse
    {
        $analysis = $ai->analyzeInvoice($invoice);
        return response()->json(['data' => ['analysis' => $analysis]]);
    }
}

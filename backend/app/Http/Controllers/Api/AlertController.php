<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InverterAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InverterAlert::with('inverter:id,serial_number,model,brand');

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->boolean('unresolved', false)) {
            $query->unresolved();
        }

        $alerts = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($alerts);
    }

    public function resolve(InverterAlert $alert): JsonResponse
    {
        $alert->update(['resolved_at' => now()]);
        return response()->json(['data' => $alert]);
    }

    public function stats(): JsonResponse
    {
        $total = InverterAlert::unresolved()->count();
        $bySeverity = InverterAlert::unresolved()
            ->selectRaw("severity, COUNT(*) as count")
            ->groupBy('severity')
            ->pluck('count', 'severity');

        $byType = InverterAlert::unresolved()
            ->selectRaw("type, COUNT(*) as count")
            ->groupBy('type')
            ->pluck('count', 'type');

        return response()->json([
            'data' => [
                'total_unresolved' => $total,
                'by_severity' => $bySeverity,
                'by_type' => $byType,
            ],
        ]);
    }
}

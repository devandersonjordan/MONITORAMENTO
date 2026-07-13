<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InverterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Inverter::with('plant:id,name,client_id');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'ilike', "%{$search}%")
                  ->orWhere('model', 'ilike', "%{$search}%")
                  ->orWhere('brand', 'ilike', "%{$search}%");
            });
        }

        if ($brand = $request->get('brand')) {
            $query->where('brand', $brand);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($plantId = $request->get('plant_id')) {
            $query->where('plant_id', $plantId);
        }

        $inverters = $query->orderBy('brand')->paginate($request->get('per_page', 15));

        return response()->json($inverters);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plant_id' => ['required', 'integer', 'exists:plants,id'],
            'brand' => ['required', 'string', 'in:elekeeper,goodwe,sungrow,deye'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100', 'unique:inverters'],
            'api_credentials' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:online,offline,warning'],
        ]);

        $validated['company_id'] = $request->user()->company_id;

        $inverter = Inverter::create($validated);

        return response()->json(['data' => $inverter->load('plant'), 'message' => 'Inversor criado com sucesso.'], 201);
    }

    public function show(Inverter $inverter): JsonResponse
    {
        return response()->json([
            'data' => $inverter->load([
                'plant.client',
                'readings' => fn($q) => $q->latest('recorded_at')->limit(24),
                'alerts' => fn($q) => $q->whereNull('resolved_at'),
            ]),
        ]);
    }

    public function update(Request $request, Inverter $inverter): JsonResponse
    {
        $validated = $request->validate([
            'plant_id' => ['sometimes', 'integer', 'exists:plants,id'],
            'brand' => ['sometimes', 'string', 'in:elekeeper,goodwe,sungrow,deye'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100', 'unique:inverters,serial_number,' . $inverter->id],
            'api_credentials' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:online,offline,warning'],
        ]);

        $inverter->update($validated);

        return response()->json(['data' => $inverter, 'message' => 'Inversor atualizado com sucesso.']);
    }

    public function destroy(Inverter $inverter): JsonResponse
    {
        $inverter->delete();

        return response()->json(null, 204);
    }
}

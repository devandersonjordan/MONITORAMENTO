<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Plant::with(['client:id,name,email', 'inverters']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('address', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $plants = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json($plants);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'power_kwp' => ['required', 'numeric', 'min:0.1'],
            'installation_date' => ['required', 'date'],
            'module_model' => ['nullable', 'string', 'max:255'],
            'module_qty' => ['nullable', 'integer', 'min:1'],
            'inverter_model' => ['nullable', 'string', 'max:255'],
            'inverter_power_kw' => ['nullable', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
            'installer_company' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:active,maintenance,inactive'],
        ]);

        $validated['company_id'] = $request->user()->company_id;

        $plant = Plant::create($validated);

        return response()->json(['data' => $plant->load('client'), 'message' => 'Usina criada com sucesso.'], 201);
    }

    public function show(Plant $plant): JsonResponse
    {
        return response()->json([
            'data' => $plant->load(['client', 'inverters.readings' => fn($q) => $q->latest('recorded_at')->limit(10)]),
        ]);
    }

    public function update(Request $request, Plant $plant): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['sometimes', 'integer', 'exists:users,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'power_kwp' => ['sometimes', 'numeric', 'min:0.1'],
            'installation_date' => ['sometimes', 'date'],
            'module_model' => ['nullable', 'string', 'max:255'],
            'module_qty' => ['nullable', 'integer', 'min:1'],
            'inverter_model' => ['nullable', 'string', 'max:255'],
            'inverter_power_kw' => ['nullable', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
            'installer_company' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:active,maintenance,inactive'],
        ]);

        $plant->update($validated);

        return response()->json(['data' => $plant, 'message' => 'Usina atualizada com sucesso.']);
    }

    public function destroy(Plant $plant): JsonResponse
    {
        $plant->delete();

        return response()->json(null, 204);
    }
}

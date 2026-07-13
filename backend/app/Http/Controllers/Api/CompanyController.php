<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('cnpj', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $companies = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json($companies);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'max:18', 'unique:companies'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'plan' => ['sometimes', 'string', 'in:basic,professional,enterprise'],
            'max_clients' => ['sometimes', 'integer', 'min:1'],
            'max_plants' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
        ]);

        $company = Company::create($validated);

        return response()->json(['data' => $company, 'message' => 'Empresa criada com sucesso.'], 201);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json([
            'data' => $company->loadCount(['users', 'plants']),
        ]);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'cnpj' => ['sometimes', 'string', 'max:18', 'unique:companies,cnpj,' . $company->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
            'plan' => ['sometimes', 'string', 'in:basic,professional,enterprise'],
            'max_clients' => ['sometimes', 'integer', 'min:1'],
            'max_plants' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
        ]);

        $company->update($validated);

        return response()->json(['data' => $company, 'message' => 'Empresa atualizada com sucesso.']);
    }

    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return response()->json(null, 204);
    }
}

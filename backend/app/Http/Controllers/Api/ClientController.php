<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::clients()->with('company');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('cpf_cnpj', 'ilike', "%{$search}%")
                  ->orWhere('uc_number', 'ilike', "%{$search}%");
            });
        }

        $clients = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json($clients);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'cpf_cnpj' => ['nullable', 'string', 'max:18'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip' => ['nullable', 'string', 'max:10'],
            'distributor' => ['nullable', 'string', 'max:100'],
            'uc_number' => ['nullable', 'string', 'max:30'],
            'meter_number' => ['nullable', 'string', 'max:30'],
            'equatorial_login' => ['nullable', 'string', 'max:100'],
            'equatorial_password' => ['nullable', 'string', 'max:100'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'client';
        $validated['company_id'] = $request->user()->company_id;

        $client = User::create($validated);
        $client->assignRole('client');

        return response()->json(['data' => $client, 'message' => 'Cliente criado com sucesso.'], 201);
    }

    public function show(User $client): JsonResponse
    {
        return response()->json([
            'data' => $client->load(['plants', 'invoices' => fn($q) => $q->latest('competence')->limit(6)]),
        ]);
    }

    public function update(Request $request, User $client): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $client->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'cpf_cnpj' => ['nullable', 'string', 'max:18'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip' => ['nullable', 'string', 'max:10'],
            'distributor' => ['nullable', 'string', 'max:100'],
            'uc_number' => ['nullable', 'string', 'max:30'],
            'meter_number' => ['nullable', 'string', 'max:30'],
            'equatorial_login' => ['nullable', 'string', 'max:100'],
            'equatorial_password' => ['nullable', 'string', 'max:100'],
        ]);

        $client->update($validated);

        return response()->json(['data' => $client, 'message' => 'Cliente atualizado com sucesso.']);
    }

    public function destroy(User $client): JsonResponse
    {
        $client->delete();

        return response()->json(null, 204);
    }
}

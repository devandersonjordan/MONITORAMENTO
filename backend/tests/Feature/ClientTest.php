<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->admin = User::factory()->for($this->company)->create(['role' => 'admin']);
    }

    public function test_can_list_clients(): void
    {
        User::factory()->for($this->company)->count(5)->client()->create();

        $response = $this->actingAs($this->admin)->getJson('/api/clients');

        $response->assertOk();
    }

    public function test_can_create_client(): void
    {
        $data = [
            'name' => 'João Silva',
            'email' => 'joao@test.com',
            'password' => 'password123',
            'phone' => '82999887766',
            'cpf_cnpj' => '12345678901',
            'uc_number' => '1234567',
            'distributor' => 'Equatorial Alagoas',
            'city' => 'Maceió',
            'state' => 'AL',
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/clients', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'João Silva');
    }

    public function test_can_search_clients(): void
    {
        User::factory()->for($this->company)->client()->create(['name' => 'Maria Santos']);
        User::factory()->for($this->company)->client()->create(['name' => 'João Oliveira']);

        $response = $this->actingAs($this->admin)->getJson('/api/clients?search=Maria');

        $response->assertOk();
    }

    public function test_clients_are_scoped_to_company(): void
    {
        $otherCompany = Company::factory()->create();
        User::factory()->for($otherCompany)->client()->create(['name' => 'Cliente Outra Empresa']);

        $response = $this->actingAs($this->admin)->getJson('/api/clients');

        $response->assertOk();
        $clients = collect($response->json('data'));
        $this->assertTrue($clients->every(fn($c) => $c['company_id'] === $this->company->id));
    }
}

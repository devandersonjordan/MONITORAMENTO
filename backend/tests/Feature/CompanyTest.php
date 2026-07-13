<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
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

    public function test_admin_can_list_companies(): void
    {
        Company::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->getJson('/api/companies');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_admin_can_create_company(): void
    {
        $data = [
            'name' => 'Nova Empresa Solar',
            'cnpj' => '12345678000199',
            'email' => 'contato@novaempresa.com',
            'phone' => '82999999999',
            'plan' => 'professional',
            'max_clients' => 100,
            'max_plants' => 200,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/companies', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Nova Empresa Solar');

        $this->assertDatabaseHas('companies', ['cnpj' => '12345678000199']);
    }

    public function test_admin_can_update_company(): void
    {
        $response = $this->actingAs($this->admin)->putJson("/api/companies/{$this->company->id}", [
            'name' => 'Empresa Atualizada',
            'cnpj' => $this->company->cnpj,
            'email' => $this->company->email,
            'plan' => 'enterprise',
            'max_clients' => 500,
            'max_plants' => 1000,
            'status' => 'active',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Empresa Atualizada');
    }

    public function test_admin_can_delete_company(): void
    {
        $company = Company::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("/api/companies/{$company->id}");

        $response->assertOk();
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }
}

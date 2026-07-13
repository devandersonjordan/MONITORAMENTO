<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->admin = User::factory()->for($this->company)->create(['role' => 'admin']);
        $this->client = User::factory()->for($this->company)->client()->create();
    }

    public function test_can_list_plants(): void
    {
        Plant::factory()->for($this->company)->count(3)->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->admin)->getJson('/api/plants');

        $response->assertOk();
    }

    public function test_can_create_plant(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'name' => 'Usina Solar Teste',
            'power_kwp' => 10.5,
            'installation_date' => '2024-01-15',
            'module_model' => 'Canadian Solar 550W',
            'module_qty' => 20,
            'inverter_model' => 'GoodWe 10kW',
            'inverter_power_kw' => 10,
            'latitude' => -9.6498,
            'longitude' => -35.7089,
            'address' => 'Rua Teste, 123',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/plants', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Usina Solar Teste');
    }

    public function test_can_update_plant(): void
    {
        $plant = Plant::factory()->for($this->company)->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->admin)->putJson("/api/plants/{$plant->id}", [
            'client_id' => $this->client->id,
            'name' => 'Usina Atualizada',
            'power_kwp' => 15.0,
            'status' => 'active',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Usina Atualizada');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Inverter;
use App\Models\InverterReading;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_stats(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => 'admin']);
        $client = User::factory()->for($company)->client()->create();
        $plant = Plant::factory()->for($company)->create(['client_id' => $client->id]);
        $inverter = Inverter::factory()->for($company)->create(['plant_id' => $plant->id]);

        InverterReading::factory()->count(5)->create([
            'inverter_id' => $inverter->id,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_clients',
                    'total_plants',
                    'energy_today_kwh',
                    'energy_month_kwh',
                    'energy_year_kwh',
                ],
            ]);
    }
}

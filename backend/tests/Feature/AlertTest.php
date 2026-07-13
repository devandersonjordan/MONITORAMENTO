<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Inverter;
use App\Models\InverterAlert;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertTest extends TestCase
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

    public function test_can_list_alerts(): void
    {
        $plant = Plant::factory()->for($this->company)->create();
        $inverter = Inverter::factory()->for($this->company)->create(['plant_id' => $plant->id]);
        InverterAlert::factory()->count(3)->create([
            'inverter_id' => $inverter->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/alerts');

        $response->assertOk();
    }

    public function test_can_resolve_alert(): void
    {
        $plant = Plant::factory()->for($this->company)->create();
        $inverter = Inverter::factory()->for($this->company)->create(['plant_id' => $plant->id]);
        $alert = InverterAlert::factory()->create([
            'inverter_id' => $inverter->id,
            'company_id' => $this->company->id,
            'resolved_at' => null,
        ]);

        $response = $this->actingAs($this->admin)->patchJson("/api/alerts/{$alert->id}/resolve");

        $response->assertOk();
        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_can_get_alert_stats(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/alerts/stats');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['total_unresolved', 'by_severity', 'by_type']]);
    }
}

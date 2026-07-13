<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Inverter;
use App\Models\InverterReading;
use App\Models\Plant;
use App\Services\AnomalyDetectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnomalyDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_no_communication(): void
    {
        $company = Company::factory()->create();
        $plant = Plant::factory()->for($company)->create();
        $inverter = Inverter::factory()->for($company)->create(['plant_id' => $plant->id]);

        $detector = new AnomalyDetectorService();
        $anomalies = $detector->detectInverterAnomalies($inverter);

        $this->assertNotEmpty($anomalies);
        $this->assertEquals('no_communication', $anomalies[0]->type);
        $this->assertEquals('critical', $anomalies[0]->severity);
    }

    public function test_detects_high_temperature(): void
    {
        $company = Company::factory()->create();
        $plant = Plant::factory()->for($company)->create();
        $inverter = Inverter::factory()->for($company)->create(['plant_id' => $plant->id]);

        InverterReading::factory()->create([
            'inverter_id' => $inverter->id,
            'recorded_at' => now(),
            'temperature_c' => 80,
            'power_w' => 5000,
        ]);

        $detector = new AnomalyDetectorService();
        $anomalies = $detector->detectInverterAnomalies($inverter);

        $hasTemp = collect($anomalies)->contains(fn($a) => $a->type === 'high_temperature');
        $this->assertTrue($hasTemp);
    }

    public function test_no_anomalies_when_normal(): void
    {
        $company = Company::factory()->create();
        $plant = Plant::factory()->for($company)->create();
        $inverter = Inverter::factory()->for($company)->create(['plant_id' => $plant->id]);

        InverterReading::factory()->count(10)->create([
            'inverter_id' => $inverter->id,
            'recorded_at' => now(),
            'temperature_c' => 45,
            'power_w' => 5000,
            'efficiency_pct' => 85,
        ]);

        $detector = new AnomalyDetectorService();
        $anomalies = $detector->detectInverterAnomalies($inverter);

        $hasNoComm = collect($anomalies)->contains(fn($a) => $a->type === 'no_communication');
        $this->assertFalse($hasNoComm);
    }
}

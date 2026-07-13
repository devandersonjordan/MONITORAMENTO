<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Inverter;
use App\Models\InverterReading;
use App\Models\Invoice;
use App\Models\Plant;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_monthly_report_with_correct_data(): void
    {
        $company = Company::factory()->create();
        $client = User::factory()->for($company)->client()->create();
        $plant = Plant::factory()->for($company)->create([
            'client_id' => $client->id,
            'power_kwp' => 10,
        ]);
        $inverter = Inverter::factory()->for($company)->create(['plant_id' => $plant->id]);

        $month = now()->format('Y-m');

        InverterReading::factory()->count(10)->create([
            'inverter_id' => $inverter->id,
            'recorded_at' => now()->startOfMonth()->addDays(rand(0, 27)),
            'daily_kwh' => 35,
        ]);

        Invoice::factory()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'competence' => now()->startOfMonth(),
            'consumption_kwh' => 300,
            'injected_kwh' => 200,
            'compensated_kwh' => 180,
        ]);

        $service = new ReportService();
        $report = $service->generateMonthlyReport($client, $month);

        $this->assertEquals('monthly', $report->type);
        $this->assertEquals($client->id, $report->client_id);
        $this->assertIsArray($report->data);
        $this->assertArrayHasKey('production', $report->data);
        $this->assertArrayHasKey('consumption', $report->data);
        $this->assertArrayHasKey('financial', $report->data);
        $this->assertArrayHasKey('environmental', $report->data);
    }
}

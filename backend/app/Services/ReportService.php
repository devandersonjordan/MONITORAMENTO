<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inverter;
use App\Models\InverterReading;
use App\Models\Invoice;
use App\Models\Plant;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReportService
{
    public function generateMonthlyReport(User $client, string $month): Report
    {
        $periodStart = Carbon::parse($month)->startOfMonth();
        $periodEnd = Carbon::parse($month)->endOfMonth();

        $plants = Plant::where('client_id', $client->id)->with('inverters')->get();

        $inverterIds = $plants->pluck('inverters')->flatten()->pluck('id');

        $readings = InverterReading::whereIn('inverter_id', $inverterIds)
            ->whereBetween('recorded_at', [$periodStart, $periodEnd])
            ->get();

        $invoice = Invoice::where('client_id', $client->id)
            ->where('competence', $periodStart->format('Y-m-d'))
            ->first();

        $totalProduced = $readings->max('monthly_kwh') ?? $readings->sum('daily_kwh');
        $totalCapacity = $plants->sum('power_kwp');
        $daysInMonth = $periodStart->daysInMonth;
        $peakSunHours = 5.2; // Média Alagoas

        $expectedProduction = $totalCapacity * $peakSunHours * $daysInMonth;
        $performanceRatio = $expectedProduction > 0 ? ($totalProduced / $expectedProduction) * 100 : 0;

        $consumption = $invoice?->consumption_kwh ?? 0;
        $injected = $invoice?->injected_kwh ?? 0;
        $compensated = $invoice?->compensated_kwh ?? 0;
        $autoConsumption = $totalProduced > 0 ? max(0, $totalProduced - $injected) : 0;
        $autoConsumptionPct = $totalProduced > 0 ? ($autoConsumption / $totalProduced) * 100 : 0;

        $tariff = $invoice?->tariff ?? 0.85;
        $savings = $compensated * $tariff;
        $co2Avoided = $totalProduced * 0.0817; // kg CO2/kWh grid Brasil
        $treesEquivalent = $co2Avoided / 22; // ~22kg CO2/árvore/ano ÷ 12 meses

        $previousMonth = $periodStart->copy()->subMonth()->format('Y-m-d');
        $previousInvoice = Invoice::where('client_id', $client->id)
            ->where('competence', $previousMonth)
            ->first();

        $previousReadings = InverterReading::whereIn('inverter_id', $inverterIds)
            ->whereBetween('recorded_at', [
                $periodStart->copy()->subMonth(),
                $periodEnd->copy()->subMonth(),
            ])
            ->get();
        $previousProduced = $previousReadings->max('monthly_kwh') ?? $previousReadings->sum('daily_kwh');

        $data = [
            'client' => ['id' => $client->id, 'name' => $client->name, 'uc' => $client->uc_number],
            'period' => ['start' => $periodStart->format('Y-m-d'), 'end' => $periodEnd->format('Y-m-d'), 'month_label' => $periodStart->translatedFormat('F Y')],
            'plants' => $plants->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'power_kwp' => $p->power_kwp])->toArray(),
            'production' => [
                'total_kwh' => round($totalProduced, 2),
                'expected_kwh' => round($expectedProduction, 2),
                'performance_ratio' => round($performanceRatio, 1),
                'previous_month_kwh' => round($previousProduced, 2),
                'variation_pct' => $previousProduced > 0 ? round((($totalProduced - $previousProduced) / $previousProduced) * 100, 1) : 0,
            ],
            'consumption' => [
                'total_kwh' => round($consumption, 2),
                'auto_consumption_kwh' => round($autoConsumption, 2),
                'auto_consumption_pct' => round($autoConsumptionPct, 1),
                'from_grid_kwh' => round(max(0, $consumption - $autoConsumption), 2),
            ],
            'energy_balance' => [
                'injected_kwh' => round($injected, 2),
                'compensated_kwh' => round($compensated, 2),
                'previous_balance_kwh' => round($invoice?->previous_balance_kwh ?? 0, 2),
                'current_balance_kwh' => round($invoice?->current_balance_kwh ?? 0, 2),
                'credits_received_kwh' => round($invoice?->credits_received_kwh ?? 0, 2),
                'credits_used_kwh' => round($invoice?->credits_used_kwh ?? 0, 2),
            ],
            'financial' => [
                'invoice_amount_brl' => $invoice ? round($invoice->amount_cents / 100, 2) : 0,
                'savings_brl' => round($savings, 2),
                'tariff' => round($tariff, 6),
                'flag' => $invoice?->flag,
                'icms' => round($invoice?->icms_value ?? 0, 2),
                'pis' => round($invoice?->pis_value ?? 0, 2),
                'cofins' => round($invoice?->cofins_value ?? 0, 2),
                'public_lighting' => round($invoice?->public_lighting_value ?? 0, 2),
            ],
            'environmental' => [
                'co2_avoided_kg' => round($co2Avoided, 1),
                'trees_equivalent' => round($treesEquivalent, 1),
            ],
            'efficiency' => [
                'plant_efficiency_pct' => round($performanceRatio, 1),
                'best_day_kwh' => round($readings->groupBy(fn($r) => $r->recorded_at->format('Y-m-d'))->map->max('daily_kwh')->max() ?? 0, 2),
                'worst_day_kwh' => round($readings->groupBy(fn($r) => $r->recorded_at->format('Y-m-d'))->map->max('daily_kwh')->filter()->min() ?? 0, 2),
                'avg_daily_kwh' => round($totalProduced / max(1, $daysInMonth), 2),
            ],
        ];

        return Report::create([
            'client_id' => $client->id,
            'plant_id' => $plants->first()?->id,
            'company_id' => $client->company_id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'type' => 'monthly',
            'data' => $data,
        ]);
    }
}

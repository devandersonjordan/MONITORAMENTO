<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inverter;
use App\Models\InverterAlert;
use App\Models\InverterReading;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AnomalyDetectorService
{
    public function detectInverterAnomalies(Inverter $inverter): array
    {
        $anomalies = [];

        $recent = InverterReading::where('inverter_id', $inverter->id)
            ->where('recorded_at', '>=', Carbon::now()->subHours(24))
            ->orderByDesc('recorded_at')
            ->get();

        if ($recent->isEmpty()) {
            $anomalies[] = $this->createAlert($inverter, 'no_communication', 'critical', 'Sem comunicação nas últimas 24 horas');
            return $anomalies;
        }

        $latest = $recent->first();
        $hour = (int) $latest->recorded_at->format('H');

        if ($hour >= 8 && $hour <= 17 && $latest->power_w < ($inverter->rated_power_w ?? 1000) * 0.05) {
            $anomalies[] = $this->createAlert($inverter, 'low_production', 'warning', "Produção muito baixa durante horário solar: {$latest->power_w}W");
        }

        if ($latest->temperature_c && $latest->temperature_c > 75) {
            $anomalies[] = $this->createAlert($inverter, 'high_temperature', 'critical', "Temperatura elevada: {$latest->temperature_c}°C (limite: 75°C)");
        }

        if ($latest->efficiency_pct && $latest->efficiency_pct < 60) {
            $anomalies[] = $this->createAlert($inverter, 'low_efficiency', 'warning', "Eficiência baixa: {$latest->efficiency_pct}% (mínimo: 60%)");
        }

        $avg7Days = InverterReading::where('inverter_id', $inverter->id)
            ->where('recorded_at', '>=', Carbon::now()->subDays(7))
            ->avg('daily_kwh');

        $avg30Days = InverterReading::where('inverter_id', $inverter->id)
            ->where('recorded_at', '>=', Carbon::now()->subDays(30))
            ->avg('daily_kwh');

        if ($avg30Days > 0 && $avg7Days < $avg30Days * 0.6) {
            $drop = round((1 - $avg7Days / $avg30Days) * 100, 1);
            $anomalies[] = $this->createAlert($inverter, 'production_drop', 'warning', "Queda de {$drop}% na produção dos últimos 7 dias vs média mensal");
        }

        $voltageReadings = $recent->where('voltage_v', '>', 0);
        if ($voltageReadings->isNotEmpty()) {
            $avgVoltage = $voltageReadings->avg('voltage_v');
            $voltageVariation = $voltageReadings->max('voltage_v') - $voltageReadings->min('voltage_v');
            if ($avgVoltage > 0 && ($voltageVariation / $avgVoltage) > 0.15) {
                $anomalies[] = $this->createAlert($inverter, 'voltage_instability', 'warning', "Variação de tensão instável: {$voltageVariation}V nas últimas 24h");
            }
        }

        return $anomalies;
    }

    public function detectBillingAnomalies(Invoice $invoice): array
    {
        $anomalies = [];

        $previousInvoices = Invoice::where('client_id', $invoice->client_id)
            ->where('competence', '<', $invoice->competence)
            ->orderByDesc('competence')
            ->limit(6)
            ->get();

        if ($previousInvoices->isEmpty()) return [];

        $avgConsumption = $previousInvoices->avg('consumption_kwh');
        if ($avgConsumption > 0 && $invoice->consumption_kwh > $avgConsumption * 1.5) {
            $increase = round(($invoice->consumption_kwh / $avgConsumption - 1) * 100, 1);
            $anomalies[] = [
                'type' => 'consumption_spike',
                'severity' => 'warning',
                'message' => "Consumo {$increase}% acima da média dos últimos 6 meses",
            ];
        }

        $avgAmount = $previousInvoices->avg('amount_cents');
        if ($avgAmount > 0 && $invoice->amount_cents > $avgAmount * 1.5) {
            $increase = round(($invoice->amount_cents / $avgAmount - 1) * 100, 1);
            $anomalies[] = [
                'type' => 'bill_spike',
                'severity' => 'warning',
                'message' => "Valor da fatura {$increase}% acima da média",
            ];
        }

        if ($invoice->injected_kwh > 0 && $invoice->compensated_kwh < $invoice->injected_kwh * 0.5) {
            $anomalies[] = [
                'type' => 'low_compensation',
                'severity' => 'info',
                'message' => 'Compensação de energia abaixo de 50% do injetado — verificar saldo de créditos',
            ];
        }

        return $anomalies;
    }

    private function createAlert(Inverter $inverter, string $type, string $severity, string $message): InverterAlert
    {
        $existing = InverterAlert::where('inverter_id', $inverter->id)
            ->where('type', $type)
            ->whereNull('resolved_at')
            ->first();

        if ($existing) return $existing;

        return InverterAlert::create([
            'inverter_id' => $inverter->id,
            'company_id' => $inverter->company_id,
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
            'data' => ['detected_at' => now()->toIso8601String()],
        ]);
    }
}

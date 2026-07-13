<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Integrations\Inverters\InverterAdapterFactory;
use App\Models\Inverter;
use App\Models\InverterAlert;
use App\Models\InverterReading;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncInverterDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly ?int $inverterId = null,
    ) {}

    public function handle(): void
    {
        $query = Inverter::query()->whereNotNull('api_credentials');

        if ($this->inverterId) {
            $query->where('id', $this->inverterId);
        }

        $inverters = $query->get();

        foreach ($inverters as $inverter) {
            $this->syncInverter($inverter);
        }
    }

    private function syncInverter(Inverter $inverter): void
    {
        try {
            $adapter = InverterAdapterFactory::make($inverter->brand);
            $data = $adapter->fetchRealtimeData($inverter);

            if (!$data) {
                $this->handleOffline($inverter);
                return;
            }

            InverterReading::create([
                'inverter_id' => $inverter->id,
                'recorded_at' => Carbon::now(),
                ...$data->toArray(),
            ]);

            $newStatus = $data->status === 'fault' ? 'warning' : ($data->power_w > 0 ? 'online' : 'online');
            $inverter->update([
                'status' => $newStatus,
                'last_communication_at' => now(),
            ]);

            $this->checkAnomalies($inverter, $data);

            $alarms = $adapter->getAlarms($inverter);
            foreach ($alarms as $alarm) {
                InverterAlert::firstOrCreate(
                    [
                        'inverter_id' => $inverter->id,
                        'type' => $alarm['type'],
                        'resolved_at' => null,
                    ],
                    [
                        'company_id' => $inverter->company_id,
                        'severity' => $alarm['severity'],
                        'message' => $alarm['message'],
                        'data' => $alarm['data'] ?? null,
                    ]
                );
            }

            Log::info("Inverter {$inverter->id} ({$inverter->brand}) synced: {$data->power_w}W");
        } catch (\Throwable $e) {
            Log::error("Sync failed for inverter {$inverter->id}: {$e->getMessage()}");
            $this->handleOffline($inverter);
        }
    }

    private function handleOffline(Inverter $inverter): void
    {
        if ($inverter->status !== 'offline') {
            $inverter->update(['status' => 'offline']);

            InverterAlert::create([
                'inverter_id' => $inverter->id,
                'company_id' => $inverter->company_id,
                'type' => 'offline',
                'severity' => 'critical',
                'message' => "Inversor {$inverter->serial_number} sem comunicação",
                'data' => ['last_communication' => $inverter->last_communication_at?->toIso8601String()],
            ]);
        }
    }

    private function checkAnomalies(Inverter $inverter, $data): void
    {
        $hour = (int) now()->format('H');
        $isSunHours = $hour >= 7 && $hour <= 17;

        if ($isSunHours && $data->power_w < 100 && $data->power_w >= 0) {
            InverterAlert::firstOrCreate(
                ['inverter_id' => $inverter->id, 'type' => 'low_production', 'resolved_at' => null],
                [
                    'company_id' => $inverter->company_id,
                    'severity' => 'warning',
                    'message' => 'Produção muito baixa durante horário solar',
                    'data' => ['power_w' => $data->power_w, 'hour' => $hour],
                ]
            );
        }

        if ($data->temperature_c > 75) {
            InverterAlert::firstOrCreate(
                ['inverter_id' => $inverter->id, 'type' => 'high_temperature', 'resolved_at' => null],
                [
                    'company_id' => $inverter->company_id,
                    'severity' => 'critical',
                    'message' => "Temperatura crítica: {$data->temperature_c}°C",
                    'data' => ['temperature' => $data->temperature_c],
                ]
            );
        }

        if ($data->efficiency_pct > 0 && $data->efficiency_pct < 60) {
            InverterAlert::firstOrCreate(
                ['inverter_id' => $inverter->id, 'type' => 'efficiency_drop', 'resolved_at' => null],
                [
                    'company_id' => $inverter->company_id,
                    'severity' => 'warning',
                    'message' => "Eficiência baixa: {$data->efficiency_pct}%",
                    'data' => ['efficiency' => $data->efficiency_pct],
                ]
            );
        }
    }
}

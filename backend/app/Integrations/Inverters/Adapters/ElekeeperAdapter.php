<?php

declare(strict_types=1);

namespace App\Integrations\Inverters\Adapters;

use App\Integrations\Inverters\InverterDataDTO;
use App\Models\Inverter;

class ElekeeperAdapter extends AbstractInverterAdapter
{
    public static function brand(): string
    {
        return 'elekeeper';
    }

    protected function baseUrl(): string
    {
        return 'https://api.elekeeper.com/v1';
    }

    protected function loginEndpoint(): string
    {
        return '/auth/login';
    }

    protected function realtimeEndpoint(Inverter $inverter): string
    {
        $stationId = $inverter->api_credentials['station_id'] ?? '';
        return "/station/{$stationId}/realtime";
    }

    protected function parseRealtimeResponse(array $response): InverterDataDTO
    {
        $data = $response['data'] ?? $response;

        return new InverterDataDTO(
            power_w: (float) ($data['pac'] ?? $data['power'] ?? 0),
            voltage_v: (float) ($data['vpv1'] ?? $data['voltage'] ?? 0),
            current_a: (float) ($data['ipv1'] ?? $data['current'] ?? 0),
            frequency_hz: (float) ($data['fac'] ?? $data['frequency'] ?? 0),
            temperature_c: (float) ($data['temperature'] ?? 0),
            daily_kwh: (float) ($data['eDay'] ?? $data['daily_energy'] ?? 0),
            monthly_kwh: (float) ($data['eMonth'] ?? $data['monthly_energy'] ?? 0),
            yearly_kwh: (float) ($data['eYear'] ?? $data['yearly_energy'] ?? 0),
            total_kwh: (float) ($data['eTotal'] ?? $data['total_energy'] ?? 0),
            efficiency_pct: (float) ($data['efficiency'] ?? 0),
            status: $this->mapStatus($data['status'] ?? -1),
        );
    }

    protected function parseAlarmsResponse(array $response): array
    {
        $alarms = [];
        foreach (($response['data'] ?? []) as $alarm) {
            $alarms[] = [
                'type' => $alarm['alarmCode'] ?? 'unknown',
                'severity' => $this->mapSeverity($alarm['level'] ?? 0),
                'message' => $alarm['alarmName'] ?? $alarm['message'] ?? 'Alarme detectado',
                'data' => $alarm,
            ];
        }
        return $alarms;
    }

    private function mapStatus(int|string $status): string
    {
        return match ((int) $status) {
            1 => 'normal',
            2 => 'warning',
            3 => 'fault',
            default => 'offline',
        };
    }

    private function mapSeverity(int $level): string
    {
        return match ($level) {
            1 => 'info',
            2 => 'warning',
            3, 4 => 'critical',
            default => 'info',
        };
    }
}

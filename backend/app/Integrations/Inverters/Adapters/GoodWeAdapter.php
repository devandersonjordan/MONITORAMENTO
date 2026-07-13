<?php

declare(strict_types=1);

namespace App\Integrations\Inverters\Adapters;

use App\Integrations\Inverters\InverterDataDTO;
use App\Models\Inverter;

class GoodWeAdapter extends AbstractInverterAdapter
{
    public static function brand(): string
    {
        return 'goodwe';
    }

    protected function baseUrl(): string
    {
        return 'https://semsportal.com/api/v2';
    }

    protected function loginEndpoint(): string
    {
        return '/Common/CrossLogin';
    }

    protected function realtimeEndpoint(Inverter $inverter): string
    {
        $stationId = $inverter->api_credentials['station_id'] ?? '';
        return "/PowerStation/GetMonitorDetailByPowerstationId?powerstationId={$stationId}";
    }

    protected function buildLoginPayload(array $credentials): array
    {
        return [
            'account' => $credentials['username'] ?? '',
            'pwd' => $credentials['password'] ?? '',
        ];
    }

    protected function extractToken(array $response): ?string
    {
        return $response['data']['token'] ?? $response['data'] ?? null;
    }

    protected function parseRealtimeResponse(array $response): InverterDataDTO
    {
        $inverter = $response['data']['inverter'][0] ?? $response['data'] ?? [];
        $kpi = $response['data']['kpi'] ?? [];

        return new InverterDataDTO(
            power_w: (float) ($inverter['out_pac'] ?? $inverter['pac'] ?? 0),
            voltage_v: (float) ($inverter['vpv1'] ?? 0),
            current_a: (float) ($inverter['ipv1'] ?? 0),
            frequency_hz: (float) ($inverter['fac1'] ?? 0),
            temperature_c: (float) ($inverter['tempperature'] ?? 0),
            daily_kwh: (float) ($kpi['power'] ?? $inverter['eday'] ?? 0),
            monthly_kwh: (float) ($kpi['month_generation'] ?? $inverter['emonth'] ?? 0),
            yearly_kwh: (float) ($kpi['year_generation'] ?? 0),
            total_kwh: (float) ($kpi['total_power'] ?? $inverter['etotal'] ?? 0),
            efficiency_pct: (float) ($inverter['efficiency'] ?? 0),
            status: $this->mapStatus($inverter['status'] ?? -1),
        );
    }

    protected function parseAlarmsResponse(array $response): array
    {
        $alarms = [];
        foreach (($response['data']['list'] ?? []) as $alarm) {
            $alarms[] = [
                'type' => $alarm['warningCode'] ?? 'unknown',
                'severity' => $this->mapSeverity($alarm['warningLevel'] ?? 0),
                'message' => $alarm['warningName'] ?? 'Alarme GoodWe',
                'data' => $alarm,
            ];
        }
        return $alarms;
    }

    private function mapStatus(int|string $status): string
    {
        return match ((int) $status) {
            0 => 'offline',
            1 => 'normal',
            2 => 'warning',
            default => 'offline',
        };
    }

    private function mapSeverity(int $level): string
    {
        return match ($level) {
            1 => 'info',
            2 => 'warning',
            3 => 'critical',
            default => 'info',
        };
    }
}

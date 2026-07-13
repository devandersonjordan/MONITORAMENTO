<?php

declare(strict_types=1);

namespace App\Integrations\Inverters\Adapters;

use App\Integrations\Inverters\InverterDataDTO;
use App\Models\Inverter;

class SungrowAdapter extends AbstractInverterAdapter
{
    public static function brand(): string
    {
        return 'sungrow';
    }

    protected function baseUrl(): string
    {
        return 'https://gateway.isolarcloud.com/v1';
    }

    protected function loginEndpoint(): string
    {
        return '/userService/login';
    }

    protected function realtimeEndpoint(Inverter $inverter): string
    {
        $stationId = $inverter->api_credentials['station_id'] ?? '';
        return "/devService/queryDeviceRealTimeData?ps_id={$stationId}";
    }

    protected function buildLoginPayload(array $credentials): array
    {
        return [
            'user_account' => $credentials['username'] ?? '',
            'user_password' => $credentials['password'] ?? '',
            'appkey' => $credentials['app_key'] ?? '',
        ];
    }

    protected function extractToken(array $response): ?string
    {
        return $response['result_data']['token'] ?? null;
    }

    protected function parseRealtimeResponse(array $response): InverterDataDTO
    {
        $data = $response['result_data']['page_list'][0] ?? $response['result_data'] ?? [];

        return new InverterDataDTO(
            power_w: (float) ($data['p_ac'] ?? $data['total_active_power'] ?? 0) * 1000,
            voltage_v: (float) ($data['pv_voltage'] ?? $data['mppt1_voltage'] ?? 0),
            current_a: (float) ($data['pv_current'] ?? $data['mppt1_current'] ?? 0),
            frequency_hz: (float) ($data['grid_frequency'] ?? 0),
            temperature_c: (float) ($data['inverter_temperature'] ?? 0),
            daily_kwh: (float) ($data['today_energy'] ?? 0),
            monthly_kwh: (float) ($data['month_energy'] ?? 0),
            yearly_kwh: (float) ($data['year_energy'] ?? 0),
            total_kwh: (float) ($data['total_energy'] ?? 0),
            efficiency_pct: (float) ($data['efficiency'] ?? 0),
            status: $this->mapStatus($data['device_status'] ?? -1),
        );
    }

    protected function parseAlarmsResponse(array $response): array
    {
        $alarms = [];
        foreach (($response['result_data']['page_list'] ?? []) as $alarm) {
            $alarms[] = [
                'type' => $alarm['fault_code'] ?? 'unknown',
                'severity' => $this->mapSeverity((int) ($alarm['fault_level'] ?? 0)),
                'message' => $alarm['fault_name'] ?? 'Alarme Sungrow',
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

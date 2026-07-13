<?php

declare(strict_types=1);

namespace App\Integrations\Inverters\Adapters;

use App\Integrations\Inverters\InverterDataDTO;
use App\Models\Inverter;

class DeyeAdapter extends AbstractInverterAdapter
{
    public static function brand(): string
    {
        return 'deye';
    }

    protected function baseUrl(): string
    {
        return 'https://api.solarmanpv.com';
    }

    protected function loginEndpoint(): string
    {
        return '/account/v1.0/token';
    }

    protected function realtimeEndpoint(Inverter $inverter): string
    {
        return '/device/v1.0/currentData';
    }

    protected function buildLoginPayload(array $credentials): array
    {
        return [
            'appSecret' => $credentials['app_secret'] ?? '',
            'email' => $credentials['username'] ?? '',
            'password' => $credentials['password'] ?? '',
        ];
    }

    protected function extractToken(array $response): ?string
    {
        return $response['access_token'] ?? null;
    }

    public function fetchRealtimeData(Inverter $inverter): ?InverterDataDTO
    {
        try {
            $creds = $inverter->api_credentials;
            if (!$this->token && !$this->authenticate($creds)) {
                return null;
            }

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withToken($this->token)
                ->post($this->baseUrl() . $this->realtimeEndpoint($inverter), [
                    'deviceSn' => $inverter->serial_number,
                ]);

            if ($response->successful()) {
                return $this->parseRealtimeResponse($response->json());
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("deye realtime fetch failed for inverter {$inverter->id}: {$e->getMessage()}");
        }

        return null;
    }

    protected function parseRealtimeResponse(array $response): InverterDataDTO
    {
        $dataList = $response['dataList'] ?? [];
        $mapped = [];
        foreach ($dataList as $item) {
            $mapped[$item['key'] ?? ''] = (float) ($item['value'] ?? 0);
        }

        return new InverterDataDTO(
            power_w: $mapped['APo_t1'] ?? $mapped['DPi_t1'] ?? 0,
            voltage_v: $mapped['PV_Voltage1'] ?? $mapped['PV1_Voltage'] ?? 0,
            current_a: $mapped['PV_Current1'] ?? $mapped['PV1_Current'] ?? 0,
            frequency_hz: $mapped['B_AC_Frequency'] ?? 0,
            temperature_c: $mapped['INV_Temperature'] ?? $mapped['DC_Temperature'] ?? 0,
            daily_kwh: $mapped['Etdy_ge1'] ?? $mapped['Et_ge0'] ?? 0,
            monthly_kwh: $mapped['Emon_ge1'] ?? 0,
            yearly_kwh: $mapped['Eyr_ge1'] ?? 0,
            total_kwh: $mapped['Etotal_ge1'] ?? $mapped['Et_ge1'] ?? 0,
            efficiency_pct: $mapped['INV_Efficiency'] ?? 0,
            status: isset($mapped['INV_Status']) ? $this->mapStatus((int) $mapped['INV_Status']) : 'offline',
        );
    }

    protected function parseAlarmsResponse(array $response): array
    {
        $alarms = [];
        foreach (($response['stationDataItems'] ?? []) as $alarm) {
            $alarms[] = [
                'type' => $alarm['alarmCode'] ?? 'unknown',
                'severity' => 'warning',
                'message' => $alarm['alarmMessage'] ?? 'Alarme Deye',
                'data' => $alarm,
            ];
        }
        return $alarms;
    }

    private function mapStatus(int $status): string
    {
        return match ($status) {
            1 => 'normal',
            2 => 'warning',
            3 => 'fault',
            default => 'offline',
        };
    }
}

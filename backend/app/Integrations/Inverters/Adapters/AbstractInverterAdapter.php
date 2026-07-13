<?php

declare(strict_types=1);

namespace App\Integrations\Inverters\Adapters;

use App\Integrations\Inverters\InverterAdapterInterface;
use App\Integrations\Inverters\InverterDataDTO;
use App\Models\Inverter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractInverterAdapter implements InverterAdapterInterface
{
    protected ?string $token = null;
    protected array $credentials = [];

    abstract protected function baseUrl(): string;

    abstract protected function loginEndpoint(): string;

    abstract protected function realtimeEndpoint(Inverter $inverter): string;

    abstract protected function parseRealtimeResponse(array $response): InverterDataDTO;

    abstract protected function parseAlarmsResponse(array $response): array;

    public function authenticate(array $credentials): bool
    {
        $this->credentials = $credentials;

        try {
            $response = Http::timeout(30)
                ->post($this->baseUrl() . $this->loginEndpoint(), $this->buildLoginPayload($credentials));

            if ($response->successful()) {
                $this->token = $this->extractToken($response->json());
                return $this->token !== null;
            }
        } catch (\Throwable $e) {
            Log::warning(static::brand() . " auth failed: {$e->getMessage()}");
        }

        return false;
    }

    public function fetchRealtimeData(Inverter $inverter): ?InverterDataDTO
    {
        try {
            $creds = $inverter->api_credentials;
            if (!$this->token && !$this->authenticate($creds)) {
                return null;
            }

            $response = Http::timeout(30)
                ->withToken($this->token)
                ->get($this->baseUrl() . $this->realtimeEndpoint($inverter));

            if ($response->successful()) {
                return $this->parseRealtimeResponse($response->json());
            }
        } catch (\Throwable $e) {
            Log::warning(static::brand() . " realtime fetch failed for inverter {$inverter->id}: {$e->getMessage()}");
        }

        return null;
    }

    public function fetchDailyHistory(Inverter $inverter, string $date): array
    {
        return [];
    }

    public function getStatus(Inverter $inverter): string
    {
        $data = $this->fetchRealtimeData($inverter);
        return $data?->status ?? 'offline';
    }

    public function getAlarms(Inverter $inverter): array
    {
        return [];
    }

    protected function buildLoginPayload(array $credentials): array
    {
        return [
            'username' => $credentials['username'] ?? '',
            'password' => $credentials['password'] ?? '',
        ];
    }

    protected function extractToken(array $response): ?string
    {
        return $response['data']['token'] ?? $response['token'] ?? $response['access_token'] ?? null;
    }
}

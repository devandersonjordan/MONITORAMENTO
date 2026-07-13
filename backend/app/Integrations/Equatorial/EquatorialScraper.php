<?php

declare(strict_types=1);

namespace App\Integrations\Equatorial;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EquatorialScraper
{
    private const BASE_URL = 'https://agenciavirtual.equatorialenergia.com.br';
    private ?string $sessionToken = null;

    public function authenticate(string $login, string $password): bool
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'application/json',
            ])->post(self::BASE_URL . '/api/auth/login', [
                'login' => $login,
                'senha' => $password,
                'tipo' => 'CPF',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->sessionToken = $data['token'] ?? $data['data']['token'] ?? null;
                return $this->sessionToken !== null;
            }

            Log::warning('Equatorial auth failed', [
                'status' => $response->status(),
                'login' => substr($login, 0, 3) . '***',
            ]);
        } catch (\Throwable $e) {
            Log::error('Equatorial auth error: ' . $e->getMessage());
        }

        return false;
    }

    public function listInvoices(string $ucNumber): array
    {
        if (!$this->sessionToken) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->sessionToken}",
                'Accept' => 'application/json',
            ])->get(self::BASE_URL . '/api/faturas/listar', [
                'unidade_consumidora' => $ucNumber,
            ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? $response->json()['faturas'] ?? [];
            }
        } catch (\Throwable $e) {
            Log::error("Equatorial list invoices error for UC {$ucNumber}: {$e->getMessage()}");
        }

        return [];
    }

    public function downloadInvoicePdf(string $invoiceId, string $ucNumber): ?string
    {
        if (!$this->sessionToken) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->sessionToken}",
            ])->get(self::BASE_URL . '/api/faturas/download', [
                'fatura_id' => $invoiceId,
                'unidade_consumidora' => $ucNumber,
            ]);

            if ($response->successful() && $response->header('Content-Type') === 'application/pdf') {
                $filename = "invoices/{$ucNumber}/{$invoiceId}.pdf";
                Storage::disk('local')->put($filename, $response->body());
                return $filename;
            }
        } catch (\Throwable $e) {
            Log::error("Equatorial PDF download error: {$e->getMessage()}");
        }

        return null;
    }

    public function getInvoiceHistory(string $ucNumber, int $months = 12): array
    {
        if (!$this->sessionToken) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->sessionToken}",
                'Accept' => 'application/json',
            ])->get(self::BASE_URL . '/api/faturas/historico', [
                'unidade_consumidora' => $ucNumber,
                'meses' => $months,
            ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
        } catch (\Throwable $e) {
            Log::error("Equatorial history error for UC {$ucNumber}: {$e->getMessage()}");
        }

        return [];
    }

    public function getSecondCopy(string $ucNumber): ?string
    {
        return $this->downloadInvoicePdf('segunda-via', $ucNumber);
    }
}

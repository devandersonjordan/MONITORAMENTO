<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Integrations\Equatorial\EquatorialScraper;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DownloadEquatorialInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function handle(EquatorialScraper $scraper): void
    {
        $clients = User::where('role', 'client')
            ->whereNotNull('equatorial_login')
            ->whereNotNull('equatorial_password')
            ->whereNotNull('uc_number')
            ->get();

        foreach ($clients as $client) {
            try {
                $this->processClient($scraper, $client);
            } catch (\Throwable $e) {
                Log::error("Equatorial download failed for client {$client->id}: {$e->getMessage()}");
            }
        }
    }

    private function processClient(EquatorialScraper $scraper, User $client): void
    {
        $authenticated = $scraper->authenticate(
            $client->equatorial_login,
            $client->equatorial_password
        );

        if (!$authenticated) {
            Log::warning("Equatorial auth failed for client {$client->id}");
            return;
        }

        $invoices = $scraper->listInvoices($client->uc_number);

        foreach ($invoices as $invoiceData) {
            $competence = $this->parseCompetence($invoiceData['referencia'] ?? $invoiceData['competencia'] ?? '');
            if (!$competence) continue;

            $exists = Invoice::where('client_id', $client->id)
                ->where('competence', $competence)
                ->exists();

            if ($exists) continue;

            $invoiceId = $invoiceData['id'] ?? $invoiceData['fatura_id'] ?? null;
            $pdfPath = null;

            if ($invoiceId) {
                $pdfPath = $scraper->downloadInvoicePdf((string) $invoiceId, $client->uc_number);
            }

            $amount = $this->parseAmount($invoiceData['valor'] ?? $invoiceData['valor_total'] ?? 0);

            Invoice::create([
                'client_id' => $client->id,
                'company_id' => $client->company_id,
                'competence' => $competence,
                'due_date' => $this->parseDate($invoiceData['vencimento'] ?? null),
                'amount_cents' => $amount,
                'pdf_path' => $pdfPath,
                'ocr_status' => $pdfPath ? 'pending' : 'no_pdf',
            ]);

            Log::info("Invoice downloaded for client {$client->id}, competence {$competence}");
        }
    }

    private function parseCompetence(string $value): ?string
    {
        if (preg_match('/(\d{2})\/(\d{4})/', $value, $m)) {
            return "{$m[2]}-{$m[1]}-01";
        }
        if (preg_match('/(\d{4})-(\d{2})/', $value, $m)) {
            return "{$m[1]}-{$m[2]}-01";
        }
        return null;
    }

    private function parseDate(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $value, $m)) {
                return "{$m[3]}-{$m[2]}-{$m[1]}";
            }
        }
        return null;
    }

    private function parseAmount(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) round((float) $value * 100);
        }
        $value = str_replace('.', '', (string) $value);
        $value = str_replace(',', '.', $value);
        return (int) round((float) $value * 100);
    }
}

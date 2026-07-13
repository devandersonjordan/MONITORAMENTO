<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Integrations\OCR\InvoiceOCRProcessor;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInvoiceOCRJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function handle(InvoiceOCRProcessor $processor): void
    {
        $invoices = Invoice::where('ocr_status', 'pending')
            ->whereNotNull('pdf_path')
            ->limit(20)
            ->get();

        foreach ($invoices as $invoice) {
            try {
                $data = $processor->extractData($invoice->pdf_path);

                if (!$data) {
                    $invoice->update(['ocr_status' => 'failed']);
                    continue;
                }

                $updateData = ['ocr_status' => 'completed', 'raw_ocr_data' => $data];

                if (isset($data['consumption_kwh'])) $updateData['consumption_kwh'] = $data['consumption_kwh'];
                if (isset($data['injected_kwh'])) $updateData['injected_kwh'] = $data['injected_kwh'];
                if (isset($data['compensated_kwh'])) $updateData['compensated_kwh'] = $data['compensated_kwh'];
                if (isset($data['previous_balance_kwh'])) $updateData['previous_balance_kwh'] = $data['previous_balance_kwh'];
                if (isset($data['current_balance_kwh'])) $updateData['current_balance_kwh'] = $data['current_balance_kwh'];
                if (isset($data['credits_received_kwh'])) $updateData['credits_received_kwh'] = $data['credits_received_kwh'];
                if (isset($data['credits_used_kwh'])) $updateData['credits_used_kwh'] = $data['credits_used_kwh'];
                if (isset($data['tariff'])) $updateData['tariff'] = $data['tariff'];
                if (isset($data['flag'])) $updateData['flag'] = $data['flag'];
                if (isset($data['icms_value'])) $updateData['icms_value'] = $data['icms_value'];
                if (isset($data['pis_value'])) $updateData['pis_value'] = $data['pis_value'];
                if (isset($data['cofins_value'])) $updateData['cofins_value'] = $data['cofins_value'];
                if (isset($data['public_lighting_value'])) $updateData['public_lighting_value'] = $data['public_lighting_value'];

                if (isset($data['amount']) && !$invoice->amount_cents) {
                    $updateData['amount_cents'] = (int) round($data['amount'] * 100);
                }

                if (isset($data['due_date']) && !$invoice->due_date) {
                    $parsed = $this->parseDate($data['due_date']);
                    if ($parsed) $updateData['due_date'] = $parsed;
                }

                $invoice->update($updateData);

                Log::info("OCR completed for invoice {$invoice->id}");
            } catch (\Throwable $e) {
                $invoice->update(['ocr_status' => 'failed']);
                Log::error("OCR failed for invoice {$invoice->id}: {$e->getMessage()}");
            }
        }
    }

    private function parseDate(string $value): ?string
    {
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $value, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        return null;
    }
}

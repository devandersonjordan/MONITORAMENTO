<?php

declare(strict_types=1);

namespace App\Integrations\OCR;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceOCRProcessor
{
    public function extractData(string $pdfPath): ?array
    {
        $fullPath = Storage::disk('local')->path($pdfPath);

        if (!file_exists($fullPath)) {
            Log::error("OCR: PDF not found at {$fullPath}");
            return null;
        }

        try {
            $text = $this->extractTextFromPdf($fullPath);

            if (empty($text)) {
                return null;
            }

            return $this->parseInvoiceText($text);
        } catch (\Throwable $e) {
            Log::error("OCR processing failed for {$pdfPath}: {$e->getMessage()}");
            return null;
        }
    }

    private function extractTextFromPdf(string $path): string
    {
        // Try Tesseract OCR first
        if (class_exists(\thiagoalessio\TesseractOCR\TesseractOCR::class)) {
            try {
                $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR($path);
                return $ocr->lang('por')->run();
            } catch (\Throwable $e) {
                Log::warning("Tesseract OCR failed, trying pdftotext: {$e->getMessage()}");
            }
        }

        // Fallback: pdftotext
        $output = [];
        exec("pdftotext -layout '{$path}' -", $output);
        return implode("\n", $output);
    }

    private function parseInvoiceText(string $text): array
    {
        $data = [
            'raw_text' => $text,
        ];

        $data['competence'] = $this->extractPattern($text, '/Refer[eê]ncia[:\s]+(\d{2}\/\d{4})/i')
            ?? $this->extractPattern($text, '/M[eê]s\/Ano[:\s]+(\d{2}\/\d{4})/i');

        $data['due_date'] = $this->extractPattern($text, '/Vencimento[:\s]+(\d{2}\/\d{2}\/\d{4})/i');

        $data['amount'] = $this->extractDecimal($text, '/Valor a Pagar[:\s]*R?\$?\s*([\d.,]+)/i')
            ?? $this->extractDecimal($text, '/Total[:\s]*R?\$?\s*([\d.,]+)/i');

        $data['consumption_kwh'] = $this->extractDecimal($text, '/Consumo[:\s]*([\d.,]+)\s*kWh/i');

        $data['injected_kwh'] = $this->extractDecimal($text, '/Energia\s+Injetada[:\s]*([\d.,]+)/i')
            ?? $this->extractDecimal($text, '/Inje[çc][aã]o[:\s]*([\d.,]+)/i');

        $data['compensated_kwh'] = $this->extractDecimal($text, '/Energia\s+Compensada[:\s]*([\d.,]+)/i')
            ?? $this->extractDecimal($text, '/Compensa[çc][aã]o[:\s]*([\d.,]+)/i');

        $data['previous_balance_kwh'] = $this->extractDecimal($text, '/Saldo\s+Anterior[:\s]*([\d.,]+)/i');

        $data['current_balance_kwh'] = $this->extractDecimal($text, '/Saldo\s+Atual[:\s]*([\d.,]+)/i');

        $data['credits_received_kwh'] = $this->extractDecimal($text, '/Cr[eé]dito\s+Recebido[:\s]*([\d.,]+)/i');

        $data['credits_used_kwh'] = $this->extractDecimal($text, '/Cr[eé]dito\s+Utilizado[:\s]*([\d.,]+)/i');

        $data['tariff'] = $this->extractDecimal($text, '/Tarifa[:\s]*R?\$?\s*([\d.,]+)/i');

        $flagMatch = $this->extractPattern($text, '/Bandeira[:\s]*(Verde|Amarela|Vermelha[^,\n]*)/i');
        $data['flag'] = $flagMatch ? strtolower(str_replace(' ', '_', trim($flagMatch))) : null;

        $data['icms_value'] = $this->extractDecimal($text, '/ICMS[:\s]*R?\$?\s*([\d.,]+)/i');
        $data['pis_value'] = $this->extractDecimal($text, '/PIS\/PASEP[:\s]*R?\$?\s*([\d.,]+)/i')
            ?? $this->extractDecimal($text, '/PIS[:\s]*R?\$?\s*([\d.,]+)/i');
        $data['cofins_value'] = $this->extractDecimal($text, '/COFINS[:\s]*R?\$?\s*([\d.,]+)/i');
        $data['public_lighting_value'] = $this->extractDecimal($text, '/Ilumina[çc][aã]o\s+P[uú]blica[:\s]*R?\$?\s*([\d.,]+)/i')
            ?? $this->extractDecimal($text, '/CIP[:\s]*R?\$?\s*([\d.,]+)/i');

        return $data;
    }

    private function extractPattern(string $text, string $pattern): ?string
    {
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    private function extractDecimal(string $text, string $pattern): ?float
    {
        $value = $this->extractPattern($text, $pattern);
        if ($value === null) {
            return null;
        }
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }
}

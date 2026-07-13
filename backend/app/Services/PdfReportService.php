<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfReportService
{
    public function generate(Report $report): string
    {
        $company = Company::find($report->company_id);
        $data = $report->data;

        $html = $this->buildHtml($data, $company);

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
            ]);

        $filename = "reports/{$report->company_id}/{$report->id}.pdf";
        Storage::disk('local')->put($filename, $pdf->output());

        $report->update(['pdf_path' => $filename]);

        return $filename;
    }

    private function buildHtml(array $data, ?Company $company): string
    {
        $companyName = $company?->name ?? 'SolarSaaS';
        $clientName = $data['client']['name'] ?? '';
        $period = $data['period']['month_label'] ?? '';
        $prod = $data['production'] ?? [];
        $cons = $data['consumption'] ?? [];
        $balance = $data['energy_balance'] ?? [];
        $financial = $data['financial'] ?? [];
        $env = $data['environmental'] ?? [];
        $eff = $data['efficiency'] ?? [];

        $variationColor = ($prod['variation_pct'] ?? 0) >= 0 ? '#16a34a' : '#dc2626';
        $variationSign = ($prod['variation_pct'] ?? 0) >= 0 ? '+' : '';

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 20px; }
                .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px; }
                .header h1 { color: #2563eb; font-size: 22px; margin: 0; }
                .header .company { font-size: 14px; color: #64748b; }
                .section { margin-bottom: 18px; }
                .section-title { font-size: 14px; font-weight: bold; color: #2563eb; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px; }
                .grid { display: table; width: 100%; }
                .grid-row { display: table-row; }
                .grid-cell { display: table-cell; padding: 6px 10px; width: 25%; }
                .metric { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center; }
                .metric .value { font-size: 20px; font-weight: bold; color: #1e293b; }
                .metric .label { font-size: 9px; color: #64748b; text-transform: uppercase; }
                table.data { width: 100%; border-collapse: collapse; font-size: 10px; }
                table.data th { background: #2563eb; color: white; padding: 6px 8px; text-align: left; }
                table.data td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; }
                table.data tr:nth-child(even) td { background: #f8fafc; }
                .highlight { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px; margin: 10px 0; }
                .footer { margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 9px; color: #94a3b8; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <div>
                    <h1>Relatório Mensal de Energia Solar</h1>
                    <div class="company">{$companyName}</div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:13px;font-weight:bold">{$clientName}</div>
                    <div style="color:#64748b">UC: {$data['client']['uc']}</div>
                    <div style="color:#64748b">Período: {$period}</div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Resumo de Produção</div>
                <div class="grid">
                    <div class="grid-row">
                        <div class="grid-cell"><div class="metric"><div class="value">{$prod['total_kwh']} kWh</div><div class="label">Produção Total</div></div></div>
                        <div class="grid-cell"><div class="metric"><div class="value">{$prod['performance_ratio']}%</div><div class="label">Performance Ratio</div></div></div>
                        <div class="grid-cell"><div class="metric"><div class="value" style="color:{$variationColor}">{$variationSign}{$prod['variation_pct']}%</div><div class="label">vs Mês Anterior</div></div></div>
                        <div class="grid-cell"><div class="metric"><div class="value">{$eff['avg_daily_kwh']} kWh</div><div class="label">Média Diária</div></div></div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Balanço Energético</div>
                <table class="data">
                    <tr><th>Indicador</th><th>Valor</th></tr>
                    <tr><td>Consumo Total</td><td>{$cons['total_kwh']} kWh</td></tr>
                    <tr><td>Autoconsumo</td><td>{$cons['auto_consumption_kwh']} kWh ({$cons['auto_consumption_pct']}%)</td></tr>
                    <tr><td>Energia Injetada</td><td>{$balance['injected_kwh']} kWh</td></tr>
                    <tr><td>Energia Compensada</td><td>{$balance['compensated_kwh']} kWh</td></tr>
                    <tr><td>Saldo Anterior</td><td>{$balance['previous_balance_kwh']} kWh</td></tr>
                    <tr><td>Saldo Atual</td><td>{$balance['current_balance_kwh']} kWh</td></tr>
                    <tr><td>Créditos Recebidos</td><td>{$balance['credits_received_kwh']} kWh</td></tr>
                    <tr><td>Créditos Utilizados</td><td>{$balance['credits_used_kwh']} kWh</td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">Análise Financeira</div>
                <div class="grid">
                    <div class="grid-row">
                        <div class="grid-cell"><div class="metric"><div class="value">R$ {$financial['savings_brl']}</div><div class="label">Economia do Mês</div></div></div>
                        <div class="grid-cell"><div class="metric"><div class="value">R$ {$financial['invoice_amount_brl']}</div><div class="label">Valor da Fatura</div></div></div>
                        <div class="grid-cell"><div class="metric"><div class="value">{$env['co2_avoided_kg']} kg</div><div class="label">CO₂ Evitado</div></div></div>
                        <div class="grid-cell"><div class="metric"><div class="value">{$env['trees_equivalent']}</div><div class="label">Árvores Equivalentes</div></div></div>
                    </div>
                </div>
            </div>

            <div class="highlight">
                <strong>Detalhes da Fatura:</strong> Tarifa R$ {$financial['tariff']}/kWh | Bandeira: {$financial['flag']} | ICMS: R$ {$financial['icms']} | PIS: R$ {$financial['pis']} | COFINS: R$ {$financial['cofins']} | Iluminação Pública: R$ {$financial['public_lighting']}
            </div>

            <div class="footer">
                <p>Relatório gerado automaticamente por {$companyName} — SolarSaaS</p>
                <p>Este documento é informativo. Dados sujeitos a variações de medição.</p>
            </div>
        </body>
        </html>
        HTML;
    }
}

<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\PdfReportService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function handle(ReportService $reportService, PdfReportService $pdfService): void
    {
        $month = Carbon::now()->subMonth()->format('Y-m');

        $clients = User::role('client')->get();

        foreach ($clients as $client) {
            try {
                $report = $reportService->generateMonthlyReport($client, $month);
                $pdfService->generate($report);
                Log::info("Monthly report generated for client {$client->id}, period {$month}");
            } catch (\Throwable $e) {
                Log::error("Failed to generate monthly report for client {$client->id}: {$e->getMessage()}");
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Services\PdfReportService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Report::query();

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $reports = $query->orderByDesc('period_end')
            ->paginate($request->integer('per_page', 15));

        return response()->json($reports);
    }

    public function show(Report $report): JsonResponse
    {
        return response()->json(['data' => $report]);
    }

    public function generate(Request $request, ReportService $reportService): JsonResponse
    {
        $request->validate([
            'client_id' => 'required|exists:users,id',
            'month' => 'required|date_format:Y-m',
        ]);

        $client = User::findOrFail($request->client_id);
        $report = $reportService->generateMonthlyReport($client, $request->month);

        return response()->json(['data' => $report], 201);
    }

    public function downloadPdf(Report $report, PdfReportService $pdfService): mixed
    {
        if (!$report->pdf_path || !Storage::disk('local')->exists($report->pdf_path)) {
            $pdfService->generate($report);
            $report->refresh();
        }

        return response()->download(
            Storage::disk('local')->path($report->pdf_path),
            "relatorio_{$report->id}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}

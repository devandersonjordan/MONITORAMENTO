<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query();

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('status')) {
            $query->where('ocr_status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('client', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('uc_number', 'ilike', "%{$search}%");
            });
        }

        $invoices = $query->with('client:id,name,uc_number')
            ->orderByDesc('competence')
            ->paginate($request->integer('per_page', 15));

        return response()->json($invoices);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json(['data' => $invoice->load('client:id,name,uc_number')]);
    }

    public function downloadPdf(Invoice $invoice): mixed
    {
        if (!$invoice->pdf_path || !Storage::disk('local')->exists($invoice->pdf_path)) {
            return response()->json(['message' => 'PDF não disponível'], 404);
        }

        return response()->download(
            Storage::disk('local')->path($invoice->pdf_path),
            "fatura_{$invoice->id}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}

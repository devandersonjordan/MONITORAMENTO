<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InverterReading;
use App\Models\Invoice;
use App\Models\Plant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartController extends Controller
{
    public function dailyGeneration(Request $request): JsonResponse
    {
        $request->validate(['plant_id' => 'nullable|exists:plants,id', 'days' => 'nullable|integer|min:1|max:90']);

        $days = $request->integer('days', 30);
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $query = InverterReading::query()
            ->where('recorded_at', '>=', $startDate)
            ->select(
                DB::raw("DATE(recorded_at) as date"),
                DB::raw("SUM(daily_kwh) as total_kwh"),
                DB::raw("AVG(power_w) as avg_power_w"),
                DB::raw("MAX(power_w) as max_power_w")
            )
            ->groupBy('date')
            ->orderBy('date');

        if ($request->filled('plant_id')) {
            $inverterIds = Plant::find($request->plant_id)?->inverters()->pluck('id') ?? [];
            $query->whereIn('inverter_id', $inverterIds);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function monthlyGeneration(Request $request): JsonResponse
    {
        $request->validate(['year' => 'nullable|integer|min:2020|max:2030']);

        $year = $request->integer('year', Carbon::now()->year);

        $data = InverterReading::query()
            ->whereYear('recorded_at', $year)
            ->select(
                DB::raw("EXTRACT(MONTH FROM recorded_at)::int as month"),
                DB::raw("SUM(daily_kwh) as total_kwh")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = collect(range(1, 12))->map(fn($m) => [
            'month' => $m,
            'label' => Carbon::create($year, $m)->translatedFormat('M'),
            'total_kwh' => round((float) ($data[$m]->total_kwh ?? 0), 2),
        ]);

        return response()->json(['data' => $months->values()]);
    }

    public function yearlyGeneration(Request $request): JsonResponse
    {
        $data = InverterReading::query()
            ->select(
                DB::raw("EXTRACT(YEAR FROM recorded_at)::int as year"),
                DB::raw("SUM(daily_kwh) as total_kwh")
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function productionVsConsumption(Request $request): JsonResponse
    {
        $request->validate(['client_id' => 'nullable|exists:users,id', 'months' => 'nullable|integer|min:1|max:24']);

        $months = $request->integer('months', 12);
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        $invoices = Invoice::query()
            ->where('competence', '>=', $startDate)
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
            ->select('competence', DB::raw("SUM(consumption_kwh) as consumption"), DB::raw("SUM(injected_kwh) as injected"), DB::raw("SUM(compensated_kwh) as compensated"))
            ->groupBy('competence')
            ->orderBy('competence')
            ->get();

        $production = InverterReading::query()
            ->where('recorded_at', '>=', $startDate)
            ->select(
                DB::raw("DATE_TRUNC('month', recorded_at)::date as month"),
                DB::raw("SUM(daily_kwh) as total_kwh")
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->month)->format('Y-m'));

        $data = $invoices->map(function ($inv) use ($production) {
            $key = Carbon::parse($inv->competence)->format('Y-m');
            return [
                'month' => $key,
                'label' => Carbon::parse($inv->competence)->translatedFormat('M/Y'),
                'production_kwh' => round((float) ($production[$key]->total_kwh ?? 0), 2),
                'consumption_kwh' => round((float) $inv->consumption, 2),
                'injected_kwh' => round((float) $inv->injected, 2),
                'compensated_kwh' => round((float) $inv->compensated, 2),
            ];
        });

        return response()->json(['data' => $data->values()]);
    }

    public function savingsHistory(Request $request): JsonResponse
    {
        $request->validate(['client_id' => 'nullable|exists:users,id', 'months' => 'nullable|integer|min:1|max:24']);

        $months = $request->integer('months', 12);
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        $data = Invoice::query()
            ->where('competence', '>=', $startDate)
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
            ->select(
                'competence',
                DB::raw("SUM(compensated_kwh * COALESCE(tariff, 0.85)) as savings"),
                DB::raw("SUM(amount_cents) / 100.0 as invoice_total")
            )
            ->groupBy('competence')
            ->orderBy('competence')
            ->get()
            ->map(fn($r) => [
                'month' => Carbon::parse($r->competence)->format('Y-m'),
                'label' => Carbon::parse($r->competence)->translatedFormat('M/Y'),
                'savings_brl' => round((float) $r->savings, 2),
                'invoice_brl' => round((float) $r->invoice_total, 2),
            ]);

        return response()->json(['data' => $data->values()]);
    }

    public function realtimePower(Request $request): JsonResponse
    {
        $request->validate(['plant_id' => 'nullable|exists:plants,id']);

        $query = InverterReading::query()
            ->where('recorded_at', '>=', Carbon::now()->subHours(24))
            ->select(
                DB::raw("DATE_TRUNC('hour', recorded_at) as hour"),
                DB::raw("AVG(power_w) as avg_power_w"),
                DB::raw("SUM(power_w) as total_power_w")
            )
            ->groupBy('hour')
            ->orderBy('hour');

        if ($request->filled('plant_id')) {
            $inverterIds = Plant::find($request->plant_id)?->inverters()->pluck('id') ?? [];
            $query->whereIn('inverter_id', $inverterIds);
        }

        return response()->json(['data' => $query->get()]);
    }
}

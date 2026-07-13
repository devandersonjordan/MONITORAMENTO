<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inverter;
use App\Models\InverterAlert;
use App\Models\InverterReading;
use App\Models\Invoice;
use App\Models\Plant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $yearStart = Carbon::now()->startOfYear();

        $totalClients = User::where('company_id', $companyId)
            ->where('role', 'client')->count();

        $totalPlants = Plant::where('company_id', $companyId)->count();

        $totalInverters = Inverter::where('company_id', $companyId)->count();

        $offlineInverters = Inverter::where('company_id', $companyId)
            ->where('status', 'offline')->count();

        $activeAlerts = InverterAlert::where('company_id', $companyId)
            ->whereNull('resolved_at')->count();

        $inverterIds = Inverter::where('company_id', $companyId)->pluck('id');

        $energyToday = InverterReading::whereIn('inverter_id', $inverterIds)
            ->whereDate('recorded_at', $today)
            ->max('daily_kwh') ?? 0;

        $energyMonth = InverterReading::whereIn('inverter_id', $inverterIds)
            ->where('recorded_at', '>=', $monthStart)
            ->sum('daily_kwh') ?? 0;

        $energyYear = InverterReading::whereIn('inverter_id', $inverterIds)
            ->where('recorded_at', '>=', $yearStart)
            ->sum('daily_kwh') ?? 0;

        $pendingInvoices = Invoice::where('company_id', $companyId)
            ->where('due_date', '>=', $today)
            ->count();

        $totalCredits = Invoice::where('company_id', $companyId)
            ->sum('current_balance_kwh') ?? 0;

        $totalSavings = Invoice::where('company_id', $companyId)
            ->selectRaw('SUM(compensated_kwh * tariff) as savings')
            ->value('savings') ?? 0;

        return response()->json([
            'data' => [
                'total_clients' => $totalClients,
                'total_plants' => $totalPlants,
                'total_inverters' => $totalInverters,
                'energy_today_kwh' => round((float) $energyToday, 2),
                'energy_month_kwh' => round((float) $energyMonth, 2),
                'energy_year_kwh' => round((float) $energyYear, 2),
                'total_savings_brl' => round((float) $totalSavings, 2),
                'total_credits_kwh' => round((float) $totalCredits, 2),
                'pending_invoices' => $pendingInvoices,
                'offline_inverters' => $offlineInverters,
                'active_alerts' => $activeAlerts,
            ],
        ]);
    }
}

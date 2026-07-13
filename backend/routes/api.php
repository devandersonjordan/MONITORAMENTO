<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\PlantController;
use App\Http\Controllers\Api\InverterController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ChartController;
use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth (authenticated)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Resource routes
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('plants', PlantController::class);
    Route::apiResource('inverters', InverterController::class);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);

    // Reports
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/{report}', [ReportController::class, 'show']);
    Route::post('/reports/generate', [ReportController::class, 'generate']);
    Route::get('/reports/{report}/pdf', [ReportController::class, 'downloadPdf']);

    // Charts
    Route::prefix('charts')->group(function () {
        Route::get('/daily-generation', [ChartController::class, 'dailyGeneration']);
        Route::get('/monthly-generation', [ChartController::class, 'monthlyGeneration']);
        Route::get('/yearly-generation', [ChartController::class, 'yearlyGeneration']);
        Route::get('/production-vs-consumption', [ChartController::class, 'productionVsConsumption']);
        Route::get('/savings-history', [ChartController::class, 'savingsHistory']);
        Route::get('/realtime-power', [ChartController::class, 'realtimePower']);
    });

    // AI Assistant
    Route::prefix('ai')->group(function () {
        Route::post('/chat', [AiAssistantController::class, 'chat']);
        Route::get('/analyze/plant/{plant}', [AiAssistantController::class, 'analyzePlant']);
        Route::get('/analyze/invoice/{invoice}', [AiAssistantController::class, 'analyzeInvoice']);
    });

    // Alerts
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::get('/alerts/stats', [AlertController::class, 'stats']);
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    });
});

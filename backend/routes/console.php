<?php

use App\Jobs\SyncInverterDataJob;
use App\Jobs\DownloadEquatorialInvoicesJob;
use App\Jobs\ProcessInvoiceOCRJob;
use App\Jobs\GenerateMonthlyReportsJob;
use App\Jobs\DetectAnomaliesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SyncInverterDataJob())->everyFiveMinutes()->withoutOverlapping();

Schedule::job(new DownloadEquatorialInvoicesJob())->dailyAt('06:00')->withoutOverlapping();

Schedule::job(new ProcessInvoiceOCRJob())->hourly()->withoutOverlapping();

Schedule::job(new GenerateMonthlyReportsJob())->monthlyOn(1, '08:00')->withoutOverlapping();

Schedule::job(new DetectAnomaliesJob())->everyThirtyMinutes()->withoutOverlapping();

Schedule::command('backup:run --only-db')->dailyAt('02:00');
Schedule::command('backup:clean')->dailyAt('03:00');

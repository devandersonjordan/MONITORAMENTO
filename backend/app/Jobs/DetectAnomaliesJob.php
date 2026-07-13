<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Inverter;
use App\Models\User;
use App\Notifications\AlertNotification;
use App\Services\AnomalyDetectorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DetectAnomaliesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function handle(AnomalyDetectorService $detector): void
    {
        $inverters = Inverter::with('plant.client')->where('status', 'online')->get();

        foreach ($inverters as $inverter) {
            try {
                $alerts = $detector->detectInverterAnomalies($inverter);

                foreach ($alerts as $alert) {
                    if ($alert->wasRecentlyCreated) {
                        $this->notifyUsers($inverter, $alert);
                    }
                }
            } catch (\Throwable $e) {
                Log::error("Anomaly detection failed for inverter {$inverter->id}: {$e->getMessage()}");
            }
        }
    }

    private function notifyUsers(Inverter $inverter, $alert): void
    {
        $client = $inverter->plant?->client;
        if ($client) {
            $client->notify(new AlertNotification($alert));
        }

        $admins = User::where('company_id', $inverter->company_id)
            ->where('role', 'admin')
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new AlertNotification($alert));
        }
    }
}

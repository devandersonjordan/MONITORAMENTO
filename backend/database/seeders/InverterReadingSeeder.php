<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Inverter;
use App\Models\InverterReading;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InverterReadingSeeder extends Seeder
{
    public function run(): void
    {
        $inverters = Inverter::all();
        $now = Carbon::now();

        foreach ($inverters as $inverter) {
            $readings = [];
            $totalKwh = fake()->randomFloat(2, 5000, 50000);

            for ($day = 29; $day >= 0; $day--) {
                $date = $now->copy()->subDays($day);
                $dailyKwh = 0;

                foreach ([8, 11, 14, 17] as $hour) {
                    $recordedAt = $date->copy()->setHour($hour);
                    $isSunny = $hour >= 6 && $hour <= 18;
                    $peakFactor = match (true) {
                        $hour >= 10 && $hour <= 14 => 1.0,
                        $hour >= 8 && $hour < 10 => 0.6,
                        $hour > 14 && $hour <= 17 => 0.5,
                        default => 0,
                    };

                    $power = $isSunny ? round(fake()->randomFloat(2, 2000, 7000) * $peakFactor, 2) : 0;
                    $intervalKwh = round($power * 3 / 1000, 2);
                    $dailyKwh += $intervalKwh;
                    $totalKwh += $intervalKwh;

                    $readings[] = [
                        'inverter_id' => $inverter->id,
                        'recorded_at' => $recordedAt,
                        'power_w' => $power,
                        'voltage_v' => $power > 0 ? round(fake()->randomFloat(2, 220, 380), 2) : 0,
                        'current_a' => $power > 0 ? round($power / 380, 2) : 0,
                        'frequency_hz' => $power > 0 ? round(fake()->randomFloat(2, 59.9, 60.1), 2) : 0,
                        'temperature_c' => round(fake()->randomFloat(1, 28, 55), 1),
                        'daily_kwh' => round($dailyKwh, 2),
                        'monthly_kwh' => round($dailyKwh * (30 - $day), 2),
                        'yearly_kwh' => round($totalKwh * 0.4, 2),
                        'total_kwh' => round($totalKwh, 2),
                        'efficiency_pct' => round(fake()->randomFloat(2, 80, 97), 2),
                        'status' => 'normal',
                        'created_at' => $recordedAt,
                        'updated_at' => $recordedAt,
                    ];
                }
            }

            foreach (array_chunk($readings, 50) as $chunk) {
                InverterReading::insert($chunk);
            }
        }
    }
}

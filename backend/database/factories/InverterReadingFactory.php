<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Inverter;
use App\Models\InverterReading;
use Illuminate\Database\Eloquent\Factories\Factory;

class InverterReadingFactory extends Factory
{
    protected $model = InverterReading::class;

    public function definition(): array
    {
        $hour = (int) date('H');
        $isSunny = $hour >= 6 && $hour <= 18;
        $power = $isSunny ? fake()->randomFloat(2, 500, 8000) : 0;

        return [
            'inverter_id' => Inverter::factory(),
            'recorded_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'power_w' => $power,
            'voltage_v' => $power > 0 ? fake()->randomFloat(2, 220, 380) : 0,
            'current_a' => $power > 0 ? fake()->randomFloat(2, 2, 25) : 0,
            'frequency_hz' => $power > 0 ? fake()->randomFloat(2, 59.8, 60.2) : 0,
            'temperature_c' => fake()->randomFloat(1, 25, 65),
            'daily_kwh' => fake()->randomFloat(2, 5, 40),
            'monthly_kwh' => fake()->randomFloat(2, 200, 1200),
            'yearly_kwh' => fake()->randomFloat(2, 3000, 15000),
            'total_kwh' => fake()->randomFloat(2, 5000, 100000),
            'efficiency_pct' => fake()->randomFloat(2, 75, 98),
            'status' => 'normal',
        ];
    }
}

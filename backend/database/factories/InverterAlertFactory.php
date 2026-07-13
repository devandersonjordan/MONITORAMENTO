<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Inverter;
use App\Models\InverterAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

class InverterAlertFactory extends Factory
{
    protected $model = InverterAlert::class;

    public function definition(): array
    {
        $types = [
            'offline' => 'Inversor sem comunicação',
            'low_production' => 'Produção abaixo do esperado',
            'high_temperature' => 'Temperatura elevada',
            'grid_fault' => 'Falha na rede elétrica',
            'voltage_anomaly' => 'Anomalia de tensão',
            'efficiency_drop' => 'Queda de eficiência',
        ];

        $type = fake()->randomElement(array_keys($types));

        return [
            'inverter_id' => Inverter::factory(),
            'company_id' => Company::factory(),
            'type' => $type,
            'severity' => fake()->randomElement(['info', 'warning', 'critical']),
            'message' => $types[$type],
            'data' => ['value' => fake()->randomFloat(2, 0, 100)],
            'resolved_at' => fake()->optional(0.6)->dateTimeBetween('-7 days', 'now'),
        ];
    }
}

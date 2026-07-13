<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlantFactory extends Factory
{
    protected $model = Plant::class;

    public function definition(): array
    {
        $powerKwp = fake()->randomFloat(2, 3, 75);
        $moduleWatts = fake()->randomElement([400, 450, 500, 550, 600]);
        $moduleQty = (int) ceil(($powerKwp * 1000) / $moduleWatts);

        return [
            'company_id' => Company::factory(),
            'client_id' => User::factory()->client(),
            'name' => 'Usina ' . fake('pt_BR')->lastName() . ' ' . fake()->numerify('##'),
            'power_kwp' => $powerKwp,
            'installation_date' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'module_model' => fake()->randomElement([
                'Canadian Solar CS7L-600MS', 'Jinko Tiger Neo 580W',
                'Trina Vertex S+ 450W', 'LONGi Hi-MO 6 555W',
                'BYD HM6-72 540W',
            ]),
            'module_qty' => $moduleQty,
            'inverter_model' => fake()->randomElement([
                'Sungrow SG5.0RS', 'GoodWe GW5000-MS',
                'Deye SUN-8K-SG04LP3', 'Growatt MIN 6000TL-X',
            ]),
            'inverter_power_kw' => round($powerKwp * fake()->randomFloat(2, 0.85, 1.1), 2),
            'latitude' => fake()->latitude(-10.1, -9.4),
            'longitude' => fake()->longitude(-36.8, -35.5),
            'address' => fake('pt_BR')->streetAddress() . ', ' . fake('pt_BR')->city() . ' - AL',
            'installer_company' => fake('pt_BR')->company(),
            'status' => fake()->randomElement(['active', 'active', 'active', 'maintenance', 'inactive']),
        ];
    }
}

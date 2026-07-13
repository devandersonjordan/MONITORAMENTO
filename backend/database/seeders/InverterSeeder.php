<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Inverter;
use App\Models\InverterAlert;
use App\Models\Plant;
use Illuminate\Database\Seeder;

class InverterSeeder extends Seeder
{
    public function run(): void
    {
        $plants = Plant::all();

        foreach ($plants as $plant) {
            $inverterCount = fake()->numberBetween(1, 2);
            $inverters = Inverter::factory()->count($inverterCount)->create([
                'plant_id' => $plant->id,
                'company_id' => $plant->company_id,
            ]);

            foreach ($inverters as $inverter) {
                if (fake()->boolean(30)) {
                    InverterAlert::factory()->count(fake()->numberBetween(1, 3))->create([
                        'inverter_id' => $inverter->id,
                        'company_id' => $inverter->company_id,
                    ]);
                }
            }
        }
    }
}

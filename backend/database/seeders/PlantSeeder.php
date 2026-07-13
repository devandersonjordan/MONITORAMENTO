<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlantSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::where('role', 'client')->get();

        foreach ($clients as $client) {
            $plantCount = fake()->numberBetween(1, 3);
            Plant::factory()->count($plantCount)->create([
                'company_id' => $client->company_id,
                'client_id' => $client->id,
            ]);
        }
    }
}

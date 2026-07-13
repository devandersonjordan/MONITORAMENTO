<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Inverter;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;

class InverterFactory extends Factory
{
    protected $model = Inverter::class;

    public function definition(): array
    {
        $brand = fake()->randomElement(['elekeeper', 'goodwe', 'sungrow', 'deye']);

        $models = [
            'elekeeper' => ['EKP-5K', 'EKP-8K', 'EKP-10K', 'EKP-15K'],
            'goodwe' => ['GW5000-MS', 'GW8000-MS', 'GW10K-DT', 'GW15K-DT'],
            'sungrow' => ['SG5.0RS', 'SG8.0RS', 'SG10RT', 'SG15RT'],
            'deye' => ['SUN-5K-SG03LP1', 'SUN-8K-SG04LP3', 'SUN-10K-SG04LP3', 'SUN-12K-SG04LP3'],
        ];

        return [
            'plant_id' => Plant::factory(),
            'company_id' => Company::factory(),
            'brand' => $brand,
            'model' => fake()->randomElement($models[$brand]),
            'serial_number' => strtoupper(fake()->bothify('??#####??##')),
            'api_credentials' => json_encode([
                'username' => fake()->userName(),
                'password' => fake()->password(),
                'station_id' => (string) fake()->numerify('######'),
            ]),
            'status' => fake()->randomElement(['online', 'online', 'online', 'offline', 'warning']),
            'last_communication_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    public function online(): static
    {
        return $this->state(fn() => [
            'status' => 'online',
            'last_communication_at' => now(),
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn() => [
            'status' => 'offline',
            'last_communication_at' => fake()->dateTimeBetween('-7 days', '-2 hours'),
        ]);
    }
}

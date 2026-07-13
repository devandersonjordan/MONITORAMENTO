<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake('pt_BR')->company(),
            'cnpj' => fake('pt_BR')->cnpj(),
            'phone' => fake('pt_BR')->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'plan' => fake()->randomElement(['basic', 'professional', 'enterprise']),
            'max_clients' => fake()->randomElement([50, 100, 500]),
            'max_plants' => fake()->randomElement([100, 500, 2000]),
            'status' => 'active',
        ];
    }
}

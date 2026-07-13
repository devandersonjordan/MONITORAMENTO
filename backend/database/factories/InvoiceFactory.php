<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $consumption = fake()->randomFloat(2, 150, 800);
        $injected = fake()->randomFloat(2, 200, 1500);
        $compensated = min($consumption, $injected * fake()->randomFloat(2, 0.7, 0.95));
        $previousBalance = fake()->randomFloat(2, 0, 500);
        $creditsReceived = $injected - $compensated;
        $currentBalance = $previousBalance + $creditsReceived;
        $tariff = fake()->randomFloat(6, 0.6, 1.2);

        return [
            'client_id' => User::factory()->client(),
            'company_id' => Company::factory(),
            'competence' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-01'),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'amount_cents' => (int) (($consumption - $compensated) * $tariff * 100) + fake()->numberBetween(800, 3500),
            'consumption_kwh' => $consumption,
            'injected_kwh' => $injected,
            'compensated_kwh' => round($compensated, 2),
            'previous_balance_kwh' => $previousBalance,
            'current_balance_kwh' => round($currentBalance, 2),
            'credits_received_kwh' => round($creditsReceived, 2),
            'credits_used_kwh' => round($compensated, 2),
            'tariff' => $tariff,
            'flag' => fake()->randomElement(['verde', 'amarela', 'vermelha_1', 'vermelha_2']),
            'icms_value' => fake()->randomFloat(2, 5, 50),
            'pis_value' => fake()->randomFloat(2, 1, 10),
            'cofins_value' => fake()->randomFloat(2, 2, 15),
            'public_lighting_value' => fake()->randomFloat(2, 10, 45),
            'pdf_path' => null,
            'ocr_status' => 'pending',
        ];
    }
}

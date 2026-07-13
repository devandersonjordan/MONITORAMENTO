<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake('pt_BR')->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'client',
            'phone' => fake('pt_BR')->phoneNumber(),
            'whatsapp' => fake('pt_BR')->cellphoneNumber(),
            'cpf_cnpj' => fake('pt_BR')->cpf(),
            'address' => fake('pt_BR')->streetAddress(),
            'city' => fake('pt_BR')->city(),
            'state' => fake('pt_BR')->stateAbbr(),
            'zip' => fake('pt_BR')->postcode(),
            'distributor' => 'Equatorial Alagoas',
            'uc_number' => (string) fake()->numerify('##########'),
            'meter_number' => (string) fake()->numerify('########'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => ['role' => 'admin']);
    }

    public function employee(): static
    {
        return $this->state(fn() => ['role' => 'employee']);
    }

    public function client(): static
    {
        return $this->state(fn() => ['role' => 'client']);
    }
}

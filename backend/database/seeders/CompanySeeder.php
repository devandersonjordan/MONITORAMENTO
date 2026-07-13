<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::create([
            'name' => 'Solar Tech Alagoas',
            'cnpj' => '12.345.678/0001-90',
            'phone' => '(82) 3333-4444',
            'email' => 'contato@solartechal.com.br',
            'plan' => 'enterprise',
            'max_clients' => 500,
            'max_plants' => 2000,
            'status' => 'active',
        ]);

        Company::create([
            'name' => 'Energia Verde Brasil',
            'cnpj' => '98.765.432/0001-10',
            'phone' => '(82) 5555-6666',
            'email' => 'contato@energiaverde.com.br',
            'plan' => 'professional',
            'max_clients' => 100,
            'max_plants' => 500,
            'status' => 'active',
        ]);
    }
}

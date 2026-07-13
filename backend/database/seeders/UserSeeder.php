<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $admin = User::create([
                'company_id' => $company->id,
                'name' => 'Admin ' . $company->name,
                'email' => 'admin@' . str_replace(' ', '', strtolower($company->name)) . '.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '(82) 99999-0001',
                'email_verified_at' => now(),
            ]);
            $admin->assignRole('admin');

            for ($i = 1; $i <= 2; $i++) {
                $employee = User::factory()->employee()->create([
                    'company_id' => $company->id,
                ]);
                $employee->assignRole('employee');
            }

            for ($i = 1; $i <= 5; $i++) {
                $client = User::factory()->client()->create([
                    'company_id' => $company->id,
                ]);
                $client->assignRole('client');
            }
        }
    }
}

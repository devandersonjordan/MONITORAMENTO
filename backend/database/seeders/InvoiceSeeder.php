<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::where('role', 'client')->get();

        foreach ($clients as $client) {
            for ($i = 5; $i >= 0; $i--) {
                $competence = Carbon::now()->subMonths($i)->startOfMonth();

                Invoice::factory()->create([
                    'client_id' => $client->id,
                    'company_id' => $client->company_id,
                    'competence' => $competence->format('Y-m-d'),
                    'due_date' => $competence->copy()->addDays(20)->format('Y-m-d'),
                ]);
            }
        }
    }
}

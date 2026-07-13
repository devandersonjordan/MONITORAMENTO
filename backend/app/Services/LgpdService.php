<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Plant;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LgpdService
{
    public function anonymizeUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->update([
                'name' => 'Usuário Removido #' . $user->id,
                'email' => "removed_{$user->id}@anonymized.local",
                'phone' => null,
                'whatsapp' => null,
                'cpf_cnpj' => null,
                'address' => null,
                'city' => null,
                'state' => null,
                'zip_code' => null,
                'equatorial_login' => null,
                'equatorial_password' => null,
                'uc_number' => null,
                'meter_number' => null,
            ]);

            $user->tokens()->delete();

            AuditLog::create([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'action' => 'lgpd_anonymize',
                'auditable_type' => 'users',
                'auditable_id' => $user->id,
                'old_values' => null,
                'new_values' => ['anonymized_at' => now()->toIso8601String()],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            Log::info("LGPD: User {$user->id} anonymized");
        });
    }

    public function exportUserData(User $user): array
    {
        return [
            'personal_data' => $user->only(['name', 'email', 'phone', 'whatsapp', 'cpf_cnpj', 'address', 'city', 'state', 'zip_code']),
            'plants' => Plant::where('client_id', $user->id)->get(['name', 'address', 'power_kwp', 'created_at'])->toArray(),
            'invoices' => Invoice::where('client_id', $user->id)->get(['competence', 'amount_cents', 'consumption_kwh', 'created_at'])->toArray(),
            'reports' => Report::where('client_id', $user->id)->get(['type', 'period_start', 'period_end', 'created_at'])->toArray(),
            'exported_at' => now()->toIso8601String(),
        ];
    }

    public function deleteUserData(User $user): void
    {
        DB::transaction(function () use ($user) {
            Report::where('client_id', $user->id)->delete();
            Invoice::where('client_id', $user->id)->delete();

            $plants = Plant::where('client_id', $user->id)->get();
            foreach ($plants as $plant) {
                $plant->inverters()->each(function ($inv) {
                    $inv->readings()->delete();
                    $inv->alerts()->delete();
                    $inv->delete();
                });
                $plant->delete();
            }

            $this->anonymizeUser($user);
            $user->delete();

            Log::info("LGPD: All data for user {$user->id} deleted");
        });
    }
}

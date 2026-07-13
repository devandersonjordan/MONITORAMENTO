<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'companies.view', 'companies.create', 'companies.edit', 'companies.delete',
            'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
            'plants.view', 'plants.create', 'plants.edit', 'plants.delete',
            'inverters.view', 'inverters.create', 'inverters.edit', 'inverters.delete',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
            'reports.view', 'reports.create',
            'dashboard.view',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);

        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $employee->syncPermissions([
            'clients.view', 'clients.create', 'clients.edit',
            'plants.view', 'plants.create', 'plants.edit',
            'inverters.view', 'inverters.create', 'inverters.edit',
            'invoices.view',
            'reports.view', 'reports.create',
            'dashboard.view',
        ]);

        $client = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        $client->syncPermissions([
            'plants.view',
            'inverters.view',
            'invoices.view',
            'reports.view',
            'dashboard.view',
        ]);
    }
}

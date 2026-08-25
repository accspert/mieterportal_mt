<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = ['Administrator', 'Sachbearbeiter', 'Eigentümer', 'Mieter'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        // Basic permissions (expand as needed)
        $perms = [
            'manage users',
            'manage properties',
            'manage units',
            'manage tenants',
            'manage renters',
            'manage documents',
            'manage tickets',
            'manage messages',
            'manage tasks',
            'manage invoices',
            'manage announcements',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // Assign broad permissions to Administrator
        $admin = Role::where('name', 'Administrator')->first();
        if ($admin) {
            $admin->givePermissionTo(Permission::all());
        }
    }
}

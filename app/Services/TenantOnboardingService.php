<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompanyTenant;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantOnboardingService
{
    /**
     * Creates a property management and invites its first administrator.
     *
     * All property managements share one database, so this is just the tenant record and the
     * admin account. The invitation is sent after the transaction has been committed.
     *
     * @param  array{company_name: string, slug: string, admin_name: string, admin_email: string, phone?: string|null, plan?: string|null}  $data
     */
    public function createTenant(array $data): CompanyTenant
    {
        // Roles and permissions are shared by all property managements.
        (new InitialSetupSeeder)->run();

        [$tenant, $admin] = DB::transaction(function () use ($data): array {
            $tenant = CompanyTenant::create([
                'id' => Str::slug($data['slug']),
                'company_name' => $data['company_name'],
                'email' => $data['admin_email'],
                'phone' => $data['phone'] ?? null,
                'plan' => $data['plan'] ?? config('plans.default'),
                'is_active' => true,
            ]);

            $admin = $tenant->run(function () use ($data): User {
                // The administrator sets the password via the invitation link.
                $admin = User::create([
                    'name' => $data['admin_name'],
                    'email' => mb_strtolower($data['admin_email']),
                    'password' => Str::password(40),
                    'email_verified_at' => now(),
                ]);

                $admin->assignRole('Administrator');

                return $admin;
            });

            return [$tenant, $admin];
        });

        $tenant->run(fn () => $admin->sendInvitation($tenant->company_name));

        return $tenant;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompanyTenant;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantOnboardingService
{
    /**
     * Erstellt eine neue Hausverwaltung inklusive isolierter DB, Subdomain und Initial-Admin.
     */
    public function createTenant(array $data): CompanyTenant
    {
        $slug = Str::slug($data['subdomain']);
        $centralDomains = config('tenancy.central_domains', ['mieterportal-demo.eu']);
        $centralDomain = $centralDomains[0] ?? 'mieterportal-demo.eu';
        $domain = "{$slug}.{$centralDomain}";

        $tenant = null;

        try {
            // 1. Tenant Datensatz anlegen (triggert automatisch DB-Erstellung & Tenant-Migrationen)
            $tenant = CompanyTenant::create([
                'id' => $slug,
                'company_name' => $data['company_name'],
                'email' => $data['admin_email'],
                'phone' => $data['phone'] ?? null,
                'plan' => $data['plan'] ?? 'standard',
                'is_active' => true,
            ]);

            // 2. Primäre Subdomain anlegen (slug für Subdomain-Resolver + optional FQDN)
            $tenant->domains()->create([
                'domain' => $slug,
            ]);

            // Falls direkt eine Custom Domain übergeben wurde:
            if (! empty($data['custom_domain'])) {
                $tenant->domains()->create([
                    'domain' => strtolower(trim($data['custom_domain'])),
                ]);
            }

            // 3. Im Kontext des neuen Mandanten Rollen initialisieren und den ersten Administrator anlegen
            $tenant->run(function () use ($data) {
                // Rollen & Berechtigungen in der neuen Mandanten-DB anlegen
                (new InitialSetupSeeder)->run();

                $admin = User::create([
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => Hash::make($data['admin_password']),
                    'email_verified_at' => now(),
                ]);

                // Weisen wir die Administrator-Rolle zu
                $admin->assignRole('Administrator');
            });

            return $tenant;
        } catch (\Throwable $e) {
            // Rollback bei Fehler
            if ($tenant) {
                try {
                    $tenant->delete();
                } catch (\Throwable $cleanupException) {
                    // Ignore cleanup error
                }
            }

            throw $e;
        }
    }
}

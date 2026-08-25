<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CompanyTenant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create 
                            {id : The tenant unique identifier (e.g. demo, acme)}
                            {domain : The subdomain or custom domain (e.g. demo or demo.localhost)}
                            {company_name? : The company / property management name}
                            {--email= : Contact email for the tenant}
                            {--seed : Run tenant database seeder with demo data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant with a separate isolated database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = (string) $this->argument('id');
        $domain = (string) $this->argument('domain');
        $companyName = (string) ($this->argument('company_name') ?? $id);
        $email = (string) ($this->option('email') ?? "admin@{$id}.local");
        $shouldSeed = (bool) $this->option('seed');

        $this->info("Creating tenant [{$id}]...");

        if (CompanyTenant::find($id)) {
            $this->error("Tenant [{$id}] already exists!");

            return self::FAILURE;
        }

        /** @var CompanyTenant $tenant */
        $tenant = CompanyTenant::create([
            'id' => $id,
            'company_name' => $companyName,
            'email' => $email,
            'plan' => 'enterprise',
            'is_active' => true,
        ]);

        $this->info("Creating domain [{$domain}] for tenant [{$id}]...");
        $tenant->domains()->firstOrCreate(['domain' => $domain]);

        // Also register bare subdomain if domain has central domain suffix
        if (str_contains($domain, '.')) {
            $subdomain = explode('.', $domain)[0];
            $tenant->domains()->firstOrCreate(['domain' => $subdomain]);
        } else {
            $tenant->domains()->firstOrCreate(['domain' => "{$domain}.localhost"]);
        }

        $this->info("Tenant [{$id}] and domain [{$domain}] successfully created.");
        $this->info('Database: '.($tenant->tenancy_db_name ?? ('tenant_'.$id)));

        if ($shouldSeed) {
            $this->info("Seeding tenant database for [{$id}]...");
            $tenant->run(function () {
                $this->call(DatabaseSeeder::class);
            });
            $this->info('Tenant database seeded successfully.');
        }

        $this->newLine();
        $this->info('✓ Tenant setup complete!');
        $this->line("• Admin Dashboard: http://{$domain}:8000/admin (or http://{$domain}/admin)");
        $this->line("• Mieterportal:    http://{$domain}:8000/portal/login");
        $this->line('• Admin Login:     admin@kundenportal.local / password');
        $this->line('• Mieter Login:    tenant@kundenportal.local / password');

        return self::SUCCESS;
    }
}

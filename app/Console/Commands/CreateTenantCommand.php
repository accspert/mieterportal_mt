<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CompanyTenant;
use App\Services\CentralLoginService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Console\Command;

class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create
                            {id : The unique identifier of the property management (e.g. demo, acme)}
                            {company_name? : The company / property management name}
                            {--email= : Contact email for the property management}
                            {--seed : Fill the property management with demo data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a property management (all of them share one database)';

    public function handle(): int
    {
        $id = (string) $this->argument('id');
        $companyName = (string) ($this->argument('company_name') ?? $id);
        $shouldSeed = (bool) $this->option('seed');

        if ($shouldSeed && ! DatabaseSeeder::isAllowed()) {
            $this->error('Demo data (--seed) is not allowed in production unless APP_DEMO_MODE=true.');

            return self::FAILURE;
        }

        if (CompanyTenant::find($id)) {
            $this->error("Tenant [{$id}] already exists!");

            return self::FAILURE;
        }

        $this->call(InitialSetupSeeder::class);

        $tenant = CompanyTenant::create([
            'id' => $id,
            'company_name' => $companyName,
            'email' => $this->option('email'),
            'plan' => config('plans.default'),
            'is_active' => true,
        ]);

        $this->info("Property management [{$id}] created.");

        if ($shouldSeed) {
            $tenant->run(fn () => $this->call(DatabaseSeeder::class));
            $this->info('Demo data created.');
        }

        $this->newLine();
        $this->line('• Login: '.CentralLoginService::loginUrl());

        if ($shouldSeed) {
            $this->line('• Admin: admin@kundenportal.local / password');
            $this->line('• Mieter: tenant@kundenportal.local / password');
        }

        return self::SUCCESS;
    }
}

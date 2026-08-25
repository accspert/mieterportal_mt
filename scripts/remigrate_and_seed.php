<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CompanyTenant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

echo "========================================================\n";
echo " MIGRATING & SEEDING ALL TENANTS WITH RENTER MODEL\n";
echo "========================================================\n\n";

foreach (CompanyTenant::all() as $tenant) {
    echo "Processing Tenant: {$tenant->id}...\n";
    $tenant->run(function () use ($tenant) {
        $db = DB::connection()->getDatabaseName();
        echo "  Connected DB: {$db}\n";

        Artisan::call('migrate:fresh', [
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
        echo "  -> Migrations Fresh Completed.\n";

        Artisan::call('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--force' => true,
        ]);
        echo "  -> Seeding Completed.\n";
    });
    echo "\n";
}

echo "========================================================\n";
echo " ALL TENANTS SUCCESSFULLY MIGRATED AND SEEDED!\n";
echo "========================================================\n";

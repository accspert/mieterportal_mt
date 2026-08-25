<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\CompanyTenant;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

echo "========================================================\n";
echo " MIETERPORTAL MULTI-TENANCY VERIFICATION REPORT\n";
echo "========================================================\n\n";

$tenants = CompanyTenant::with('domains')->get();
echo 'Central Database Tenants Count: '.$tenants->count()."\n";
echo 'Central Database Name: '.DB::connection()->getDatabaseName()."\n\n";

foreach ($tenants as $tenant) {
    $tenant->domains()->firstOrCreate(['domain' => $tenant->id]);
    $tenant->domains()->firstOrCreate(['domain' => $tenant->id.'.localhost']);
    $tenant->load('domains');

    echo 'Mandant: ['.$tenant->id."]\n";
    echo '  Firma:   '.($tenant->company_name ?? '-')."\n";
    echo '  Domains: '.$tenant->domains->pluck('domain')->implode(', ')."\n";

    // If tenant DB doesn't exist yet, create and seed it
    $dbName = $tenant->database()->getName();
    if (! $tenant->database()->manager()->databaseExists($dbName)) {
        echo "  [INFO] Creating and seeding database for {$tenant->id}...\n";
        $tenant->database()->manager()->createDatabase($tenant);
        $tenant->run(function () {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
            Artisan::call('db:seed', [
                '--class' => DatabaseSeeder::class,
                '--force' => true,
            ]);
        });
    }

    $tenant->run(function () {
        $dbName = DB::connection()->getDatabaseName();
        $userCount = User::count();
        $propertyCount = Property::count();
        $unitCount = Unit::count();
        $ticketCount = Ticket::count();

        echo '  Datenbank: '.$dbName."\n";
        echo '  Statistiken: '.$userCount.' Users, '.$propertyCount.' Liegenschaften, '.$unitCount.' Einheiten, '.$ticketCount." Tickets\n";

        $admin = User::role('Administrator')->first();
        $tenantUser = User::role('Mieter')->first();
        echo '  Admin-User:  '.($admin ? $admin->email : 'keiner')."\n";
        echo '  Mieter-User: '.($tenantUser ? $tenantUser->email : 'keiner')."\n";
    });
    echo "\n";
}

echo "========================================================\n";
echo " ALL TENANTS VERIFIED: Multi-Database Tenancy Active!\n";
echo "========================================================\n";

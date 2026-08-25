<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CompanyTenant;
use App\Models\Renter;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Ticket;
use App\Models\Invoice;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "========================================================\n";
echo " VERIFYING RENTER REFACTORING ACROSS TENANTS\n";
echo "========================================================\n\n";

$allPassed = true;

foreach (CompanyTenant::all() as $companyTenant) {
    echo "Testing SaaS Tenant: {$companyTenant->id}...\n";

    $companyTenant->run(function () use (&$allPassed, $companyTenant) {
        $dbName = DB::connection()->getDatabaseName();
        echo "  DB: {$dbName}\n";

        // 1. Check tables exist
        $hasRenters = Schema::hasTable('renters');
        $hasOldTenants = Schema::hasTable('tenants');
        echo "  - Table 'renters' exists: " . ($hasRenters ? "YES (OK)" : "NO (FAIL)") . "\n";
        echo "  - Table 'tenants' exists: " . ($hasOldTenants ? "YES (Warning - legacy still present)" : "NO (Clean - Correct)") . "\n";

        if (!$hasRenters || $hasOldTenants) {
            $allPassed = false;
        }

        // 2. Check foreign key columns
        $unitsHasRenterId = Schema::hasColumn('units', 'current_renter_id');
        $ticketsHasRenterId = Schema::hasColumn('tickets', 'renter_id');
        $invoicesHasRenterId = Schema::hasColumn('invoices', 'renter_id');

        echo "  - Column units.current_renter_id: " . ($unitsHasRenterId ? "YES (OK)" : "NO (FAIL)") . "\n";
        echo "  - Column tickets.renter_id: " . ($ticketsHasRenterId ? "YES (OK)" : "NO (FAIL)") . "\n";
        echo "  - Column invoices.renter_id: " . ($invoicesHasRenterId ? "YES (OK)" : "NO (FAIL)") . "\n";

        if (!$unitsHasRenterId || !$ticketsHasRenterId || !$invoicesHasRenterId) {
            $allPassed = false;
        }

        // 3. Count Renter model records
        $renterCount = Renter::count();
        echo "  - Total Renters in DB: {$renterCount}\n";
        if ($renterCount < 1) {
            $allPassed = false;
            echo "  - ERROR: No renters found!\n";
        }

        // 4. Test Renter relations
        $firstRenter = Renter::with(['user', 'units', 'tickets', 'invoices'])->first();
        if ($firstRenter) {
            echo "  - Sample Renter: {$firstRenter->first_name} {$firstRenter->last_name} (Email: {$firstRenter->email})\n";
            echo "    * User relation: " . ($firstRenter->user ? "OK ({$firstRenter->user->email})" : "None") . "\n";
            echo "    * Units assigned: " . $firstRenter->units->count() . "\n";
            echo "    * Tickets count: " . $firstRenter->tickets->count() . "\n";
            echo "    * Invoices count: " . $firstRenter->invoices->count() . "\n";
        }

        // 5. Test Unit -> currentRenter
        $unitWithRenter = Unit::whereNotNull('current_renter_id')->with('currentRenter')->first();
        if ($unitWithRenter) {
            echo "  - Unit #{$unitWithRenter->number} -> currentRenter: " . ($unitWithRenter->currentRenter ? "OK ({$unitWithRenter->currentRenter->last_name})" : "FAIL") . "\n";
            if (!$unitWithRenter->currentRenter) {
                $allPassed = false;
            }
        }

        // 6. Test User -> renter relation
        $tenantUser = User::where('email', 'tenant@kundenportal.local')->first();
        if ($tenantUser) {
            echo "  - User tenant@kundenportal.local -> renter: " . ($tenantUser->renter ? "OK ({$tenantUser->renter->first_name} {$tenantUser->renter->last_name})" : "FAIL") . "\n";
            if (!$tenantUser->renter) {
                $allPassed = false;
            }
        }

        // 7. Backward Compatibility Check: Tenant alias model
        $legacyTenantCount = Tenant::count();
        echo "  - Legacy Tenant::count() alias works: {$legacyTenantCount} (OK)\n";
    });

    echo "\n";
}

echo "========================================================\n";
echo " RESULT: " . ($allPassed ? "ALL CHECKS PASSED SUCCESSFULLY!" : "FAILURES DETECTED!") . "\n";
echo "========================================================\n";

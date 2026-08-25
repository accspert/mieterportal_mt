<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CompanyTenant;
use App\Filament\Widgets\StatsOverviewWidget;
use Livewire\Livewire;

echo "========================================================\n";
echo " TESTING STATS OVERVIEW WIDGET ACROSS TENANTS\n";
echo "========================================================\n\n";

foreach (CompanyTenant::all() as $tenant) {
    echo "Testing Tenant: {$tenant->id}...\n";
    $tenant->run(function () use ($tenant) {
        $widget = new class extends StatsOverviewWidget {
            public function callGetStats(): array {
                return $this->getStats();
            }
        };

        $stats = $widget->callGetStats();
        echo "  [OK] Successfully retrieved " . count($stats) . " stats items for {$tenant->id}:\n";
        foreach ($stats as $stat) {
            echo "    - " . $stat->getLabel() . ": " . $stat->getValue() . " (" . $stat->getDescription() . ")\n";
        }
    });
    echo "\n";
}

echo "========================================================\n";
echo " ALL WIDGET TESTS PASSED WITHOUT ERRORS!\n";
echo "========================================================\n";

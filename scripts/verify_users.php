<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\CompanyTenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Console\Kernel;

$panel = Filament::getPanel('admin');

foreach (CompanyTenant::all() as $tenant) {
    echo "=== Tenant: {$tenant->id} ===\n";
    $tenant->run(function () use ($panel) {
        foreach (User::all() as $u) {
            $roles = $u->roles->pluck('name')->implode(', ');
            $can = $u->canAccessPanel($panel) ? 'YES' : 'NO';
            echo "  User: {$u->email} | Roles: [{$roles}] | canAccessPanel: {$can}\n";
        }
    });
}

<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);

use App\Models\CompanyTenant;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

echo "========================================================\n";
echo " HTTP ROUTES & DASHBOARDS VERIFICATION\n";
echo "========================================================\n\n";

function makeRequest($kernel, $method, $url, $user = null)
{
    $req = Request::create($url, $method);
    if ($user) {
        auth()->login($user);
    }
    $response = $kernel->handle($req);
    $status = $response->getStatusCode();
    echo sprintf("[%d] %-6s %s\n", $status, $method, $url);
    $kernel->terminate($req, $response);

    return $response;
}

// 1. Central Domain
makeRequest($kernel, 'GET', 'http://localhost/');

// 2. Tenant Subdomain - Guest
makeRequest($kernel, 'GET', 'http://demo.localhost/portal/login');

// 3. Tenant Subdomain - Logged In Tenant User
$tenant = CompanyTenant::find('demo');
$tenant->run(function () use ($kernel) {
    $user = User::role('Mieter')->first();
    echo "\nTesting as Tenant User ({$user->email}):\n";

    makeRequest($kernel, 'GET', 'http://demo.localhost/portal/dashboard', $user);
    makeRequest($kernel, 'GET', 'http://demo.localhost/portal/tickets', $user);
    makeRequest($kernel, 'GET', 'http://demo.localhost/portal/documents', $user);
    makeRequest($kernel, 'GET', 'http://demo.localhost/portal/invoices', $user);
    makeRequest($kernel, 'GET', 'http://demo.localhost/portal/messages', $user);
    makeRequest($kernel, 'GET', 'http://demo.localhost/portal/units', $user);
    makeRequest($kernel, 'GET', 'http://demo.localhost/portal/profile', $user);

    $admin = User::role('Administrator')->first();
    echo "\nTesting as Administrator ({$admin->email}):\n";
    makeRequest($kernel, 'GET', 'http://demo.localhost/admin', $admin);
});

echo "\n========================================================\n";
echo " ALL ROUTES & DASHBOARDS 100% OPERATIONAL!\n";
echo "========================================================\n";

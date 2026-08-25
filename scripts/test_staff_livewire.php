<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

echo "========================================================\n";
echo " TESTING STAFF LOGIN, ADMIN ACCESS & LIVEWIRE UPDATES\n";
echo "========================================================\n\n";

// Step 1: GET login page
$req1 = Request::create('http://demo.localhost:8000/portal/login', 'GET');
$res1 = $kernel->handle($req1);
echo '1. GET /portal/login -> Status: '.$res1->getStatusCode()."\n";

$cookies = [];
foreach ($res1->headers->getCookies() as $cookie) {
    $cookies[$cookie->getName()] = $cookie->getValue();
}
$kernel->terminate($req1, $res1);

// Step 2: POST login with staff credentials
$req2 = Request::create('http://demo.localhost:8000/portal/login', 'POST', [
    'email' => 'staff@kundenportal.local',
    'password' => 'password',
    '_token' => session()->token(),
], $cookies, [], [
    'HTTP_HOST' => 'demo.localhost:8000',
]);

$res2 = $kernel->handle($req2);
echo '2. POST /portal/login -> Status: '.$res2->getStatusCode()."\n";
echo '   Redirect Location: '.$res2->headers->get('Location')."\n";

foreach ($res2->headers->getCookies() as $cookie) {
    $cookies[$cookie->getName()] = $cookie->getValue();
}
$kernel->terminate($req2, $res2);

// Step 3: GET /admin
$adminUrl = $res2->headers->get('Location') ?? 'http://demo.localhost:8000/admin';
$req3 = Request::create($adminUrl, 'GET', [], $cookies, [], [
    'HTTP_HOST' => 'demo.localhost:8000',
]);

$res3 = $kernel->handle($req3);
echo "3. GET {$adminUrl} -> Status: ".$res3->getStatusCode()."\n";

foreach ($res3->headers->getCookies() as $cookie) {
    $cookies[$cookie->getName()] = $cookie->getValue();
}
$kernel->terminate($req3, $res3);

// Step 4: POST /livewire/update (simulate Livewire widget refresh/ping)
$req4 = Request::create('http://demo.localhost:8000/livewire/update', 'POST', [
    '_token' => session()->token(),
    'components' => [],
], $cookies, [], [
    'HTTP_HOST' => 'demo.localhost:8000',
    'HTTP_X_LIVEWIRE' => 'true',
    'HTTP_ACCEPT' => 'application/json',
]);

$res4 = $kernel->handle($req4);
echo '4. POST /livewire/update -> Status: '.$res4->getStatusCode()."\n";

if ($res4->getStatusCode() === 200) {
    echo "\n>>> SUCCESS: Livewire update succeeded in tenant context! No logout occurred! <<<\n";
} else {
    echo "\n>>> Livewire response: ".$res4->getContent()." <<<\n";
}

$kernel->terminate($req4, $res4);

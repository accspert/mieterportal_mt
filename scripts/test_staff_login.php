<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

echo "========================================================\n";
echo " TESTING STAFF LOGIN & ADMIN ACCESS FLOW\n";
echo "========================================================\n\n";

// Step 1: GET login page to obtain session & CSRF token
$req1 = Request::create('http://demo.localhost:8000/portal/login', 'GET');
$res1 = $kernel->handle($req1);
echo '1. GET /portal/login -> Status: '.$res1->getStatusCode()."\n";

$cookies = $res1->headers->getCookies();
$cookieHeader = [];
foreach ($cookies as $cookie) {
    $cookieHeader[$cookie->getName()] = $cookie->getValue();
}
$sessionCookie = $cookieHeader[config('session.cookie')] ?? null;
echo '   Session Cookie obtained: '.($sessionCookie ? 'YES' : 'NO')."\n";

$kernel->terminate($req1, $res1);

// Step 2: POST login with staff credentials
$req2 = Request::create('http://demo.localhost:8000/portal/login', 'POST', [
    'email' => 'staff@kundenportal.local',
    'password' => 'password',
    '_token' => session()->token(),
], $cookieHeader, [], [
    'HTTP_HOST' => 'demo.localhost:8000',
]);

$res2 = $kernel->handle($req2);
echo '2. POST /portal/login -> Status: '.$res2->getStatusCode()."\n";
echo '   Redirect Location: '.$res2->headers->get('Location')."\n";

foreach ($res2->headers->getCookies() as $cookie) {
    $cookieHeader[$cookie->getName()] = $cookie->getValue();
}
$kernel->terminate($req2, $res2);

// Step 3: GET /admin following redirect
$adminUrl = $res2->headers->get('Location') ?? 'http://demo.localhost:8000/admin';
$req3 = Request::create($adminUrl, 'GET', [], $cookieHeader, [], [
    'HTTP_HOST' => 'demo.localhost:8000',
]);

$res3 = $kernel->handle($req3);
echo "3. GET {$adminUrl} -> Status: ".$res3->getStatusCode()."\n";

if ($res3->getStatusCode() === 200) {
    echo "\n>>> SUCCESS: Staff user logged in and accessed /admin dashboard without expiration or logout! <<<\n";
} elseif ($res3->getStatusCode() === 302) {
    echo "\n>>> Redirected to: ".$res3->headers->get('Location')." <<<\n";
} else {
    echo "\n>>> Error Status: ".$res3->getStatusCode()." <<<\n";
}

$kernel->terminate($req3, $res3);

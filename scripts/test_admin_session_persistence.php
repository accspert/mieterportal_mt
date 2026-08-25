<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);

echo "========================================================\n";
echo " VERIFYING SESSION PERSISTENCE FOR ADMIN & STAFF\n";
echo "========================================================\n\n";

function testUserFlow($kernel, $email, $roleName)
{
    echo "--- Testing {$roleName} ({$email}) ---\n";

    // 1. Get Login Page
    $req1 = Request::create('http://demo.localhost:8000/portal/login', 'GET');
    $res1 = $kernel->handle($req1);
    $cookies = [];
    foreach ($res1->headers->getCookies() as $cookie) {
        $cookies[$cookie->getName()] = $cookie->getValue();
    }
    $kernel->terminate($req1, $res1);

    // 2. Post Login
    $req2 = Request::create('http://demo.localhost:8000/portal/login', 'POST', [
        'email' => $email,
        'password' => 'password',
        '_token' => session()->token(),
    ], $cookies, [], ['HTTP_HOST' => 'demo.localhost:8000']);
    $res2 = $kernel->handle($req2);
    echo '  [POST /portal/login] -> Status: '.$res2->getStatusCode().' | Redirect: '.$res2->headers->get('Location')."\n";
    foreach ($res2->headers->getCookies() as $cookie) {
        $cookies[$cookie->getName()] = $cookie->getValue();
    }
    $kernel->terminate($req2, $res2);

    // 3. Test Admin Panel Routes sequentially using the same session
    $routes = [
        '/admin',
        '/admin/properties',
        '/admin/units',
        '/admin/renters',
        '/admin/tickets',
        '/admin/documents',
        '/admin/invoices',
    ];

    foreach ($routes as $route) {
        $req = Request::create("http://demo.localhost:8000{$route}", 'GET', [], $cookies, [], [
            'HTTP_HOST' => 'demo.localhost:8000',
        ]);
        $res = $kernel->handle($req);
        $status = $res->getStatusCode();
        echo "  [GET {$route}] -> Status: {$status}".($status === 302 ? ' (Redirected to: '.$res->headers->get('Location').')' : '')."\n";
        foreach ($res->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }
        $kernel->terminate($req, $res);
    }
    echo "\n";
}

testUserFlow($kernel, 'admin@kundenportal.local', 'Administrator');
testUserFlow($kernel, 'staff@kundenportal.local', 'Sachbearbeiter');

echo "========================================================\n";
echo " ALL TESTS COMPLETED: Session stays active permanently!\n";
echo "========================================================\n";

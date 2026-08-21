<?php

// PHP CLI smoke-test for the API
// Usage: php scripts/smoke_test.php [base_url]

$base = $argv[1] ?? 'http://127.0.0.1:8000';

function http_request($method, $url, $data = null, $token = null)
{
    $ch = curl_init();
    $headers = ['Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer '.$token;
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data !== null) {
        $payload = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Curl error: '.$err);
    }
    curl_close($ch);

    $json = json_decode($resp, true);

    return ['code' => $code, 'body' => $json, 'raw' => $resp];
}

try {
    echo "Base URL: {$base}\n";

    // 1) Register a test user
    $testEmail = 'smoke+'.time().'@example.com';
    $pw = 'secret1234';
    echo "Registering user: {$testEmail}\n";
    $reg = http_request('POST', rtrim($base, '/').'/api/auth/register', [
        'name' => 'Smoke Tester',
        'email' => $testEmail,
        'password' => $pw,
        'password_confirmation' => $pw,
    ]);

    if ($reg['code'] === 201 && isset($reg['body']['token'])) {
        $token = $reg['body']['token'];
        echo "Registered and received token.\n";
    } else {
        // try login instead
        echo "Register failed or no token returned (code: {$reg['code']}). Trying login...\n";
        $login = http_request('POST', rtrim($base, '/').'/api/auth/login', [
            'email' => $testEmail,
            'password' => $pw,
        ]);

        if (($login['code'] === 200) && isset($login['body']['token'])) {
            $token = $login['body']['token'];
            echo "Login successful.\n";
        } else {
            echo "Failed to register or login. Response:\n";
            echo $reg['raw']."\n";
            echo $login['raw']."\n";
            exit(1);
        }
    }

    // 2) Create a Property
    echo "Creating property...\n";
    $prop = http_request('POST', rtrim($base, '/').'/api/properties', [
        'name' => 'Smoketest Haus',
        'street' => 'Teststrasse 1',
        'postal_code' => '12345',
        'city' => 'Teststadt',
        'country' => 'DE',
        'description' => 'Automatically created by smoke test',
    ], $token);

    if ($prop['code'] >= 200 && $prop['code'] < 300) {
        $propertyId = $prop['body']['data']['id'] ?? $prop['body']['id'] ?? null;
        echo 'Property created. ID: '.($propertyId ?? '(unknown)')."\n";
    } else {
        echo "Failed to create property. Response code: {$prop['code']}\n";
        echo $prop['raw']."\n";
        exit(1);
    }

    // 3) Create a Ticket
    echo "Creating ticket...\n";
    $ticketPayload = [
        'title' => 'Smoketest Ticket',
        'description' => 'This ticket was created by the smoke-test script.',
        'priority' => 'medium',
    ];
    if (! empty($propertyId)) {
        $ticketPayload['property_id'] = $propertyId;
    }

    $ticket = http_request('POST', rtrim($base, '/').'/api/tickets', $ticketPayload, $token);

    if ($ticket['code'] >= 200 && $ticket['code'] < 300) {
        echo "Ticket created successfully.\n";
        echo json_encode($ticket['body'], JSON_PRETTY_PRINT)."\n";
    } else {
        echo "Failed to create ticket (code: {$ticket['code']}). Response:\n";
        echo $ticket['raw']."\n";
        exit(1);
    }

    echo "Smoke test completed successfully.\n";
    exit(0);
} catch (Exception $e) {
    echo 'Error during smoke test: '.$e->getMessage()."\n";
    exit(1);
}

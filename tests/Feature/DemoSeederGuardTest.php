<?php

use Database\Seeders\DatabaseSeeder;

test('demo data is refused in production', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.demo_mode' => false]);

    expect(fn () => (new DatabaseSeeder)->run())
        ->toThrow(RuntimeException::class, 'APP_DEMO_MODE');

    $this->assertDatabaseMissing('users', ['email' => 'admin@kundenportal.local']);
});

test('demo data is allowed on a production demo installation', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.demo_mode' => true]);

    expect(DatabaseSeeder::isAllowed())->toBeTrue();
});

test('tenant:create refuses demo data in production', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.demo_mode' => false]);

    $this->artisan('tenant:create', ['id' => 'prodtest', '--seed' => true])
        ->expectsOutputToContain('not allowed in production')
        ->assertFailed();

    $this->assertDatabaseMissing('company_tenants', ['id' => 'prodtest']);
});

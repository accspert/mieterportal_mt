<?php

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;

beforeEach(function () {
    $this->seed(InitialSetupSeeder::class);
});

/**
 * phpunit.xml disables the requirement; Filament only registers the set-up route when it is required at boot.
 */
function requireAdminMultiFactorAuthentication(): void
{
    config(['app.admin_mfa_required' => true]);

    require base_path('vendor/filament/filament/routes/web.php');
    app('router')->getRoutes()->refreshNameLookups();
}

test('administrators without a second factor must set one up before using the admin panel', function () {
    requireAdminMultiFactorAuthentication();

    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertRedirect(route('filament.admin.auth.multi-factor-authentication.set-up-required'));
});

test('administrators with email authentication enabled can use the admin panel', function () {
    requireAdminMultiFactorAuthentication();

    $admin = User::factory()->create();
    $admin->assignRole('Administrator');
    $admin->toggleEmailAuthentication(true);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

test('the authenticator app secret is stored encrypted and never serialized', function () {
    $user = User::factory()->create();
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    $storedSecret = DB::table('users')->where('id', $user->id)->value('app_authentication_secret');

    expect($storedSecret)->not->toBe('JBSWY3DPEHPK3PXP')
        ->and($user->fresh()->getAppAuthenticationSecret())->toBe('JBSWY3DPEHPK3PXP')
        ->and($user->fresh()->toArray())->not->toHaveKey('app_authentication_secret');
});

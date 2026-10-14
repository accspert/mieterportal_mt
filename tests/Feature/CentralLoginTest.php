<?php

use App\Models\CompanyTenant;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\CentralLoginService;
use Database\Seeders\InitialSetupSeeder;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\Notifications\VerifyEmailAuthentication;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->seed(InitialSetupSeeder::class);
});

/**
 * Creates a user in its own property management. Real requests start without an active
 * property management, so the context is ended afterwards.
 */
function userOfPropertyManagement(string $tenantId, string $email, string $role = 'Mieter', ?callable $configureUser = null): User
{
    $tenant = CompanyTenant::query()->create(['id' => $tenantId, 'company_name' => "Verwaltung {$tenantId}"]);

    $user = $tenant->run(function () use ($email, $role, $configureUser): User {
        $user = User::create(['name' => 'Testperson', 'email' => $email, 'password' => 'Passwort2026']);
        $user->assignRole($role);

        if ($configureUser) {
            $configureUser($user);
        }

        return $user;
    });

    tenancy()->end();

    return $user;
}

function loginCentrally(string $email, string $password = 'Passwort2026'): TestResponse
{
    return test()->post(route('central.login.attempt'), ['email' => $email, 'password' => $password]);
}

test('renters only enter e-mail and password and land in the portal of their property management', function () {
    userOfPropertyManagement('alpha', 'mieter@example.ch');

    loginCentrally('Mieter@Example.ch')->assertRedirect(route('portal.dashboard'));

    expect(auth()->user()?->email)->toBe('mieter@example.ch');

    $this->get(route('portal.dashboard'))->assertOk();
    expect(tenant()?->getTenantKey())->toBe('alpha');
});

test('administrators land in the admin panel', function () {
    userOfPropertyManagement('alpha', 'chefin@example.ch', 'Administrator');

    loginCentrally('chefin@example.ch')->assertRedirect('/admin');
});

test('wrong passwords and unknown addresses get the same answer', function () {
    userOfPropertyManagement('alpha', 'mieter@example.ch');

    loginCentrally('mieter@example.ch', 'falsch')->assertSessionHasErrors(['email' => 'Die angegebenen Anmeldedaten sind ungültig.']);
    loginCentrally('niemand@example.ch')->assertSessionHasErrors(['email' => 'Die angegebenen Anmeldedaten sind ungültig.']);

    $this->assertGuest();
});

test('accounts of deactivated property managements cannot log in', function () {
    userOfPropertyManagement('alpha', 'mieter@example.ch');
    CompanyTenant::find('alpha')->update(['is_active' => false]);

    loginCentrally('mieter@example.ch')->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('an e-mail address can only have one account on the platform', function () {
    userOfPropertyManagement('alpha', 'doppelt@example.ch');

    expect(fn () => userOfPropertyManagement('beta', 'doppelt@example.ch'))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('there is no selection page for property managements', function () {
    expect(Route::has('central.login.choose'))->toBeFalse();
});

test('accounts with email authentication must enter the mailed code', function () {
    Notification::fake();
    $user = userOfPropertyManagement('mfamail', 'chef@example.ch', 'Administrator', fn (User $user) => $user->toggleEmailAuthentication(true));

    loginCentrally('chef@example.ch')->assertRedirect(route('auth.challenge'));
    $this->assertGuest();

    $code = null;
    Notification::assertSentTo($user, VerifyEmailAuthentication::class, function (VerifyEmailAuthentication $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    $this->get(route('auth.challenge'))->assertOk()->assertSee('Code aus der E-Mail');

    $this->post(route('auth.challenge.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
    $this->assertGuest();

    $this->post(route('auth.challenge.verify'), ['code' => $code])->assertRedirect('/admin');
    expect(auth()->user()?->email)->toBe('chef@example.ch');
});

test('accounts with an authenticator app must enter its code', function () {
    $secret = AppAuthentication::make()->generateSecret();
    userOfPropertyManagement('mfaapp', 'chef@example.ch', 'Administrator', fn (User $user) => $user->saveAppAuthenticationSecret($secret));

    loginCentrally('chef@example.ch')->assertRedirect(route('auth.challenge'));
    $this->assertGuest();

    $this->post(route('auth.challenge.verify'), ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
        ->assertRedirect('/admin');
    expect(auth()->user()?->email)->toBe('chef@example.ch');
});

test('the second factor expires', function () {
    userOfPropertyManagement('mfaold', 'chef@example.ch', 'Administrator', fn (User $user) => $user->toggleEmailAuthentication(true));
    Notification::fake();

    loginCentrally('chef@example.ch')->assertRedirect(route('auth.challenge'));
    $this->travel(11)->minutes();

    $this->get(route('auth.challenge'))->assertRedirect(route('central.login'));
});

test('forgotten passwords are reset with a link to the portal', function () {
    Notification::fake();
    $user = userOfPropertyManagement('reset', 'mieter@example.ch');

    $this->post(route('central.password.email'), ['email' => 'mieter@example.ch'])->assertSessionHas('success');
    $this->post(route('central.password.email'), ['email' => 'niemand@example.ch'])->assertSessionHas('success');

    Notification::assertSentTimes(ResetPasswordNotification::class, 1);
    Notification::assertSentTo($user, ResetPasswordNotification::class, fn (ResetPasswordNotification $notification): bool => str_contains($notification->toMail($user)->actionUrl, '/portal/password/reset/'));
});

test('the header of the landing page links to the single login', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<a class="nav-login" href="'.CentralLoginService::loginUrl().'">Login</a>', false);
});

test('the password on the login page can be shown with the eye button', function () {
    $this->get(route('central.login'))
        ->assertOk()
        ->assertSee('data-password-toggle', false)
        ->assertSee('aria-label="Passwort anzeigen"', false)
        ->assertSee('autocomplete="current-password"', false);
});

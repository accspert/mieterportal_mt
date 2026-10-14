<?php

use App\Models\CompanyTenant;
use App\Models\TenantRegistration;
use App\Models\User;
use App\Notifications\RegistrationConfirmationNotification;
use App\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    // Sign-ups happen without a logged-in user, i.e. without an active property management.
    tenancy()->end();
});

/**
 * @return array<string, mixed>
 */
function registrationPayload(array $overrides = []): array
{
    return [
        'plan' => 'standard',
        'company_name' => 'Rheinblick Immobilien GmbH',
        'admin_name' => 'Laura Meier',
        'email' => 'Laura@Rheinblick.ch',
        'phone' => '+41 61 000 00 00',
        'terms' => '1',
        'website' => '',
        ...$overrides,
    ];
}

function confirmationUrl(TenantRegistration $registration): string
{
    return URL::temporarySignedRoute('central.register.confirm', now()->addHour(), ['registration' => $registration->id]);
}

test('the landing page invites to test for free', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.route('central.register').'"', false)
        ->assertSee('Jetzt kostenlos testen');
});

test('the registration offers the free standard plan', function () {
    $this->get(route('central.register'))
        ->assertOk()
        ->assertSee('Standard')
        ->assertSee('Kostenlos')
        ->assertSee('name="plan" value="standard" checked', false);
});

test('a registration is stored and must be confirmed by e-mail', function () {
    Notification::fake();

    $this->post(route('central.register.store'), registrationPayload())
        ->assertRedirect(route('central.register.sent'));

    $registration = TenantRegistration::sole();

    expect($registration->email)->toBe('laura@rheinblick.ch')
        ->and($registration->plan)->toBe('standard')
        ->and($registration->terms_accepted_at)->not->toBeNull()
        ->and($registration->isConfirmed())->toBeFalse();

    Notification::assertSentOnDemand(RegistrationConfirmationNotification::class, function (RegistrationConfirmationNotification $notification, array $channels, object $notifiable): bool {
        return $notifiable->routes['mail'] === 'laura@rheinblick.ch'
            && str_contains($notification->confirmationUrl, '/registrieren/bestaetigen/')
            && str_contains($notification->confirmationUrl, 'signature=');
    });

    // No property management before the address is confirmed.
    expect(CompanyTenant::whereKeyNot('test')->count())->toBe(0);
});

test('terms, a valid plan and the contact data are required', function () {
    $this->post(route('central.register.store'), registrationPayload([
        'terms' => null,
        'plan' => 'gibt-es-nicht',
        'email' => 'keine-adresse',
        'company_name' => '',
    ]))->assertSessionHasErrors(['terms', 'plan', 'email', 'company_name']);

    expect(TenantRegistration::count())->toBe(0);
});

test('an e-mail address that already has an account cannot register again', function () {
    User::factory()->create(['email' => 'laura@rheinblick.ch']);

    $this->post(route('central.register.store'), registrationPayload())
        ->assertSessionHasErrors(['email' => 'Mit dieser E-Mail-Adresse besteht bereits ein Konto. Bitte melden Sie sich an oder verwenden Sie eine andere Adresse.']);
});

test('bots filling in the honeypot are silently ignored', function () {
    Notification::fake();

    $this->post(route('central.register.store'), registrationPayload(['website' => 'https://spam.example']))
        ->assertRedirect(route('central.register.sent'));

    expect(TenantRegistration::count())->toBe(0);
    Notification::assertNothingSent();
});

test('confirming creates the property management with the chosen plan and invites the administrator', function () {
    Notification::fake();
    $this->post(route('central.register.store'), registrationPayload());
    $registration = TenantRegistration::sole();

    $this->get(confirmationUrl($registration))
        ->assertOk()
        ->assertSee('Rheinblick Immobilien GmbH');

    $tenant = CompanyTenant::findOrFail('rheinblick-immobilien-gmbh');
    $admin = User::where('email', 'laura@rheinblick.ch')->firstOrFail();

    expect($tenant->plan)->toBe('standard')
        ->and($registration->fresh()->tenant_id)->toBe($tenant->id)
        ->and($registration->fresh()->isConfirmed())->toBeTrue()
        ->and($admin->company_tenant_id)->toBe($tenant->id)
        ->and($admin->hasRole('Administrator'))->toBeTrue();

    Notification::assertSentTo($admin, UserInvitationNotification::class);

    // A second click on the link does not create another property management.
    $this->get(confirmationUrl($registration))->assertOk();
    expect(CompanyTenant::where('company_name', 'Rheinblick Immobilien GmbH')->count())->toBe(1);
});

test('the identifier of a new property management never collides with an existing one', function () {
    Notification::fake();
    CompanyTenant::query()->create(['id' => 'rheinblick-immobilien-gmbh']);

    $this->post(route('central.register.store'), registrationPayload());
    $this->get(confirmationUrl(TenantRegistration::sole()))->assertOk();

    expect(CompanyTenant::find('rheinblick-immobilien-gmbh-2'))->not->toBeNull();
});

test('confirmation links must be signed', function () {
    $this->post(route('central.register.store'), registrationPayload());

    $this->get(route('central.register.confirm', ['registration' => TenantRegistration::sole()->id]))
        ->assertForbidden();

    expect(TenantRegistration::sole()->isConfirmed())->toBeFalse();
});

test('unconfirmed registrations are pruned after a week', function () {
    $this->post(route('central.register.store'), registrationPayload());

    $this->travel(TenantRegistration::UNCONFIRMED_RETENTION_DAYS + 1)->days();
    $this->artisan('model:prune', ['--model' => [TenantRegistration::class]])->assertSuccessful();

    expect(TenantRegistration::count())->toBe(0);
});

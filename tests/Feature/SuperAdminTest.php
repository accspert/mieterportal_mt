<?php

use App\Filament\Superadmin\Resources\CompanyTenantResource\Pages\EditCompanyTenant;
use App\Filament\Superadmin\Resources\CompanyTenantResource\Pages\ListCompanyTenants;
use App\Filament\Superadmin\Resources\PlatformUserResource\Pages\ListPlatformUsers;
use App\Filament\Superadmin\Resources\TenantRegistrationResource\Pages\ListTenantRegistrations;
use App\Models\CompanyTenant;
use App\Models\Property;
use App\Models\TenantRegistration;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use App\Services\ImpersonationService;
use Database\Seeders\InitialSetupSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(InitialSetupSeeder::class);
    Notification::fake();

    // Property management "alpha" with an administrator and a property.
    $this->alpha = CompanyTenant::query()->create(['id' => 'alpha', 'company_name' => 'Alpha Immobilien AG']);
    [$this->alphaAdmin, $this->alphaProperty] = $this->alpha->run(function (): array {
        $admin = User::factory()->create(['name' => 'Anna Alpha', 'email' => 'anna@alpha.ch']);
        $admin->assignRole('Administrator');

        return [$admin, Property::factory()->create(['name' => 'Haus Alpha'])];
    });

    // The super admin belongs to no property management; requests start without an active one.
    tenancy()->end();
    $this->superAdmin = User::factory()->create(['name' => 'Betrieb', 'email' => 'betrieb@liegenschaftsportal.ch']);
    $this->superAdmin->forceFill(['is_super_admin' => true])->save();

    Filament::setCurrentPanel(Filament::getPanel('superadmin'));
});

test('superadmin:create creates a platform account and sends the invitation', function () {
    $this->artisan('superadmin:create', ['email' => 'Info@Liegenschaftsportal.ch'])->assertSuccessful();

    $user = User::where('email', 'info@liegenschaftsportal.ch')->firstOrFail();

    expect($user->isSuperAdmin())->toBeTrue()
        ->and($user->company_tenant_id)->toBeNull()
        ->and($user->roles)->toBeEmpty();

    Notification::assertSentTo($user, UserInvitationNotification::class);

    // Running it again changes nothing.
    $this->artisan('superadmin:create', ['email' => 'info@liegenschaftsportal.ch'])->assertSuccessful();
    expect(User::where('email', 'info@liegenschaftsportal.ch')->count())->toBe(1);
});

test('superadmin:create never takes over an account of a property management', function () {
    $this->artisan('superadmin:create', ['email' => 'anna@alpha.ch'])->assertFailed();

    expect($this->alphaAdmin->fresh()->isSuperAdmin())->toBeFalse();
});

test('super admins land in the backoffice after the login', function () {
    $this->superAdmin->update(['password' => 'Passwort2026']);

    $this->post(route('central.login.attempt'), ['email' => 'betrieb@liegenschaftsportal.ch', 'password' => 'Passwort2026'])
        ->assertRedirect('/superadmin');

    $this->get('/superadmin')->assertOk()->assertSee('Verwaltungen');
});

test('only super admins can open the backoffice', function () {
    $this->actingAs($this->alphaAdmin)->get('/superadmin')->assertForbidden();
    auth()->logout();

    $this->get('/superadmin')->assertRedirect();
    $this->actingAs($this->superAdmin)->get('/superadmin')->assertOk();
});

test('super admins see all property managements and all users of the platform', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(ListCompanyTenants::class)
        ->assertCanSeeTableRecords([$this->alpha, CompanyTenant::find('test')]);

    Livewire::actingAs($this->superAdmin)
        ->test(ListPlatformUsers::class)
        ->assertCanSeeTableRecords([$this->alphaAdmin, $this->superAdmin])
        ->assertSee('Alpha Immobilien AG');
});

test('a property management can be blocked and unblocked', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(ListCompanyTenants::class)
        ->callTableAction('toggleActive', $this->alpha);

    expect($this->alpha->fresh()->isActive())->toBeFalse();

    auth()->logout();
    $this->actingAs($this->alphaAdmin)->get('/admin')->assertForbidden();

    // Requesting /admin switched Filament to the admin panel; back to the backoffice.
    tenancy()->end();
    Filament::setCurrentPanel(Filament::getPanel('superadmin'));

    Livewire::actingAs($this->superAdmin)
        ->test(ListCompanyTenants::class)
        ->callTableAction('toggleActive', $this->alpha->fresh());

    expect($this->alpha->fresh()->isActive())->toBeTrue();
});

test('the plan of a property management can be changed', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(EditCompanyTenant::class, ['record' => 'alpha'])
        ->fillForm(['plan' => 'standard', 'company_name' => 'Alpha Immobilien GmbH'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->alpha->fresh()->company_name)->toBe('Alpha Immobilien GmbH');
});

test('deleting a property management removes all its users and records', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(ListCompanyTenants::class)
        ->callTableAction('delete', $this->alpha);

    expect(CompanyTenant::find('alpha'))->toBeNull()
        ->and(User::find($this->alphaAdmin->id))->toBeNull()
        ->and(Property::withTrashed()->find($this->alphaProperty->id))->toBeNull();
});

test('super admins can create a property management and invite its administrator', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(ListCompanyTenants::class)
        ->callAction('createCompanyTenant', data: [
            'company_name' => 'Beta Verwaltung',
            'slug' => 'beta',
            'admin_name' => 'Ben Beta',
            'admin_email' => 'Ben@Beta.ch',
            'plan' => 'standard',
        ])
        ->assertHasNoActionErrors();

    $admin = User::where('email', 'ben@beta.ch')->firstOrFail();

    expect($admin->company_tenant_id)->toBe('beta')
        ->and($admin->hasRole('Administrator'))->toBeTrue();
    Notification::assertSentTo($admin, UserInvitationNotification::class);
});

test('super admins can work in the account of an administrator and return to the backoffice', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(ListCompanyTenants::class)
        ->callTableAction('impersonateAdministrator', $this->alpha)
        ->assertRedirect('/admin');

    expect(auth()->id())->toBe($this->alphaAdmin->id)
        ->and(ImpersonationService::isImpersonating())->toBeTrue();

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Support-Modus')
        ->assertSee('Zurück zum Backoffice');

    $this->get(route('impersonation.stop'))->assertRedirect('/superadmin');

    expect(auth()->id())->toBe($this->superAdmin->id)
        ->and(ImpersonationService::isImpersonating())->toBeFalse();
});

test('super admin accounts cannot be impersonated', function () {
    $otherSuperAdmin = User::factory()->create();
    $otherSuperAdmin->forceFill(['is_super_admin' => true])->save();

    Livewire::actingAs($this->superAdmin)
        ->test(ListPlatformUsers::class)
        ->assertTableActionHidden('impersonate', $otherSuperAdmin)
        ->assertTableActionVisible('impersonate', $this->alphaAdmin);
});

test('the return link does not grant backoffice access to normal users', function () {
    $this->actingAs($this->alphaAdmin)
        ->withSession([ImpersonationService::SESSION_KEY => $this->alphaAdmin->id])
        ->get(route('impersonation.stop'))
        ->assertRedirect();

    $this->assertGuest();
});

test('super admin rights can be granted, but not removed from oneself or the last super admin', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(ListPlatformUsers::class)
        ->assertTableActionHidden('toggleSuperAdmin', $this->superAdmin)
        ->assertTableActionHidden('delete', $this->superAdmin)
        ->callTableAction('toggleSuperAdmin', $this->alphaAdmin);

    expect($this->alphaAdmin->fresh()->isSuperAdmin())->toBeTrue();
});

test('super admins can invite further super admins', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(ListPlatformUsers::class)
        ->callAction('inviteSuperAdmin', data: ['name' => 'Zweiter Betrieb', 'email' => 'zwei@liegenschaftsportal.ch'])
        ->assertNotified('Einladung gesendet');

    $user = User::where('email', 'zwei@liegenschaftsportal.ch')->firstOrFail();

    expect($user->isSuperAdmin())->toBeTrue()->and($user->company_tenant_id)->toBeNull();
});

test('sign-ups are listed in the backoffice', function () {
    $registration = TenantRegistration::query()->create([
        'company_name' => 'Gamma Liegenschaften',
        'admin_name' => 'Gina Gamma',
        'email' => 'gina@gamma.ch',
        'plan' => 'standard',
        'terms_accepted_at' => now(),
    ]);

    Livewire::actingAs($this->superAdmin)
        ->test(ListTenantRegistrations::class)
        ->assertCanSeeTableRecords([$registration]);
});

test('super admins can reset the second factor of a user who lost it', function () {
    $this->alphaAdmin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $this->alphaAdmin->saveAppAuthenticationRecoveryCodes(['code']);

    Livewire::actingAs($this->superAdmin)
        ->test(ListPlatformUsers::class)
        ->assertTableActionHidden('resetMultiFactorAuthentication', $this->superAdmin)
        ->callTableAction('resetMultiFactorAuthentication', $this->alphaAdmin)
        ->assertNotified('Zwei-Faktor-Anmeldung zurückgesetzt');

    $admin = $this->alphaAdmin->fresh();

    expect($admin->hasMultiFactorAuthentication())->toBeFalse()
        ->and($admin->getAppAuthenticationRecoveryCodes())->toBeNull();

    // The next login only needs the password; the admin panel then asks to set up the second factor again.
    $admin->update(['password' => 'Passwort2026']);
    auth()->logout();
    $this->post(route('central.login.attempt'), ['email' => 'anna@alpha.ch', 'password' => 'Passwort2026'])
        ->assertRedirect('/admin');
});

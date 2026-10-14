<?php

use App\Filament\Resources\OwnerResource\Pages\ListOwners;
use App\Filament\Resources\RenterResource\Pages\ListRenters;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\CompanyTenant;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\UserInvitationNotification;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(InitialSetupSeeder::class);
    Notification::fake();

    $this->admin = User::factory()->create(['email' => 'admin@verwaltung.ch']);
    $this->admin->assignRole('Administrator');

    $this->staff = User::factory()->create(['email' => 'staff@verwaltung.ch']);
    $this->staff->assignRole('Sachbearbeiter');
});

test('staff invite a renter, who sets the password personally', function () {
    $renter = Tenant::factory()->create(['first_name' => 'Lea', 'last_name' => 'Keller', 'email' => 'lea@example.ch']);

    Livewire::actingAs($this->staff)
        ->test(ListRenters::class)
        ->callTableAction('invitePortalAccess', $renter, data: [
            'name' => 'Lea Keller',
            'email' => 'Lea@Example.ch',
            'manager_id' => $this->staff->id,
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified('Einladung gesendet');

    $user = User::where('email', 'lea@example.ch')->firstOrFail();

    expect($renter->fresh()->user_id)->toBe($user->id)
        ->and($renter->fresh()->manager_id)->toBe($this->staff->id)
        ->and($user->hasRole('Mieter'))->toBeTrue()
        ->and($user->company_tenant_id)->toBe('test');

    Notification::assertSentTo($user, UserInvitationNotification::class);
});

test('inviting never takes over an existing account of the property management', function () {
    $renter = Tenant::factory()->create(['email' => 'mieter@example.ch']);
    $adminPasswordHash = $this->admin->password;

    Livewire::actingAs($this->staff)
        ->test(ListRenters::class)
        ->callTableAction('invitePortalAccess', $renter, data: [
            'name' => 'Angreifer',
            'email' => 'admin@verwaltung.ch',
            'manager_id' => $this->staff->id,
        ])
        ->assertNotified('Kein Zugang angelegt');

    $admin = $this->admin->fresh();

    expect($admin->password)->toBe($adminPasswordHash)
        ->and($admin->name)->not->toBe('Angreifer')
        ->and($admin->hasRole('Mieter'))->toBeFalse()
        ->and($renter->fresh()->user_id)->toBeNull();

    Notification::assertNothingSentTo($admin);
});

test('an address used at another property management is rejected with a message instead of an error', function () {
    CompanyTenant::query()->create(['id' => 'andere'])->run(fn () => User::factory()->create(['email' => 'vergeben@example.ch']));
    $renter = Tenant::factory()->create(['email' => 'vergeben@example.ch']);

    Livewire::actingAs($this->staff)
        ->test(ListRenters::class)
        ->callTableAction('invitePortalAccess', $renter, data: [
            'name' => 'Max Muster',
            'email' => 'vergeben@example.ch',
            'manager_id' => $this->staff->id,
        ])
        ->assertNotified('Kein Zugang angelegt');

    expect($renter->fresh()->user_id)->toBeNull()
        ->and(User::withoutGlobalScopes()->where('email', 'vergeben@example.ch')->count())->toBe(1);
});

test('selected renters are invited in bulk, renters without e-mail are reported', function () {
    $withEmail = Tenant::factory()->create(['email' => 'eins@example.ch']);
    $alsoWithEmail = Tenant::factory()->create(['email' => 'zwei@example.ch']);
    $withoutEmail = Tenant::factory()->create(['email' => null]);

    Livewire::actingAs($this->staff)
        ->test(ListRenters::class)
        ->callTableBulkAction('inviteSelectedPortalAccess', [$withEmail, $alsoWithEmail, $withoutEmail])
        ->assertNotified('2 Einladungen gesendet');

    expect($withEmail->fresh()->user_id)->not->toBeNull()
        ->and($alsoWithEmail->fresh()->user_id)->not->toBeNull()
        ->and($withoutEmail->fresh()->user_id)->toBeNull();

    Notification::assertSentTimes(UserInvitationNotification::class, 2);
});

test('staff invite an owner, who then sees the own properties in the portal', function () {
    $owner = Owner::factory()->create(['first_name' => 'Peter', 'last_name' => 'Huber', 'email' => 'peter.huber@example.ch']);
    Property::factory()->create(['owner_id' => $owner->id, 'name' => 'Wohnhaus Rheinweg']);

    Livewire::actingAs($this->staff)
        ->test(ListOwners::class)
        ->callTableAction('invitePortalAccess', $owner, data: [
            'name' => 'Peter Huber',
            'email' => 'peter.huber@example.ch',
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified('Einladung gesendet');

    $user = User::where('email', 'peter.huber@example.ch')->firstOrFail();

    expect($owner->fresh()->user_id)->toBe($user->id)
        ->and($user->hasRole('Eigentümer'))->toBeTrue();

    Notification::assertSentTo($user, UserInvitationNotification::class);

    $this->actingAs($user)->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee('Wohnhaus Rheinweg');
});

test('selected owners are invited in bulk', function () {
    $owners = Owner::factory()->count(3)->create();

    Livewire::actingAs($this->admin)
        ->test(ListOwners::class)
        ->callTableBulkAction('inviteSelectedPortalAccess', $owners)
        ->assertNotified('3 Einladungen gesendet');

    $owners->each(fn (Owner $owner) => expect($owner->fresh()->user?->hasRole('Eigentümer'))->toBeTrue());
});

test('staff send a password link instead of seeing or setting passwords', function () {
    $user = User::factory()->create(['email' => 'mieter@example.ch']);
    $user->assignRole('Mieter');
    $renter = Tenant::factory()->create(['user_id' => $user->id, 'email' => 'mieter@example.ch']);
    $passwordHash = $user->password;

    Livewire::actingAs($this->staff)
        ->test(ListRenters::class)
        ->assertTableActionHidden('invitePortalAccess', $renter)
        ->callTableAction('sendPasswordLink', $renter)
        ->assertNotified('Passwort-Link gesendet');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
    expect($user->fresh()->password)->toBe($passwordHash);
});

test('owners with an account get a password link too', function () {
    $user = User::factory()->create();
    $owner = Owner::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($this->staff)
        ->test(ListOwners::class)
        ->callTableAction('sendPasswordLink', $owner)
        ->assertNotified('Passwort-Link gesendet');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('the old actions that showed or overwrote passwords are gone', function () {
    $renter = Tenant::factory()->create(['email' => 'x@example.ch']);

    Livewire::actingAs($this->staff)
        ->test(ListRenters::class)
        ->assertTableActionDoesNotExist('createUserAccount')
        ->assertTableActionDoesNotExist('resetPassword')
        ->assertTableBulkActionDoesNotExist('createSelectedUserAccounts');
});

test('administrators invite staff without setting a password', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateUser::class)
        ->assertFormFieldDoesNotExist('password')
        ->fillForm([
            'name' => 'Nina Brunner',
            'email' => 'Nina@Verwaltung.ch',
            'roles' => [Role::findByName('Sachbearbeiter')->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'nina@verwaltung.ch')->firstOrFail();

    expect($user->hasRole('Sachbearbeiter'))->toBeTrue()
        ->and($user->company_tenant_id)->toBe('test');

    Notification::assertSentTo($user, UserInvitationNotification::class);
});

test('staff accounts cannot be created with renter or owner roles', function () {
    $renterRole = Role::findByName('Mieter');

    Livewire::actingAs($this->admin)
        ->test(CreateUser::class)
        ->fillForm([
            'name' => 'Falsch',
            'email' => 'falsch@verwaltung.ch',
            'roles' => [$renterRole->id],
        ])
        ->call('create')
        ->assertHasFormErrors(['roles.0']);

    expect(User::where('email', 'falsch@verwaltung.ch')->exists())->toBeFalse();
});

test('administrators can send staff a password link', function () {
    Livewire::actingAs($this->admin)
        ->test(ListUsers::class)
        ->callTableAction('sendPasswordLink', $this->staff)
        ->assertNotified('Passwort-Link gesendet');

    Notification::assertSentTo($this->staff, ResetPasswordNotification::class);
});

test('an invited person sets the password and logs in with the single login', function () {
    $renter = Tenant::factory()->create(['email' => 'neu@example.ch']);

    Livewire::actingAs($this->staff)
        ->test(ListRenters::class)
        ->callTableAction('invitePortalAccess', $renter, data: ['name' => 'Neu Mieter', 'email' => 'neu@example.ch', 'manager_id' => $this->staff->id]);

    $user = User::where('email', 'neu@example.ch')->firstOrFail();
    $token = null;
    Notification::assertSentTo($user, UserInvitationNotification::class, function (UserInvitationNotification $notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });

    auth()->logout();
    tenancy()->end();

    $this->post(route('portal.password.update'), [
        'token' => $token,
        'email' => 'neu@example.ch',
        'invitation' => 1,
        'password' => 'MeinPasswort2026',
        'password_confirmation' => 'MeinPasswort2026',
    ])->assertRedirect();

    expect(Hash::check('MeinPasswort2026', $user->fresh()->password))->toBeTrue();

    $this->post(route('central.login.attempt'), ['email' => 'neu@example.ch', 'password' => 'MeinPasswort2026'])
        ->assertRedirect(route('portal.dashboard'));
});

<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\UserInvitationNotification;
use App\Services\CentralLoginService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('the central login links to the forgot password form', function () {
    $this->get(route('central.login'))
        ->assertOk()
        ->assertSee(route('central.password.request'), false);

    $this->get(route('central.password.request'))
        ->assertOk()
        ->assertSee('Passwort');
});

test('the reset mail links to the reset form on the tenant domain', function () {
    $user = User::factory()->create();
    $notification = new ResetPasswordNotification('reset-token');

    expect($notification)->toBeInstanceOf(ShouldQueue::class)
        ->and($notification->toMail($user)->actionUrl)->toContain('/portal/password/reset/reset-token');
});

test('a valid token sets the new password, revokes api tokens and returns to the central login', function () {
    $user = User::factory()->create(['email' => 'mieter@example.ch']);
    $user->createToken('app');
    $token = Password::broker()->createToken($user);

    $this->get(route('portal.password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('Neues Passwort');

    $this->post(route('portal.password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NeuesPasswort2026',
        'password_confirmation' => 'NeuesPasswort2026',
    ])->assertRedirect(CentralLoginService::loginUrl(['passwort' => 1]));

    expect(Hash::check('NeuesPasswort2026', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);
});

test('an invalid token is rejected', function () {
    $user = User::factory()->create();

    $this->post(route('portal.password.update'), [
        'token' => 'ungueltig',
        'email' => $user->email,
        'password' => 'NeuesPasswort2026',
        'password_confirmation' => 'NeuesPasswort2026',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('NeuesPasswort2026', $user->fresh()->password))->toBeFalse();
});

test('weak passwords are rejected', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->post(route('portal.password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'kurz',
        'password_confirmation' => 'kurz',
    ])->assertSessionHasErrors('password');
});

test('an invitation link lets a new user set the first password', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'neu@example.ch']);

    $user->sendInvitation('Testverwaltung AG');

    $invitationToken = null;
    Notification::assertSentTo($user, UserInvitationNotification::class, function (UserInvitationNotification $notification) use ($user, &$invitationToken): bool {
        $invitationToken = $notification->token;

        return str_contains($notification->toMail($user)->actionUrl, 'invitation=1');
    });

    $this->get(route('portal.password.reset', ['token' => $invitationToken, 'email' => $user->email, 'invitation' => 1]))
        ->assertOk()
        ->assertSee('Passwort festlegen');

    // Invitation tokens are only valid through the invitation broker.
    $this->post(route('portal.password.update'), [
        'token' => $invitationToken,
        'email' => $user->email,
        'password' => 'MeinStartPasswort1',
        'password_confirmation' => 'MeinStartPasswort1',
    ])->assertSessionHasErrors('email');

    $this->post(route('portal.password.update'), [
        'token' => $invitationToken,
        'email' => $user->email,
        'invitation' => 1,
        'password' => 'MeinStartPasswort1',
        'password_confirmation' => 'MeinStartPasswort1',
    ])->assertRedirect(CentralLoginService::loginUrl(['passwort' => 1]));

    expect(Hash::check('MeinStartPasswort1', $user->fresh()->password))->toBeTrue();
});

test('invitation tokens survive the hourly cleanup of password reset tokens', function () {
    $user = User::factory()->create();
    $invitationToken = Password::broker('invitations')->createToken($user);

    $this->travel(2)->days();
    $this->artisan('auth:clear-resets')->assertSuccessful();

    expect(Password::broker('invitations')->tokenExists($user, $invitationToken))->toBeTrue();
});

test('the admin panel offers a password reset', function () {
    $this->get('/admin/password-reset/request')->assertOk();
});

test('the page to set a password uses the design of the website', function (bool $isInvitation, string $heading) {
    $this->get(route('portal.password.reset', ['token' => 'abc', 'email' => 'neu@example.ch', 'invitation' => $isInvitation ? 1 : null]))
        ->assertOk()
        ->assertSee('css/landing.css', false)
        ->assertSee('class="form-card"', false)
        ->assertSee($heading, false)
        ->assertSee('id="password_confirmation"', false)
        ->assertSee('data-password-toggle', false)
        ->assertDontSee('css/portal.css', false);
})->with([
    'invitation' => [true, 'Willkommen <span class="serif">an Bord.</span>'],
    'password reset' => [false, 'Neues <span class="serif">Passwort.</span>'],
]);

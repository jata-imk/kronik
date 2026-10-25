<?php

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\UserInvitation;
use App\Services\UserInvitationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

function invitationInput(User $user, string $token): array
{
    return ['email' => $user->email, 'token' => $token, 'password' => 'una-clave-segura', 'password_confirmation' => 'una-clave-segura'];
}

test('invitation remains valid after several hours and activates only once', function () {
    $user = User::factory()->create(['status' => UserStatus::Pending, 'email_verified_at' => null]);
    $token = Password::broker('invitaciones')->createToken($user);
    $this->travel(71)->hours();
    $this->post(route('invitations.accept'), invitationInput($user, $token))->assertRedirect(route('login'))->assertSessionHasNoErrors();
    expect($user->fresh()->status)->toBe(UserStatus::Active)->and($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->post(route('invitations.accept'), invitationInput($user, $token))->assertSessionHasErrors('email');
});

test('invitation expires at its configured duration and errors are readable', function () {
    config(['auth.passwords.invitaciones.expire' => 90]);
    $user = User::factory()->create(['status' => UserStatus::Pending]);
    $token = Password::broker('invitaciones')->createToken($user);
    $this->travel(91)->minutes();
    $this->post(route('invitations.accept'), invitationInput($user, $token))->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('invitación')->not->toContain('validation.')
        ->and($user->fresh()->status)->toBe(UserStatus::Pending);
});

test('normal recovery still expires after sixty minutes and cannot consume invitation tokens', function () {
    $active = User::factory()->create();
    $pending = User::factory()->create(['status' => UserStatus::Pending]);
    $reset = Password::broker('users')->createToken($active);
    $invite = Password::broker('invitaciones')->createToken($pending);
    $this->post('/reset-password', invitationInput($pending, $invite))->assertSessionHasErrors('email');
    $this->post(route('invitations.accept'), invitationInput($active, $reset))->assertSessionHasErrors('email');
    $this->travel(61)->minutes();
    $this->post('/reset-password', invitationInput($active, $reset))->assertSessionHasErrors('email');
    expect(Hash::check('password', $active->fresh()->password))->toBeTrue();
});

test('resend is throttled with a wait message and replaces invitation and legacy tokens', function () {
    Notification::fake();
    $admin = actingAsSuperAdmin();
    $user = User::factory()->create(['status' => UserStatus::Pending]);
    $legacy = Password::broker('users')->createToken($user);
    app(UserInvitationService::class)->send($user);
    $first = Notification::sent($user, UserInvitation::class)->last()->token;
    $sentAt = $user->fresh()->invited_at->toIso8601String();
    $this->actingAs($admin)->post(route('admin.users.invitation.resend', $user))->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe('Espera un minuto antes de reenviar la invitación.')
        ->and($user->fresh()->invited_at->toIso8601String())->toBe($sentAt);
    $this->travel(61)->seconds();
    $this->post(route('admin.users.invitation.resend', $user))->assertSessionHasNoErrors();
    expect(Password::broker('invitaciones')->tokenExists($user, $first))->toBeFalse()
        ->and(Password::broker('users')->tokenExists($user, $legacy))->toBeFalse();
});

test('an invitation cannot reset an active or inactive account', function ($status) {
    $user = User::factory()->create(['status' => UserStatus::Pending]);
    $token = Password::broker('invitaciones')->createToken($user);
    $user->forceFill(['status' => $status])->save();
    $this->post(route('invitations.accept'), invitationInput($user, $token))->assertSessionHasErrors('email');
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
})->with([UserStatus::Active, UserStatus::Inactive]);

test('legacy reset links retain their short lifetime and activation retires both flows', function () {
    $user = User::factory()->create(['status' => UserStatus::Pending]);
    $legacy = Password::broker('users')->createToken($user);
    $invite = Password::broker('invitaciones')->createToken($user);
    $this->post('/reset-password', invitationInput($user, $legacy))->assertSessionHasNoErrors();
    expect(Password::broker('invitaciones')->tokenExists($user, $invite))->toBeFalse();
});

test('invitation detail selects the acceptance route and listing exposes actual expiry', function () {
    $user = User::factory()->create(['status' => UserStatus::Pending]);
    $token = Password::broker('invitaciones')->createToken($user);
    $this->get(route('invitations.show', ['token' => $token, 'email' => $user->email]))
        ->assertInertia(fn (Assert $page) => $page->component('Auth/ResetPassword')->where('invitation', true));
    $details = app(UserInvitationService::class)->details($user);
    expect($details['invitation_expired'])->toBeFalse()->and($details['invitation_expires_at'])->not->toBeNull();
    $this->travel(73)->hours();
    expect(app(UserInvitationService::class)->details($user)['invitation_expired'])->toBeTrue();
});

test('historical Jetstream invitation URLs cannot accept even existing invitations', function () {
    $admin = actingAsSuperAdmin();
    $invitation = $admin->currentTeam->teamInvitations()->create(['email' => 'old@example.test', 'role' => 'admin']);
    $this->actingAs($admin)->get(URL::signedRoute('team-invitations.accept', $invitation))->assertNotFound();
    $this->delete(route('team-invitations.destroy', $invitation))->assertNotFound();
    expect($invitation->fresh())->not->toBeNull();
});

test('failed delivery exposes a readable error and does not advertise an unsent token', function () {
    $admin = actingAsSuperAdmin();
    $user = User::factory()->create(['status' => UserStatus::Pending, 'invited_at' => null]);
    Notification::shouldReceive('send')->once()->andThrow(new Symfony\Component\Mailer\Exception\TransportException('Synthetic failure'));
    $this->actingAs($admin)->post(route('admin.users.invitation.resend', $user))->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('No se pudo enviar')->not->toContain('Synthetic failure')
        ->and($user->fresh()->invited_at)->toBeNull()
        ->and(app(UserInvitationService::class)->details($user)['invitation_expired'])->toBeTrue();
});

test('invalid acceptance input uses Spanish validation messages', function () {
    $this->post(route('invitations.accept'), ['token' => '', 'email' => 'incorrecto', 'password' => ''])->assertSessionHasErrors(['email', 'password', 'token']);
    foreach (session('errors')->all() as $message) {
        expect($message)->not->toContain('validation.');
    }
    expect(session('errors')->first('token'))->toContain('enlace de acceso');
});

test('a valid short recovery link remains visible after the administrative invitation expires', function () {
    $user = User::factory()->create(['status' => UserStatus::Pending]);
    Password::broker('invitaciones')->createToken($user);
    $this->travel(73)->hours();
    Password::broker('users')->createToken($user);
    $details = app(UserInvitationService::class)->details($user);
    expect($details['invitation_expired'])->toBeFalse()
        ->and($details['invitation_expires_at'])->toBe(now()->addHour()->toIso8601String());
});

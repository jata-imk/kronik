<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

beforeEach(function () {
    config([
        'app.name' => 'Kronik QA',
        'mail.default' => 'array',
        'mail.from.address' => 'acceso@example.test',
        'mail.from.name' => 'Equipo Kronik QA',
    ]);
});

function lastAccountEmail(): Symfony\Component\Mime\Email
{
    return Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
}

function accountActionUrl(Symfony\Component\Mime\Email $message): string
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$message->getHtmlBody());

    return $document->getElementsByTagName('a')->item(0)->getAttribute('href');
}

test('admin invitation and resend deliver branded multipart emails and the invitation activates only once', function () {
    $admin = actingAsSuperAdmin();
    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Contadora invitada',
        'email' => 'contadora+qa@example.test',
        'current_team_id' => $admin->current_team_id,
        'team_roles' => [['team_id' => $admin->current_team_id, 'role_ids' => []]],
        'sucursal_ids' => [$admin->current_sucursal_id],
        'sucursal_principal_id' => $admin->current_sucursal_id,
        'is_super_admin' => false,
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'contadora+qa@example.test')->firstOrFail();
    $first = lastAccountEmail();
    expect($first->getSubject())->toBe('Te invitaron a Kronik QA')
        ->and($first->getTo()[0]->getAddress())->toBe($user->email)
        ->and($first->getFrom()[0]->getName())->toBe('Equipo Kronik QA')
        ->and($first->getHtmlBody())->toContain('Crear mi contraseña', 'Tu equipo te espera.', '72 horas')
        ->and($first->getTextBody())->toContain('Contadora invitada', 'Crear mi contraseña', '72 horas')
        ->and($first->toString())->toContain('multipart/alternative', 'text/plain', 'text/html')
        ->and($user->status)->toBe(UserStatus::Pending);

    $firstUrl = accountActionUrl($first);
    parse_str(parse_url($firstUrl, PHP_URL_QUERY), $query);
    expect($query['email'])->toBe('contadora+qa@example.test');
    $this->app['auth']->forgetGuards();
    // Simula el contenedor nuevo de la petición del destinatario.
    Inertia::flushShared();
    $this->get($firstUrl)->assertOk();

    $this->travel(61)->seconds();
    $this->actingAs($admin)->post(route('admin.users.invitation.resend', $user))->assertSessionHasNoErrors();
    $message = lastAccountEmail();
    expect($message->getSubject())->toBe('Te invitaron a Kronik QA');
    $token = basename(parse_url(accountActionUrl($message), PHP_URL_PATH));
    expect(Password::broker('invitaciones')->tokenExists($user, basename(parse_url($firstUrl, PHP_URL_PATH))))->toBeFalse();

    $this->app['auth']->forgetGuards();
    Inertia::flushShared();
    $this->post('/invitaciones', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nueva-clave-segura',
        'password_confirmation' => 'nueva-clave-segura',
    ])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->status)->toBe(UserStatus::Active)
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('nueva-clave-segura', $user->password))->toBeTrue()
        ->and(Password::broker('invitaciones')->tokenExists($user, $token))->toBeFalse();
});

test('active user receives recovery wording and a valid token with the configured expiry', function () {
    config(['auth.passwords.users.expire' => 15]);
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();

    $message = lastAccountEmail();
    $url = accountActionUrl($message);
    $token = basename(parse_url($url, PHP_URL_PATH));
    expect($message->getSubject())->toBe('Restablece tu contraseña en Kronik QA')
        ->and($message->getHtmlBody())->toContain('Restablecer contraseña', '15 minutos', 'Si no solicitaste este cambio')
        ->not->toContain('Tu equipo te espera.', 'Crear mi contraseña')
        ->and($message->getTextBody())->toContain($url, '15 minutos')
        ->and(Password::tokenExists($user, $token))->toBeTrue();

    $this->travel(16)->minutes();
    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nueva-clave-segura',
        'password_confirmation' => 'nueva-clave-segura',
    ])->assertSessionHasErrors('email');
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('changing profile email sends a branded signed verification link for the new address', function () {
    config(['auth.verification.expire' => 20]);
    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/profile-information', [
        'name' => 'Andrea Martínez', 'email' => 'andrea.nueva@example.test',
    ])->assertSessionHasNoErrors();

    $user->refresh();
    $message = lastAccountEmail();
    $url = accountActionUrl($message);
    expect($message->getSubject())->toBe('Confirma tu correo en Kronik QA')
        ->and($message->getTo()[0]->getAddress())->toBe('andrea.nueva@example.test')
        ->and($message->getHtmlBody())->toContain('Verificar mi correo', '20 minutos', 'andrea.nueva@example.test')
        ->and($message->getTextBody())->toContain($url, 'inicia sesión')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    $this->get($url.'&tampered=1')->assertForbidden();
    $this->get($url)->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('resending verification uses the same design and expired links cannot verify an account', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->post('/email/verification-notification')->assertSessionHasNoErrors();

    $message = lastAccountEmail();
    $url = accountActionUrl($message);
    expect($message->getSubject())->toBe('Confirma tu correo en Kronik QA');
    $this->travel(61)->minutes();
    $this->get($url)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('HTML escapes personal data while plain text preserves readable characters and exact links', function () {
    config(['app.name' => 'Crédito & Equipo <Pruebas>']);
    $user = new User([
        'name' => '<img src=x onerror=alert(1)> Ana & José',
        'email' => 'ana+pruebas@example.test',
        'status' => UserStatus::Active,
    ]);
    $user->notify(new ResetPassword('TOKEN-FICTICIO'));
    $message = lastAccountEmail();
    expect($message->getHtmlBody())->toContain('&lt;img', 'Ana &amp; José', 'Crédito &amp; Equipo &lt;Pruebas&gt;')
        ->not->toContain('<img src=x', '<script')
        ->and($message->getTextBody())->toContain('Crédito & Equipo <Pruebas>', 'Ana & José')
        ->not->toContain('&amp;', '&lt;')
        ->and($message->getHtmlBody())->toContain('role="presentation"', '<!--[if mso]>', '<v:roundrect', 'font-family:Arial,Helvetica,sans-serif');
    $url = accountActionUrl($message);
    expect($message->getTextBody())->toContain($url);
    preg_match('/<v:roundrect[^>]+href="([^"]+)"/', $message->getHtmlBody(), $outlookLink);
    expect(html_entity_decode($outlookLink[1], ENT_QUOTES, 'UTF-8'))->toBe($url);
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    expect($query['email'])->toBe($user->email);
});

<?php

use App\Enums\ActivityEvent;
use App\Enums\UserStatus;
use App\Models\Activity;
use App\Models\Permission;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityLogService;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Spatie\Permission\Models\Role;

test('crear equipo por administración o Jetstream conserva actor sucursal y copia de roles sin duplicados', function (bool $jetstream) {
    $actor = actingAsSuperAdmin();
    $originalTeam = $actor->current_team_id;
    $branch = $actor->current_sucursal_id;
    $template = Role::query()->create(['name' => 'Nombre reservado del rol', 'team_id' => null, 'guard_name' => 'web']);
    $template->givePermissionTo(Permission::findOrCreate('read clientes', 'web'));
    $this->actingAs($actor)->post($jetstream ? '/teams' : route('admin.teams.store'), ['name' => 'Nombre reservado del equipo'])
        ->assertRedirect()->assertSessionHasNoErrors();
    $team = Team::where('name', 'Nombre reservado del equipo')->sole();
    $event = Activity::where('event', ActivityEvent::TeamCreated->value)->sole();
    expect($event->causer_id)->toBe($actor->id)->and($event->subject_id)->toBe($team->id)
        ->and($event->team_id)->toBe($team->id)->and($event->sucursal_id)->toBe($branch)
        ->and($actor->fresh()->current_team_id)->toBe($jetstream ? $team->id : $originalTeam);
    $copied = Activity::where('event', ActivityEvent::RoleCreated->value)->sole();
    expect($copied->team_id)->toBe($team->id)->and($copied->sucursal_id)->toBe($branch)
        ->and($copied->properties['result'])->toBe('copied')
        ->and(Activity::where('event', ActivityEvent::RolePermissionsUpdated->value)->count())->toBe(0)
        ->and($event->toJson().$copied->toJson())->not->toContain('Nombre reservado', 'read clientes');
})->with([false, true]);

test('equipo seleccionado permite delegado audita cambios efectivos y restaura contexto', function () {
    $actor = actingAsSuperAdmin();
    $actor->forceFill(['is_super_admin' => false])->save();
    $selected = Team::factory()->create();
    $selected->users()->attach($actor);
    grantTeamPermissions($actor, $selected, ['update teams', 'delete teams']);
    $original = $actor->current_team_id;
    $this->actingAs($actor)->put(route('admin.teams.update', $selected), ['name' => 'Renombrado'])->assertSessionHasNoErrors();
    $this->put(route('admin.teams.update', $selected), ['name' => 'Renombrado'])->assertSessionHasNoErrors();
    $this->delete(route('admin.teams.destroy', $selected))->assertSessionHasNoErrors();
    $this->delete(route('admin.teams.destroy', $selected))->assertSessionHasNoErrors();
    $this->put(route('admin.teams.update', $selected), ['name' => 'Renombrado', 'activo' => true])->assertSessionHasNoErrors();
    foreach ([ActivityEvent::TeamUpdated, ActivityEvent::TeamDeactivated, ActivityEvent::TeamReactivated] as $code) {
        $event = Activity::where('event', $code->value)->sole();
        expect($event->causer_id)->toBe($actor->id)->and($event->team_id)->toBe($selected->id)
            ->and($event->sucursal_id)->toBe($actor->current_sucursal_id);
    }
    expect(getPermissionsTeamId())->toBe($original)->and($actor->fresh()->current_team_id)->toBe($original);
    $foreign = Team::factory()->create();
    $this->put(route('admin.teams.update', $foreign), ['name' => 'No permitido'])->assertForbidden();
    expect(Activity::where('event', ActivityEvent::TeamUpdated->value)->count())->toBe(1);
});

test('CRUD de roles separa permisos de nombre y no emite ante no-op o rechazo', function () {
    $actor = actingAsSuperAdmin();
    $selected = Team::factory()->create();
    $permissions = Permission::findOrCreate('read clientes', 'web');
    $this->actingAs($actor)->post(route('admin.roles.store', ['team' => $selected->id]), ['name' => 'Nombre reservado'])->assertSessionHasNoErrors();
    $role = Role::where('team_id', $selected->id)->sole();
    $url = route('admin.roles.update', ['role' => $role->id, 'team' => $selected->id]);
    $this->put($url, ['name' => 'Modificado', 'permissions' => [$permissions->id]])->assertSessionHasNoErrors();
    $this->put($url, ['name' => 'Modificado', 'permissions' => [$permissions->id]])->assertSessionHasNoErrors();
    $delete = route('admin.roles.destroy', ['role' => $role->id, 'team' => $selected->id]);
    $this->delete($delete)->assertSessionHasErrors('message');
    expect(Activity::where('event', ActivityEvent::RoleDeleted->value)->count())->toBe(0);
    $this->put($url, ['name' => 'Modificado', 'permissions' => []])->assertSessionHasNoErrors();
    $this->delete($delete)->assertSessionHasNoErrors();
    expect(Activity::where('event', ActivityEvent::RoleUpdated->value)->count())->toBe(1)
        ->and(Activity::where('event', ActivityEvent::RolePermissionsUpdated->value)->count())->toBe(2);
    foreach (Activity::whereIn('event', ['role.created', 'role.updated', 'role.deleted', 'role.permissions.updated'])->get() as $event) {
        expect($event->causer_id)->toBe($actor->id)->and($event->team_id)->toBe($selected->id)
            ->and($event->sucursal_id)->toBe($actor->current_sucursal_id)
            ->and($event->toJson())->not->toContain('Nombre reservado', 'Modificado', 'read clientes');
    }
});

test('fallo de auditoría revierte dominio de equipos roles y seguridad', function (string $operation) {
    $actor = actingAsSuperAdmin();
    $team = Team::factory()->create();
    $this->actingAs($actor)->withSession(['auth.password_confirmed_at' => time()]);
    $before = Activity::count();
    $this->mock(ActivityLogService::class)->shouldReceive('log')->andThrow(new RuntimeException('Auditoría no disponible'));
    $this->withoutExceptionHandling();
    try {
        match ($operation) {
            'team' => $this->put(route('admin.teams.update', $team), ['name' => 'Debe revertirse']),
            'role' => $this->post(route('admin.roles.store'), ['name' => 'Debe revertirse']),
            'token' => $this->post('/user/api-tokens', ['name' => 'Debe revertirse', 'permissions' => ['read']]),
            '2fa' => $this->post('/user/two-factor-authentication'),
        };
        $this->fail('La operación debía fallar.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Auditoría no disponible');
    }
    expect($team->fresh()->name)->not->toBe('Debe revertirse')
        ->and(Role::where('name', 'Debe revertirse')->exists())->toBeFalse()
        ->and($actor->tokens()->count())->toBe(0)->and($actor->fresh()->two_factor_secret)->toBeNull()
        ->and(Activity::count())->toBe($before);
})->with(['team', 'role', 'token', '2fa']);

test('fallos de credenciales y limitación son anónimos aun si el correo existe', function () {
    $user = User::factory()->withPersonalTeam()->create();
    for ($i = 0; $i < 6; $i++) {
        $this->post('/login', ['email' => $user->email, 'password' => 'SECRETO-INCORRECTO']);
    }
    expect(Activity::where('event', ActivityEvent::LoginFailed->value)->count())->toBe(5)
        ->and(Activity::where('event', ActivityEvent::LoginThrottled->value)->count())->toBe(1);
    foreach (Activity::all() as $event) {
        expect($event->causer_id)->toBeNull()->and($event->subject_id)->toBeNull()
            ->and($event->team_id)->toBeNull()->and($event->sucursal_id)->toBeNull()
            ->and($event->toJson())->not->toContain($user->email, 'SECRETO-INCORRECTO');
    }
    $this->assertGuest();
});

test('login y logout se acreditan una vez y rechazo de inactiva no emite éxito', function () {
    $user = User::factory()->create();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    $this->post('/logout')->assertRedirect();
    expect(Activity::where('event', ActivityEvent::Login->value)->count())->toBe(1)
        ->and(Activity::where('event', ActivityEvent::Logout->value)->sole()->causer_id)->toBe($user->id);
    $user->update(['status' => UserStatus::Inactive]);
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    expect(Activity::where('event', ActivityEvent::LoginRejected->value)->sole()->causer_id)->toBeNull()
        ->and(Activity::where('event', ActivityEvent::Login->value)->count())->toBe(1)
        ->and(Activity::where('event', ActivityEvent::Logout->value)->count())->toBe(1);
    $this->assertGuest();
});

test('ciclo 2FA audita cambios efectivos y mantiene secretos fuera del log', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor)->withSession(['auth.password_confirmed_at' => time()]);
    $this->post('/user/two-factor-authentication')->assertSessionHasNoErrors();
    $this->post('/user/two-factor-authentication')->assertSessionHasNoErrors();
    $secret = $actor->fresh()->two_factor_secret;
    $codes = $actor->fresh()->two_factor_recovery_codes;
    $this->mock(TwoFactorAuthenticationProvider::class)->shouldReceive('verify')->andReturn(true);
    $this->post('/user/confirmed-two-factor-authentication', ['code' => '123456'])->assertSessionHasNoErrors();
    $this->post('/user/confirmed-two-factor-authentication', ['code' => '123456'])->assertSessionHasNoErrors();
    $this->post('/user/two-factor-recovery-codes')->assertSessionHasNoErrors();
    $this->delete('/user/two-factor-authentication')->assertSessionHasNoErrors();
    $this->delete('/user/two-factor-authentication')->assertSessionHasNoErrors();
    foreach ([ActivityEvent::TwoFactorEnabled, ActivityEvent::TwoFactorConfirmed, ActivityEvent::RecoveryCodesRegenerated, ActivityEvent::TwoFactorDisabled] as $code) {
        $event = Activity::where('event', $code->value)->sole();
        expect($event->causer_id)->toBe($actor->id)->and($event->team_id)->toBe($actor->current_team_id)
            ->and($event->toJson())->not->toContain($secret, $codes, '123456', 'two_factor_secret');
    }
});

test('segundo factor no acredita desafío ni cuentas inactivas y conserva fallos anónimos', function (bool $inactive) {
    $user = User::factory()->create(['two_factor_secret' => encrypt('SECRET'), 'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['RECOVERY-RESERVADO']))]);
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/two-factor-challenge');
    expect(Activity::count())->toBe(0);
    $this->mock(TwoFactorAuthenticationProvider::class)->shouldReceive('verify')->andReturn(false);
    $this->post('/two-factor-challenge', ['code' => '999999'])->assertSessionHasErrors([
        'code' => 'El código de autenticación de dos factores no es válido.',
    ]);
    expect(Activity::where('event', ActivityEvent::TwoFactorFailed->value)->sole()->causer_id)->toBeNull();
    if ($inactive) {
        $user->update(['status' => UserStatus::Inactive]);
    }
    $this->post('/two-factor-challenge', ['recovery_code' => 'RECOVERY-RESERVADO']);
    if ($inactive) {
        $this->assertGuest();
        expect(Activity::where('event', ActivityEvent::LoginRejected->value)->sole()->team_id)->toBeNull()
            ->and(Activity::where('event', ActivityEvent::TwoFactorCompleted->value)->count())->toBe(0);
    } else {
        $this->assertAuthenticatedAs($user);
        expect(Activity::where('event', ActivityEvent::TwoFactorCompleted->value)->sole()->causer_id)->toBe($user->id);
    }
    expect(Activity::all()->toJson())->not->toContain('RECOVERY-RESERVADO', 'SECRET', $user->email);
})->with([false, true]);

test('tokens API conservan propietario y registran solo cambios efectivos sin nombres ni capacidades', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor)->post('/user/api-tokens', ['name' => 'Nombre secreto', 'permissions' => ['read']])->assertSessionHasNoErrors();
    $token = $actor->tokens()->sole();
    $this->put('/user/api-tokens/'.$token->id, ['permissions' => ['read']])->assertSessionHasNoErrors();
    expect(Activity::where('event', ActivityEvent::ApiTokenUpdated->value)->count())->toBe(0);
    $this->put('/user/api-tokens/'.$token->id, ['permissions' => ['create', 'read']])->assertSessionHasNoErrors();
    $foreign = User::factory()->create()->createToken('Ajeno');
    $this->put('/user/api-tokens/'.$foreign->accessToken->id, ['permissions' => []])->assertNotFound();
    $this->delete('/user/api-tokens/'.$foreign->accessToken->id)->assertNotFound();
    $this->delete('/user/api-tokens/'.$token->id)->assertRedirect();
    foreach ([ActivityEvent::ApiTokenCreated, ActivityEvent::ApiTokenUpdated, ActivityEvent::ApiTokenRevoked] as $code) {
        $event = Activity::where('event', $code->value)->sole();
        expect($event->causer_id)->toBe($actor->id)->and($event->subject_id)->toBe($actor->id)
            ->and($event->properties['related']['id'])->toBe($token->id)
            ->and($event->toJson())->not->toContain('Nombre secreto', $token->token, 'abilities');
    }
});

test('CSV audita archivo generado sin incluir su propio evento ni filtros sensibles', function () {
    $actor = actingAsSuperAdmin();
    app(ActivityLogService::class)->log(ActivityEvent::Login, 'REGISTRO-RESERVADO', causer: $actor);
    $filters = ['team_id' => $actor->current_team_id, 'search' => 'REGISTRO-RESERVADO'];
    $csv = $this->actingAs($actor)->get(route('admin.users.activity.export', $filters))->assertOk()->streamedContent();
    expect($csv)->toContain('REGISTRO-RESERVADO')->not->toContain(ActivityEvent::ActivityExported->value);
    $event = Activity::where('event', ActivityEvent::ActivityExported->value)->sole();
    expect($event->causer_id)->toBe($actor->id)->and($event->team_id)->toBe($actor->current_team_id)
        ->and($event->properties['result'])->toBe('generated')->and($event->toJson())->not->toContain('REGISTRO-RESERVADO', 'search', 'filters');
    $this->get(route('admin.users.activity', $filters))->assertOk();
    expect(Activity::where('event', ActivityEvent::ActivityExported->value)->count())->toBe(1);
});

test('comando de permiso audita sistema y no repite creación rechazada', function () {
    $this->artisan('permission:create-permission', ['name' => 'acción reservada', 'guard' => 'web'])->assertSuccessful();
    $this->artisan('permission:create-permission', ['name' => 'acción reservada', 'guard' => 'web'])->assertFailed();
    $event = Activity::where('event', ActivityEvent::PermissionCreated->value)->sole();
    expect($event->causer_id)->toBeNull()->and($event->team_id)->toBeNull()
        ->and($event->subject_id)->toBe(Permission::where('name', 'acción reservada')->sole()->id)
        ->and($event->toJson())->not->toContain('acción reservada');
});

test('comando de roles conserva equipo explícito contexto de sistema y cambios efectivos', function () {
    $team = Team::factory()->create();
    $other = Team::factory()->create();
    $previous = getPermissionsTeamId();
    $input = ['name' => 'Rol reservado', 'guard' => 'web', 'permissions' => 'acción reservada', '--team-id' => $team->id];
    $this->artisan('permission:create-role', $input)->assertSuccessful();
    $this->artisan('permission:create-role', $input)->assertSuccessful();
    $this->artisan('permission:create-role', [...$input, '--team-id' => $other->id])->assertSuccessful();
    expect(Activity::where('event', ActivityEvent::RoleCreated->value)->count())->toBe(2)
        ->and(Activity::where('event', ActivityEvent::RolePermissionsUpdated->value)->count())->toBe(0)
        ->and(Activity::where('event', ActivityEvent::PermissionCreated->value)->count())->toBe(1)
        ->and(getPermissionsTeamId())->toBe($previous);
    $this->artisan('permission:create-role', [...$input, 'permissions' => 'otra acción'])->assertSuccessful();
    $event = Activity::where('event', ActivityEvent::RolePermissionsUpdated->value)->sole();
    expect($event->team_id)->toBe($team->id)->and($event->causer_id)->toBeNull()
        ->and($event->sucursal_id)->toBeNull()->and($event->toJson())->not->toContain('Rol reservado', 'otra acción');
});

test('limitación de segundo factor no identifica al titular del desafío', function () {
    $user = User::factory()->create(['two_factor_secret' => encrypt('SECRET'), 'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['RECOVERY']))]);
    $this->withSession(['login.id' => $user->id]);
    $this->mock(TwoFactorAuthenticationProvider::class)->shouldReceive('verify')->andReturn(false);
    for ($i = 0; $i < 6; $i++) {
        $this->post('/two-factor-challenge', ['code' => '000000']);
    }
    $event = Activity::where('event', ActivityEvent::TwoFactorThrottled->value)->sole();
    expect($event->causer_id)->toBeNull()->and($event->subject_id)->toBeNull()
        ->and($event->team_id)->toBeNull()->and($event->sucursal_id)->toBeNull()
        ->and(Activity::where('event', ActivityEvent::TwoFactorFailed->value)->count())->toBe(5);
});

test('perfil y verificación registran cambios efectivos y nunca el nombre ni enlace firmado', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor)->put('/user/profile-information', ['name' => $actor->name, 'email' => $actor->email])->assertSessionHasNoErrors();
    expect(Activity::where('event', ActivityEvent::UserProfileUpdated->value)->count())->toBe(0);
    $this->put('/user/profile-information', ['name' => 'NOMBRE-RESERVADO', 'email' => $actor->email])->assertSessionHasNoErrors();
    $actor->refresh()->forceFill(['email_verified_at' => null])->save();
    $this->actingAs($actor);
    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), ['id' => $actor->id, 'hash' => sha1($actor->email)]);
    $this->get($url)->assertRedirect();
    $this->get($url)->assertRedirect();
    assertDomainAudit(['user.profile.updated', 'user.email_verified'], $actor, $actor->current_sucursal_id, ['NOMBRE-RESERVADO', $actor->email, 'signature=']);
    expect(Activity::where('event', ActivityEvent::EmailVerified->value)->count())->toBe(1);
});

test('registro y eliminación física de cuentas continúan deshabilitados', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', ['email' => 'invalido@example.test'])->assertNotFound();
    $user = User::factory()->create();
    $this->actingAs($user)->delete('/user')->assertNotFound();
    expect($user->fresh())->not->toBeNull();
});

test('fallos anónimos solo aparecen en consulta global autorizada', function () {
    app(\App\Services\AccessAuditService::class)->anonymous(ActivityEvent::LoginFailed);
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor)->get(route('admin.users.activity'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('activityLogs.total', 1)->where('activityLogs.data.0.causer.name', 'Anónimo'));
    $this->get(route('admin.users.activity', ['team_id' => $actor->current_team_id]))->assertOk()
        ->assertInertia(fn ($page) => $page->where('activityLogs.total', 0));
    $actor->forceFill(['is_super_admin' => false])->save();
    grantTeamPermissions($actor, $actor->currentTeam, ['read activity-log']);
    $this->get(route('admin.users.activity'))->assertOk()->assertInertia(fn ($page) => $page->where('activityLogs.total', 0));
});

test('errores de segundo factor y recuperación son claros en español sin identidad atribuida', function () {
    $user = User::factory()->create(['two_factor_secret' => encrypt('SECRET'), 'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['RECOVERY']))]);
    $this->withSession(['login.id' => $user->id]);
    $this->postJson('/two-factor-challenge', ['code' => ['inválido']])->assertUnprocessable()
        ->assertJsonPath('errors.code.0', 'El campo código de autenticación debe ser texto.');
    $this->post('/two-factor-challenge', ['recovery_code' => 'incorrecto'])->assertSessionHasErrors([
        'recovery_code' => 'El código de recuperación no es válido.',
    ]);
    $event = Activity::where('event', ActivityEvent::TwoFactorFailed->value)->sole();
    expect($event->causer_id)->toBeNull()->and($event->subject_id)->toBeNull();
});

test('desafío 2FA revalida códigos revocados después de cargar al usuario', function () {
    $user = User::factory()->create(['two_factor_secret' => encrypt('SECRET'), 'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['ANTERIOR']))]);
    $this->withSession(['login.id' => $user->id]);
    app()->afterResolving(\Laravel\Fortify\Http\Requests\TwoFactorLoginRequest::class, function ($request) use ($user) {
        $request->challengedUser();
        $user->newQuery()->whereKey($user->id)->update(['two_factor_recovery_codes' => encrypt(json_encode(['NUEVO']))]);
    });
    $this->post('/two-factor-challenge', ['recovery_code' => 'ANTERIOR'])->assertSessionHasErrors('recovery_code');
    $this->assertGuest();
    expect($user->fresh()->recoveryCodes())->toBe(['NUEVO'])
        ->and(Activity::where('event', ActivityEvent::TwoFactorCompleted->value)->count())->toBe(0)
        ->and(Activity::where('event', ActivityEvent::TwoFactorFailed->value)->count())->toBe(1);
});

test('habilitar 2FA usa estado bloqueado sin sustituir una configuración habilitada concurrentemente', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $secret = encrypt('SECRET-CONCURRENTE');
    $user->newQuery()->whereKey($user->id)->update(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => encrypt(json_encode(['NUEVO']))]);
    $this->post('/user/two-factor-authentication')->assertSessionHasNoErrors();
    expect($user->fresh()->two_factor_secret)->toBe($secret)->and(Activity::count())->toBe(0);
});

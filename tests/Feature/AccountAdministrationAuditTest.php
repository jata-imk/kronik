<?php

use App\Enums\UserStatus;
use App\Models\Activity;
use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use App\Services\UserAdministrationData;
use App\Services\UserInvitationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;

function auditRows(string $event)
{
    return Activity::where('event', $event)->get();
}

test('pending account and invitation audit preserve actor subject and distinct team scopes without secrets', function () {
    Notification::fake();
    $actor = actingAsSuperAdmin();
    $selected = Team::factory()->create();
    $this->actingAs($actor)->post(route('admin.users.store'), [
        'name' => 'PERSONAL-NAME-NEVER-LOG', 'email' => 'private-never-log@example.test',
        'current_team_id' => $selected->id, 'team_roles' => [['team_id' => $selected->id, 'role_ids' => []]],
        'sucursal_ids' => [$actor->current_sucursal_id], 'sucursal_principal_id' => $actor->current_sucursal_id,
    ])->assertSessionHasNoErrors();
    $user = User::where('email', 'private-never-log@example.test')->firstOrFail();
    foreach (['user.created', 'user.invitation.sent'] as $event) {
        $row = auditRows($event)->sole();
        expect($row->causer_id)->toBe($actor->id)->and($row->subject_id)->toBe($user->id)
            ->and($row->team_id)->toBe($actor->current_team_id)->and($row->sucursal_id)->toBe($actor->current_sucursal_id);
    }
    expect(auditRows('team.member.added')->sole()->team_id)->toBe($selected->id)
        ->and(auditRows('user.invitation.sent')->sole()->properties['result'])->toBe('transport_accepted');
    $raw = DB::table('activity_log')->get()->toJson();
    expect($raw)->not->toContain($user->name, $user->email, $user->password, '/invitaciones/', 'password_confirmation');
});

test('invitation audit distinguishes resend throttle and transport failure without duplicate acceptance events', function () {
    Notification::fake();
    $actor = actingAsSuperAdmin();
    $user = User::factory()->create(['status' => UserStatus::Pending]);
    $this->actingAs($actor)->post(route('admin.users.invitation.resend', $user))->assertSessionHasNoErrors();
    $this->post(route('admin.users.invitation.resend', $user))->assertSessionHasErrors('email');
    $this->travel(61)->seconds();
    $this->post(route('admin.users.invitation.resend', $user))->assertSessionHasNoErrors();
    expect(auditRows('user.invitation.sent'))->toHaveCount(1)
        ->and(auditRows('user.invitation.resent'))->toHaveCount(1)
        ->and(auditRows('user.invitation.throttled')->sole()->properties['result'])->toBe('throttled');
});

test('failed invitation audit contains no provider error or token and a retry records one accepted send', function () {
    $actor = actingAsSuperAdmin();
    $user = User::factory()->create(['status' => UserStatus::Pending, 'invited_at' => null]);
    Notification::shouldReceive('send')->once()->andThrow(new Symfony\Component\Mailer\Exception\TransportException('SECRET-SMTP-NEVER-LOG'));
    $this->actingAs($actor)->post(route('admin.users.invitation.resend', $user))->assertSessionHasErrors('email');
    $failed = auditRows('user.invitation.failed')->sole();
    expect($failed->causer_id)->toBe($actor->id)->and($failed->subject_id)->toBe($user->id)
        ->and($failed->properties['result'])->toBe('transport_failed')
        ->and(DB::table('activity_log')->get()->toJson())->not->toContain('SECRET-SMTP-NEVER-LOG', $user->email);
    Notification::fake();
    $this->post(route('admin.users.invitation.resend', $user))->assertSessionHasNoErrors();
    expect(auditRows('user.invitation.failed'))->toHaveCount(1)->and(auditRows('user.invitation.sent'))->toHaveCount(1);
});

test('activation recovery and authenticated password change emit distinct events with no credential values', function () {
    $user = User::factory()->withPersonalTeam()->create(['status' => UserStatus::Pending, 'email_verified_at' => null]);
    $team = $user->currentTeam;
    $token = Password::broker('invitaciones')->createToken($user);
    $input = ['email' => $user->email, 'token' => $token, 'password' => 'INITIAL-PRIVATE-PASSWORD', 'password_confirmation' => 'INITIAL-PRIVATE-PASSWORD'];
    $this->post(route('invitations.accept'), $input)->assertSessionHasNoErrors();
    $this->post(route('invitations.accept'), $input)->assertSessionHasErrors('email');
    $row = auditRows('user.activated')->sole();
    expect($row->causer_id)->toBe($user->id)->and($row->subject_id)->toBe($user->id)->and($row->team_id)->toBe($team->id);
    $reset = Password::broker('users')->createToken($user->fresh());
    $this->post('/reset-password', [...$input, 'token' => $reset, 'password' => 'RECOVERED-PRIVATE-PASSWORD', 'password_confirmation' => 'RECOVERED-PRIVATE-PASSWORD'])->assertSessionHasNoErrors();
    $this->actingAs($user->fresh())->put('/user/password', [
        'current_password' => 'RECOVERED-PRIVATE-PASSWORD', 'password' => 'CHANGED-PRIVATE-PASSWORD', 'password_confirmation' => 'CHANGED-PRIVATE-PASSWORD',
    ])->assertSessionHasNoErrors();
    expect(auditRows('user.activated'))->toHaveCount(1)->and(auditRows('user.password.recovered'))->toHaveCount(1)
        ->and(auditRows('user.password.changed'))->toHaveCount(1);
    expect(DB::table('activity_log')->get()->toJson())->not->toContain($token, $reset, 'INITIAL-PRIVATE-PASSWORD', 'RECOVERED-PRIVATE-PASSWORD', 'CHANGED-PRIVATE-PASSWORD', $user->fresh()->password);
});

test('membership and Spatie roles audit actual changes once and generic Jetstream routes are also covered', function () {
    $actor = actingAsSuperAdmin();
    $team = $actor->currentTeam;
    $user = User::factory()->withPersonalTeam()->create(['sucursal_principal_id' => $actor->current_sucursal_id]);
    $user->currentTeam;
    $user->teams()->attach($team);
    $user->sucursales()->attach($actor->current_sucursal_id);
    $role = Role::query()->create(['name' => 'Audit role', 'guard_name' => 'web', 'team_id' => $team->id]);
    $data = app(UserAdministrationData::class)->user($user->fresh());
    $data['team_roles'] = array_map(fn ($assignment) => [...$assignment, 'role_ids' => $assignment['team_id'] === $team->id ? [$role->id] : $assignment['role_ids']], $data['team_roles']);
    $this->actingAs($actor)->put(route('admin.users.update', $user), $data)->assertSessionHasNoErrors();
    $this->put(route('admin.users.update', $user), $data)->assertSessionHasNoErrors();
    expect(auditRows('user.team_roles.updated'))->toHaveCount(1)
        ->and(auditRows('user.team_roles.updated')->sole()->team_id)->toBe($team->id);
    $this->delete(route('team-members.destroy', [$team, $user]))->assertSessionHasNoErrors();
    expect(auditRows('team.member.removed')->sole()->subject_id)->toBe($user->id)->and(auditRows('user.deactivated'))->toHaveCount(0);
    $this->post(route('team-members.store', $team), ['email' => $user->email, 'role' => 'editor'])->assertSessionHasNoErrors();
    $this->post(route('team-members.store', $team), ['email' => $user->email, 'role' => 'editor'])->assertSessionHasErrors();
    $this->put(route('team-members.update', [$team, $user]), ['role' => 'admin'])->assertSessionHasNoErrors();
    $this->put(route('team-members.update', [$team, $user]), ['role' => 'admin'])->assertSessionHasNoErrors();
    expect(auditRows('team.member.added'))->toHaveCount(1)->and(auditRows('team.member.legacy_role_updated'))->toHaveCount(1);
});

test('account state audit covers update and delete routes and does not duplicate no-op retries', function () {
    $actor = actingAsSuperAdmin();
    $user = User::factory()->create(['current_team_id' => $actor->current_team_id, 'sucursal_principal_id' => $actor->current_sucursal_id]);
    $user->teams()->attach($actor->current_team_id);
    $user->sucursales()->attach($actor->current_sucursal_id);
    $user->createToken('revoke-me');
    $data = app(UserAdministrationData::class)->user($user->fresh());
    $this->actingAs($actor)->put(route('admin.users.update', $user), [...$data, 'status' => 'inactive'])->assertSessionHasNoErrors();
    $this->put(route('admin.users.update', $user), [...$data, 'status' => 'inactive'])->assertSessionHasNoErrors();
    expect(auditRows('user.deactivated'))->toHaveCount(1)->and($user->tokens()->count())->toBe(0);
    $this->put(route('admin.users.update', $user), [...$data, 'status' => 'active'])->assertSessionHasNoErrors();
    expect(auditRows('user.reactivated'))->toHaveCount(1);
    $this->delete(route('admin.users.destroy', $user))->assertSessionHasNoErrors();
    $this->delete(route('admin.users.destroy', $user))->assertSessionHasNoErrors();
    expect(auditRows('user.deactivated'))->toHaveCount(2);
});

test('secondary branch changes are audited once without recording assignment values', function () {
    $actor = actingAsSuperAdmin();
    $user = User::factory()->create(['current_team_id' => $actor->current_team_id, 'sucursal_principal_id' => $actor->current_sucursal_id, 'current_sucursal_id' => $actor->current_sucursal_id]);
    $user->teams()->attach($actor->current_team_id);
    $user->sucursales()->attach($actor->current_sucursal_id);
    $secondary = Sucursal::factory()->create();
    $data = app(UserAdministrationData::class)->user($user->fresh());
    $withSecondary = [...$data, 'sucursal_ids' => [...$data['sucursal_ids'], $secondary->id]];
    $this->actingAs($actor)->put(route('admin.users.update', $user), $withSecondary)->assertSessionHasNoErrors();
    $this->put(route('admin.users.update', $user), $withSecondary)->assertSessionHasNoErrors();
    $row = auditRows('user.updated')->sole();
    expect($row->properties['changed_fields'])->toBe(['sucursal_ids'])
        ->and($row->causer_id)->toBe($actor->id)->and($row->subject_id)->toBe($user->id)
        ->and($row->team_id)->toBe($actor->current_team_id)->and($row->sucursal_id)->toBe($actor->current_sucursal_id)
        ->and($row->properties->except(['ip', 'user_agent', 'request_id'])->keys()->all())->toBe(['changed_fields', 'state', 'result']);
    $this->put(route('admin.users.update', $user), $data)->assertSessionHasNoErrors();
    $this->put(route('admin.users.update', $user), $data)->assertSessionHasNoErrors();
    expect(auditRows('user.updated'))->toHaveCount(2)
        ->and($user->sucursales()->whereKey($secondary->id)->exists())->toBeFalse();
});

test('audit preserves original context including an unknown branch if preferences change during transport', function () {
    $actor = actingAsSuperAdmin();
    $oldTeam = $actor->current_team_id;
    $actor->forceFill(['current_sucursal_id' => null])->save();
    $other = Team::factory()->create();
    $branch = Sucursal::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Pending]);
    Notification::shouldReceive('send')->once()->andReturnUsing(function () use ($actor, $other, $branch) {
        $actor->forceFill(['current_team_id' => $other->id, 'current_sucursal_id' => $branch->id])->save();
    });
    app(UserInvitationService::class)->send($user, $actor);
    $row = auditRows('user.invitation.sent')->sole();
    expect($row->team_id)->toBe($oldTeam)->and($row->sucursal_id)->toBeNull();
});

test('invalid account assignments roll back domain and audit writes together', function () {
    Notification::fake();
    $actor = actingAsSuperAdmin();
    $inactive = Team::factory()->create(['activo' => false]);
    $this->actingAs($actor)->post(route('admin.users.store'), [
        'name' => 'Rollback', 'email' => 'rollback@example.test', 'current_team_id' => $inactive->id,
        'team_roles' => [['team_id' => $inactive->id, 'role_ids' => []]],
        'sucursal_ids' => [$actor->current_sucursal_id], 'sucursal_principal_id' => $actor->current_sucursal_id,
    ])->assertSessionHasErrors('team_roles');
    expect(User::where('email', 'rollback@example.test')->exists())->toBeFalse()->and(auditRows('user.created'))->toHaveCount(0);
});

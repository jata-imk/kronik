<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\UserStatus;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamManagementService
{
    public function create(User $actor, array $data, bool $switch = false): Team
    {
        $branchId = $actor->current_sucursal_id;

        return DB::transaction(function () use ($actor, $data, $switch, $branchId) {
            $team = $actor->ownedTeams()->create(['name' => $data['name'], 'personal_team' => false, 'activo' => true]);
            $previous = getPermissionsTeamId();
            try {
                setPermissionsTeamId($team->id);
                $model = config('permission.models.role');
                foreach ($model::whereNull('team_id')->where('name', '!=', 'Super Admin')->with('permissions')->get() as $template) {
                    app(RoleManagementService::class)->create($actor, $team, [
                        'name' => $template->name, 'guard_name' => $template->guard_name, 'permissions' => $template->permissions,
                    ], copied: true);
                }
                app(ActivityLogService::class)->log(ActivityEvent::TeamCreated, ActivityEvent::TeamCreated->label(), $team,
                    ['result' => 'created'], $actor, $team->id, $branchId, inheritActorContext: false);
                if ($switch) {
                    $actor->switchTeam($team);
                }
            } finally {
                setPermissionsTeamId($previous);
                $actor->unsetRelation('roles')->unsetRelation('permissions');
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            }

            return $team;
        });
    }

    public function update(Team $team, array $data, ?User $actor = null): void
    {
        $actor ??= auth()->user();
        $branchId = $actor?->current_sucursal_id;
        DB::transaction(function () use ($team, $data, $actor, $branchId) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            if (array_key_exists('activo', $data) && ! $data['activo'] && $team->currentUsers()->exists()) {
                throw ValidationException::withMessages(['team' => 'Cambia el equipo actual de todos sus usuarios antes de desactivarlo.']);
            }
            $team->fill($data);
            $fields = array_keys($team->getDirty());
            if ($fields === []) {
                return;
            }
            $event = in_array('activo', $fields, true)
                ? ($team->activo ? ActivityEvent::TeamReactivated : ActivityEvent::TeamDeactivated)
                : ActivityEvent::TeamUpdated;
            $team->save();
            app(ActivityLogService::class)->log($event, $event->label(), $team,
                ['changed_fields' => $fields, 'result' => 'applied'], $actor, $team->id, $branchId, inheritActorContext: false);
        });
    }

    public function transfer(User $actor, Team $team, int $responsibleId): void
    {
        app(TeamContext::class)->authorize($actor, $team, 'update teams');
        DB::transaction(function () use ($actor, $team, $responsibleId) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            $new = User::whereKey($responsibleId)->lockForUpdate()->firstOrFail();
            if ($new->id === $team->user_id || $new->status !== UserStatus::Active || ! $new->belongsToTeam($team)) {
                throw ValidationException::withMessages(['responsible_id' => 'Selecciona otro integrante activo de este equipo como responsable.']);
            }
            $oldId = $team->user_id;
            $team->users()->syncWithoutDetaching([$oldId]);
            $team->users()->detach($new->id);
            $team->forceFill(['user_id' => $new->id])->save();
            app(ActivityLogService::class)->log(
                ActivityEvent::TeamResponsibleTransferred,
                "Responsable transferido del usuario {$oldId} al usuario {$new->id}.",
                $team, ['changed_fields' => ['user_id'], 'related' => ['type' => 'users', 'id' => $new->id]],
                $actor, $team->id, $actor->current_sucursal_id, inheritActorContext: false,
            );
        });
    }
}

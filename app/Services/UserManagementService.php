<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class UserManagementService
{
    public function create(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $user = User::forceCreate([
                'name' => $data['name'],
                'email' => Str::lower($data['email']),
                'password' => Hash::make(Str::random(64)),
                'current_team_id' => $data['current_team_id'],
                'sucursal_principal_id' => $data['sucursal_principal_id'],
                'current_sucursal_id' => $data['sucursal_principal_id'],
                'status' => UserStatus::Pending,
                'invited_at' => now(),
            ]);

            $this->syncAssignments($user, $data, $actor);

            return $user->refresh();
        });
    }

    public function update(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            if ($user->is_super_admin && ! $actor->is_super_admin) {
                throw ValidationException::withMessages([
                    'user' => 'Solo un Super Admin global puede modificar otra cuenta global.',
                ]);
            }

            if ($actor->is($user) && array_key_exists('is_super_admin', $data) && ! $data['is_super_admin']) {
                throw ValidationException::withMessages([
                    'is_super_admin' => 'No puedes retirar tu propio acceso de Super Admin.',
                ]);
            }

            if ($actor->is($user) && $data['status'] !== UserStatus::Active->value) {
                throw ValidationException::withMessages([
                    'status' => 'No puedes desactivar tu propia cuenta.',
                ]);
            }

            $user->forceFill([
                'name' => $data['name'],
                'email' => Str::lower($data['email']),
                'current_team_id' => $data['current_team_id'],
                'sucursal_principal_id' => $data['sucursal_principal_id'],
                'status' => $data['status'],
            ]);

            if (! in_array($user->current_sucursal_id, $data['sucursal_ids'], true)) {
                $user->current_sucursal_id = $data['sucursal_principal_id'];
            }

            $user->save();
            $this->syncAssignments($user, $data, $actor);

            return $user->refresh();
        });
    }

    private function syncAssignments(User $user, array $data, User $actor): void
    {
        $teamRoles = collect($data['team_roles'])->keyBy('team_id');
        $teamIds = $teamRoles->keys()->map(fn ($id) => (int) $id)->all();
        $sucursalIds = array_map('intval', $data['sucursal_ids']);

        if (! in_array((int) $data['current_team_id'], $teamIds, true)) {
            throw ValidationException::withMessages([
                'current_team_id' => 'El equipo actual debe estar incluido entre los equipos asignados.',
            ]);
        }

        if (! in_array((int) $data['sucursal_principal_id'], $sucursalIds, true)) {
            throw ValidationException::withMessages([
                'sucursal_principal_id' => 'La sucursal principal debe estar incluida entre las sucursales asignadas.',
            ]);
        }

        $invalidTeams = Team::query()->whereIn('id', $teamIds)->where('activo', false)->exists();
        $invalidSucursales = Sucursal::query()->whereIn('id', $sucursalIds)->where('activa', false)->exists();
        if ($invalidTeams) {
            throw ValidationException::withMessages(['team_roles' => 'No se pueden asignar equipos inactivos.']);
        }
        if ($invalidSucursales) {
            throw ValidationException::withMessages(['sucursal_ids' => 'No se pueden asignar sucursales inactivas.']);
        }

        $ownedTeamIds = $user->ownedTeams()->pluck('id')->map(fn ($id) => (int) $id);
        if ($ownedTeamIds->diff($teamIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'team_roles' => 'Primero transfiere la propiedad de los equipos que pertenecen al usuario.',
            ]);
        }

        $membershipIds = array_values(array_diff($teamIds, $ownedTeamIds->all()));
        $user->teams()->sync($membershipIds);
        $user->sucursales()->sync($sucursalIds);

        $previousTeamId = getPermissionsTeamId();
        try {
            foreach ($teamRoles as $teamId => $assignment) {
                setPermissionsTeamId((int) $teamId);
                $roleIds = array_map('intval', Arr::wrap($assignment['role_ids'] ?? []));
                $validRoleIds = DB::table('roles')
                    ->where('team_id', $teamId)
                    ->where('name', '!=', 'Super Admin')
                    ->whereIn('id', $roleIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (count($validRoleIds) !== count(array_unique($roleIds))) {
                    throw ValidationException::withMessages([
                        'team_roles' => 'Uno de los roles no pertenece al equipo seleccionado.',
                    ]);
                }

                $user->syncRoles($validRoleIds);
            }

            DB::table('model_has_roles')
                ->where('model_type', $user->getMorphClass())
                ->where('model_id', $user->id)
                ->whereNotIn('team_id', $teamIds)
                ->delete();
        } finally {
            setPermissionsTeamId($previousTeamId);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $user->unsetRelation('roles');
        }

        if ($actor->is_super_admin && array_key_exists('is_super_admin', $data)) {
            $user->forceFill(['is_super_admin' => (bool) $data['is_super_admin']])->save();
        }
    }
}

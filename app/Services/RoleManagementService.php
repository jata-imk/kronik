<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\Permission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleManagementService
{
    public function create(User $actor, Team $team, array $data, bool $copied = false): Role
    {
        return DB::transaction(function () use ($actor, $team, $data, $copied) {
            $role = Role::query()->create(['name' => $data['name'], 'guard_name' => $data['guard_name'] ?? 'web', 'team_id' => $team->id]);
            $role->syncPermissions($data['permissions'] ?? []);
            $this->record(ActivityEvent::RoleCreated, $role, $actor, ['name', 'permissions'], $copied ? 'copied' : 'created');

            return $role;
        });
    }

    public function update(User $actor, Role $role, array $data): void
    {
        DB::transaction(function () use ($actor, $role, $data) {
            $role = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();
            if ($role->name !== $data['name']) {
                $role->update(['name' => $data['name']]);
                $this->record(ActivityEvent::RoleUpdated, $role, $actor, ['name']);
            }
            if (array_key_exists('permissions', $data)) {
                $before = $role->permissions()->pluck('permissions.id')->sort()->values()->all();
                $after = Permission::whereIn('id', $data['permissions'])->pluck('id')->sort()->values()->all();
                if ($before !== $after) {
                    $role->syncPermissions($after);
                    $this->record(ActivityEvent::RolePermissionsUpdated, $role, $actor, ['permissions']);
                }
            }
        });
    }

    public function delete(User $actor, Role $role): void
    {
        DB::transaction(function () use ($actor, $role) {
            $role = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();
            if ($role->permissions()->exists() || DB::table('model_has_roles')->where('role_id', $role->id)->exists()) {
                throw ValidationException::withMessages(['message' => 'El rol tiene permisos o usuarios asociados, no se puede eliminar']);
            }
            $this->record(ActivityEvent::RoleDeleted, $role, $actor, [], 'deleted');
            $role->delete();
        });
    }

    private function record(ActivityEvent $event, Role $role, User $actor, array $fields, string $result = 'applied'): void
    {
        app(ActivityLogService::class)->log($event, $event->label(), $role,
            ['changed_fields' => $fields, 'result' => $result], $actor, $role->team_id, $actor->current_sucursal_id, inheritActorContext: false);
    }
}

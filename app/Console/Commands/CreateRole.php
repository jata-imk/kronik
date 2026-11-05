<?php

namespace App\Console\Commands;

use App\Enums\ActivityEvent;
use App\Models\Permission;
use App\Services\ActivityLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateRole extends Command
{
    protected $signature = 'permission:create-role {name} {guard?} {permissions?} {--team-id=}';

    protected $description = 'Crea un rol o agrega permisos con auditoría';

    public function handle(): int
    {
        if ($this->option('team-id') !== null && (! ctype_digit((string) $this->option('team-id')) || (int) $this->option('team-id') < 1)) {
            $this->error('El equipo debe ser un identificador entero positivo.');

            return self::FAILURE;
        }
        $teamId = $this->option('team-id') ? (int) $this->option('team-id') : null;
        if ($teamId && ! \App\Models\Team::whereKey($teamId)->exists()) {
            $this->error('No se encontró el equipo seleccionado.');

            return self::FAILURE;
        }
        $previous = getPermissionsTeamId();
        try {
            setPermissionsTeamId($teamId);
            DB::transaction(function () use ($teamId) {
                $guard = $this->argument('guard') ?: config('auth.defaults.guard');
                $model = config('permission.models.role');
                $role = $model::query()->where('team_id', $teamId)->where('guard_name', $guard)
                    ->where('name', $this->argument('name'))->lockForUpdate()->first();
                $created = $role === null;
                $role ??= $model::query()->create(['team_id' => $teamId, 'guard_name' => $guard, 'name' => $this->argument('name')]);
                $before = $role->permissions()->pluck('permissions.id')->sort()->values()->all();
                $permissions = [];
                foreach (array_filter(array_map('trim', explode('|', $this->argument('permissions') ?? ''))) as $name) {
                    $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
                    if ($permission->wasRecentlyCreated) {
                        $event = ActivityEvent::PermissionCreated;
                        app(ActivityLogService::class)->log($event, $event->label(), $permission,
                            ['result' => 'created'], inheritActorContext: false);
                    }
                    $permissions[] = $permission;
                }
                $role->givePermissionTo($permissions);
                $changed = $before !== $role->permissions()->pluck('permissions.id')->sort()->values()->all();
                if ($created || $changed) {
                    $event = $created ? ActivityEvent::RoleCreated : ActivityEvent::RolePermissionsUpdated;
                    app(ActivityLogService::class)->log($event, $event->label(), $role,
                        ['changed_fields' => ['permissions'], 'result' => $created ? 'created' : 'applied'], teamId: $teamId, inheritActorContext: false);
                }
            });
        } finally {
            setPermissionsTeamId($previous);
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
        $this->info('Rol y permisos guardados.');

        return self::SUCCESS;
    }
}

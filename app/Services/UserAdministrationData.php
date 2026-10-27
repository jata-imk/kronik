<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserAdministrationData
{
    public function formOptions(User $actor): array
    {
        return [
            'teams' => Team::orderByDesc('activo')->orderBy('name')->get(['id', 'name', 'activo']),
            'roles' => Role::whereNotNull('team_id')->where('guard_name', 'web')->where('name', '!=', 'Super Admin')->orderBy('name')->get(['id', 'name', 'team_id']),
            'sucursales' => Sucursal::orderByDesc('activa')->orderBy('nombre')->get(['id', 'nombre', 'clave', 'activa']),
            'statusOptions' => collect(UserStatus::cases())->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]),
            'canManageSuperAdmin' => (bool) $actor->is_super_admin,
        ];
    }

    public function users(Collection $users): Collection
    {
        $users = new ModelCollection($users->all());
        if ($users->isEmpty()) {
            return collect();
        }
        $users->loadMissing(['ownedTeams', 'teams', 'sucursales', 'sucursalPrincipal', 'currentSucursal']);
        $assignments = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->whereIn('model_has_roles.model_id', $users->modelKeys())->where('roles.name', '!=', 'Super Admin')
            ->get(['model_has_roles.model_id', 'model_has_roles.team_id', 'roles.id as role_id'])->groupBy('model_id');
        $invitations = app(UserInvitationService::class)->detailsFor($users);

        return $users->mapWithKeys(fn (User $user) => [$user->id => $this->user($user, $assignments->get($user->id, collect()), $invitations->get($user->id))]);
    }

    public function user(User $user, ?Collection $assignments = null, ?array $invitation = null): array
    {
        $teamRoles = ($assignments ?? DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->where('model_has_roles.model_id', $user->id)
            ->where('roles.name', '!=', 'Super Admin')
            ->get(['model_has_roles.team_id', 'roles.id as role_id']))
            ->groupBy('team_id');

        $teams = $user->ownedTeams->merge($user->teams)->unique('id');

        return [
            ...($invitation ?? app(UserInvitationService::class)->details($user)),
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'status_label' => $user->status->label(),
            'is_super_admin' => (bool) $user->is_super_admin,
            'current_team_id' => $user->current_team_id,
            'sucursal_principal_id' => $user->sucursal_principal_id,
            'current_sucursal_id' => $user->current_sucursal_id,
            'sucursal_ids' => $user->sucursales->pluck('id')->all(),
            'team_names' => $teams->pluck('name')->values()->all(),
            'sucursal_names' => $user->sucursales->pluck('nombre')->values()->all(),
            'team_search' => $teams->pluck('name')->join(' '),
            'sucursal_search' => $user->sucursales->pluck('nombre')->join(' '),
            'team_roles' => $teams->map(fn (Team $team) => [
                'team_id' => $team->id,
                'role_ids' => $teamRoles->get($team->id, collect())->pluck('role_id')->all(),
            ])->values()->all(),
            'invited_at' => $user->invited_at?->toIso8601String(),
        ];
    }
}

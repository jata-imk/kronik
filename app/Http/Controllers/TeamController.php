<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Http\Controllers\Inertia\TeamController as InertiaTeamController;
use Laravel\Jetstream\Jetstream;

class TeamController extends InertiaTeamController
{
    /**
     * Show the team management screen.
     *
     * @param  int  $teamId
     * @return \Inertia\Response
     */
    public function show(Request $request, $teamId)
    {
        $team = Jetstream::newTeamModel()->findOrFail($teamId);
        Gate::authorize('view', $team);

        $team = $team->load(
            'owner.sucursales',
            'owner.sucursalPrincipal',
            'users.sucursales',
            'users.sucursalPrincipal',
        );
        $canManageUsers = $request->user()->can('read users');
        $members = collect([$team->owner])->merge($team->users)->unique('id');
        $roleAssignments = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->where('model_has_roles.team_id', $team->id)
            ->whereIn('model_has_roles.model_id', $members->pluck('id'))
            ->get(['model_has_roles.model_id', 'roles.id', 'roles.name'])
            ->groupBy('model_id');

        return Jetstream::inertia()->render($request, 'Teams/Show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'activo' => $team->activo,
                'personal_team' => $team->personal_team,
                'owner' => $team->owner,
                'members_count' => $members->count(),
            ],
            'members' => $canManageUsers ? $members->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url,
                'status' => $user->status->value,
                'status_label' => $user->status->label(),
                'is_super_admin' => (bool) $user->is_super_admin,
                'is_owner' => $team->owner->is($user),
                'roles' => $roleAssignments->get($user->id, collect())->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->values(),
                'sucursal_principal' => $user->sucursalPrincipal
                    ? ['id' => $user->sucursalPrincipal->id, 'nombre' => $user->sucursalPrincipal->nombre]
                    : null,
                'sucursales' => $user->sucursales->map->only(['id', 'nombre', 'clave'])->values(),
            ])->values() : [],
            'roles' => $canManageUsers
                ? config('permission.models.role')::query()
                    ->where(config('permission.column_names.team_foreign_key', 'team_id'), $team->id)
                    ->where('name', '!=', 'Super Admin')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
            'sucursales' => $canManageUsers
                ? Sucursal::query()->where('activa', true)->orderBy('nombre')->get(['id', 'nombre', 'clave'])
                : [],
            'canManageUsers' => $canManageUsers,
        ]);
    }

    /**
     * Create a new team.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $creator = app(CreatesTeams::class);

        $creator->create($request->user(), $request->all());

        return $this->redirectPath($creator);
    }
}

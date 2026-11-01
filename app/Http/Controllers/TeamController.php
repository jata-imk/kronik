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
        );
        $context = app(\App\Services\TeamContext::class);
        $context->ensureAccess($request->user(), $team);
        $capabilities = $context->run($request->user(), $team, fn () => collect([
            'update teams', 'delete teams', 'read roles', 'read activity-log', 'remove members teams',
        ])->mapWithKeys(fn ($permission) => [str_replace(' ', '-', $permission) => $request->user()->can($permission)]));
        $canManageUsers = $request->user()->can('read users');
        [$membersPage, $pendingPage, $memberCount, $activeRoleCounts, $membersMeta, $pendingMeta] = app(\App\Services\EquipoListadoService::class)->paginas($request, $team);
        $members = $canManageUsers ? $membersPage->getCollection()->concat($pendingPage->getCollection())->unique('id') : collect();
        $members->loadMissing(['sucursales', 'sucursalPrincipal']);
        $roleAssignments = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->where('model_has_roles.team_id', $team->id)
            ->whereIn('model_has_roles.model_id', $members->pluck('id'))
            ->get(['model_has_roles.model_id', 'roles.id', 'roles.name'])
            ->groupBy('model_id');

        $userPayloads = $canManageUsers ? app(\App\Services\UserAdministrationData::class)->users($members) : collect();
        $memberPayload = fn (User $user) => [
            ...$userPayloads->get($user->id, []),
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
        ];

        $request->validate(['invitar_roles_page' => 'nullable|integer|min:1', 'invitar_roles_buscar' => 'nullable|string|max:100', 'invitar_roles_por_pagina' => ['nullable', 'integer', \Illuminate\Validation\Rule::in([10, 25, 50])]]);
        $inviteRoles = $canManageUsers ? $team->ownedRoles()->where('guard_name', 'web')->where('name', '!=', 'Super Admin')
            ->when($request->filled('invitar_roles_buscar'), fn ($q) => $q->where('name', 'like', '%'.$request->input('invitar_roles_buscar').'%'))
            ->orderBy('name')->orderBy('id')->paginate($request->integer('invitar_roles_por_pagina', 25), ['id', 'name'], 'invitar_roles_page')->withQueryString() : null;
        $request->validate(['roles_page' => 'nullable|integer|min:1', 'roles_por_pagina' => ['nullable', 'integer', \Illuminate\Validation\Rule::in([10, 25, 50])]]);
        $rolePage = $capabilities['read-roles'] ? $team->ownedRoles()->where('guard_name', 'web')
            ->whereIn('id', $activeRoleCounts->keys())->with('permissions.module')->orderBy('name')->orderBy('id')
            ->paginate($request->integer('roles_por_pagina', 25), ['*'], 'roles_page')->withQueryString() : null;

        return Jetstream::inertia()->render($request, 'Teams/Show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'activo' => $team->activo,
                'personal_team' => $team->personal_team,
                'owner' => $team->owner,
                'members_count' => $memberCount,
            ],
            'listado' => $canManageUsers ? $membersMeta : null,
            'pendingListado' => $canManageUsers ? $pendingMeta : null,
            'members' => $canManageUsers ? $membersPage->getCollection()->map($memberPayload)->values() : [],
            'pendingInvitations' => $canManageUsers ? $pendingPage->getCollection()->map($memberPayload)->values() : [],
            'roles' => $inviteRoles?->getCollection() ?? [],
            'inviteRolesListado' => $inviteRoles ? app(\App\Services\ListadoParametros::class)->metadata($inviteRoles, []) : null,
            'sucursales' => $canManageUsers
                ? Sucursal::query()->where('activa', true)->orderBy('nombre')->get(['id', 'nombre', 'clave'])
                : [],
            'canManageUsers' => $canManageUsers,
            'userFormOptions' => $canManageUsers ? app(\App\Services\UserAdministrationData::class)->formOptions($request->user()) : [],
            'teamCapabilities' => $capabilities,
            'responsibleCandidates' => $capabilities['update-teams'] ? app(\App\Services\EquipoListadoService::class)->miembros($team)->where('status', 'active')->where('id', '!=', $team->user_id)->orderBy('name')->limit(20)->get(['id', 'name']) : [],
            'teamRolesListado' => $rolePage ? app(\App\Services\ListadoParametros::class)->metadata($rolePage, []) : null,
            'teamRoles' => $rolePage ? $rolePage->getCollection()->map(fn ($role) => [
                'id' => $role->id, 'name' => $role->name, 'members_count' => (int) $activeRoleCounts[$role->id],
                'modules' => $role->permissions->pluck('module.etiqueta')->filter()->unique()->values(),
            ]) : [],
        ]);
    }

    public function responsables(Request $request, \App\Models\Team $team)
    {
        Gate::authorize('view', $team);
        app(\App\Services\TeamContext::class)->authorize($request->user(), $team, 'update teams');
        $data = $request->validate(['buscar' => 'nullable|string|max:100']);

        return app(\App\Services\EquipoListadoService::class)->miembros($team)->where('status', 'active')->where('id', '!=', $team->user_id)
            ->when($data['buscar'] ?? null, fn ($q, $v) => $q->where('name', 'like', '%'.$v.'%'))
            ->orderBy('name')->orderBy('id')->limit(20)->get(['id', 'name']);
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

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
        $context = app(\App\Services\TeamContext::class);
        $context->ensureAccess($request->user(), $team);
        $capabilities = $context->run($request->user(), $team, fn () => collect([
            'update teams', 'delete teams', 'read roles', 'read activity-log',
        ])->mapWithKeys(fn ($permission) => [str_replace(' ', '-', $permission) => $request->user()->can($permission)]));
        $canManageUsers = $request->user()->can('read users');
        $members = collect([$team->owner])->merge($team->users)->unique('id');
        $roleAssignments = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->where('model_has_roles.team_id', $team->id)
            ->whereIn('model_has_roles.model_id', $members->pluck('id'))
            ->get(['model_has_roles.model_id', 'roles.id', 'roles.name'])
            ->groupBy('model_id');

        $memberPayload = fn (User $user) => [
            ...app(\App\Services\UserInvitationService::class)->details($user),
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

        return Jetstream::inertia()->render($request, 'Teams/Show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'activo' => $team->activo,
                'personal_team' => $team->personal_team,
                'owner' => $team->owner,
                'members_count' => $members->count(),
            ],
            'members' => $canManageUsers ? $members->reject(fn ($user) => $user->status === \App\Enums\UserStatus::Pending)->map($memberPayload)->values() : [],
            'pendingInvitations' => $canManageUsers ? $members->filter(fn ($user) => $user->status === \App\Enums\UserStatus::Pending)->map($memberPayload)->values() : [],
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
            'teamCapabilities' => $capabilities,
            'responsibleCandidates' => $capabilities['update-teams'] ? $members
                ->filter(fn ($user) => $user->status === \App\Enums\UserStatus::Active && $user->id !== $team->user_id)
                ->map->only(['id', 'name'])->values() : [],
            'teamRoles' => $capabilities['read-roles'] ? $team->ownedRoles()->with('permissions.module')->get()->map(fn ($role) => [
                'id' => $role->id, 'name' => $role->name,
                'members_count' => $roleAssignments->flatten(1)->where('id', $role->id)->pluck('model_id')->unique()->count(),
                'modules' => $role->permissions->pluck('module.etiqueta')->filter()->unique()->values(),
            ]) : [],
            'recentActivity' => $capabilities['read-activity-log'] ? config('activitylog.activity_model')::where('team_id', $team->id)
                ->latest()->limit(10)->get(['id', 'description', 'created_at']) : [],
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

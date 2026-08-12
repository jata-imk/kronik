<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeamRequest;
use App\Http\Requests\Admin\UpdateTeamRequest;
use App\Models\Team;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;

class TeamController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:read teams', only: ['index']),
            new Middleware('permission:create teams', only: ['store']),
            new Middleware('permission:update teams', only: ['update']),
            new Middleware('permission:delete teams', only: ['destroy']),
        ];
    }

    public function index()
    {
        return Inertia::render('Admin/Teams/Index', [
            'teams' => fn () => Team::query()
                ->with('owner:id,name,email,profile_photo_path')
                ->withCount(['users', 'ownedRoles'])
                ->orderByDesc('activo')
                ->orderBy('name')
                ->get()
                ->map(fn (Team $team) => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'activo' => $team->activo,
                    'personal_team' => $team->personal_team,
                    'status_label' => $team->activo ? 'Activo' : 'Inactivo',
                    'type_label' => $team->personal_team ? 'Personal' : 'Institucional',
                    'owner' => $team->owner,
                    'members_count' => $team->users_count + 1,
                    'roles_count' => $team->owned_roles_count,
                    'current_users_count' => $team->currentUsers()->count(),
                ]),
        ]);
    }

    public function store(StoreTeamRequest $request)
    {
        DB::transaction(function () use ($request) {
            $team = $request->user()->ownedTeams()->create([
                'name' => $request->validated('name'),
                'personal_team' => false,
                'activo' => true,
            ]);

            $this->copyGlobalRoles($team);
        });

        return back()->with('success', 'Equipo creado');
    }

    public function update(UpdateTeamRequest $request, Team $team)
    {
        $team->update($request->validated());

        return back()->with('success', 'Equipo actualizado');
    }

    public function destroy(Team $team)
    {
        if ($team->currentUsers()->exists()) {
            throw ValidationException::withMessages([
                'team' => 'Cambia el equipo actual de todos sus usuarios antes de desactivarlo.',
            ]);
        }

        $team->update(['activo' => false]);

        return back()->with('success', 'Equipo desactivado');
    }

    private function copyGlobalRoles(Team $team): void
    {
        $roleModel = config('permission.models.role');
        $teamKey = config('permission.column_names.team_foreign_key', 'team_id');
        $previousTeamId = getPermissionsTeamId();

        try {
            setPermissionsTeamId($team->id);
            $globalRoles = $roleModel::query()
                ->whereNull($teamKey)
                ->where('name', '!=', 'Super Admin')
                ->with('permissions')
                ->get();

            foreach ($globalRoles as $globalRole) {
                $teamRole = $roleModel::query()->firstOrCreate([
                    'name' => $globalRole->name,
                    'guard_name' => $globalRole->guard_name,
                    $teamKey => $team->id,
                ]);
                $teamRole->syncPermissions($globalRole->permissions);
            }
        } finally {
            setPermissionsTeamId($previousTeamId);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}

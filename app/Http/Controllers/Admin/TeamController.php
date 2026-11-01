<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeamRequest;
use App\Http\Requests\Admin\UpdateTeamRequest;
use App\Models\Team;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;

class TeamController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:read teams', only: ['index']),
            new Middleware('permission:create teams', only: ['store']),
        ];
    }

    public function index(\Illuminate\Http\Request $request)
    {
        return Inertia::render('Admin/Teams/Index', app(\App\Services\AdministracionListadoService::class)->equipos($request));
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
        app(\App\Services\TeamManagementService::class)->update($team, $request->validated());

        return back()->with('success', 'Equipo actualizado');
    }

    public function destroy(Team $team)
    {
        app(\App\Services\TeamContext::class)->authorize(request()->user(), $team, 'delete teams');
        app(\App\Services\TeamManagementService::class)->update($team, ['activo' => false]);

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

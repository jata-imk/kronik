<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeamRequest;
use App\Http\Requests\Admin\UpdateTeamRequest;
use App\Models\Team;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

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
        app(\App\Services\TeamManagementService::class)->create($request->user(), $request->validated());

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
}

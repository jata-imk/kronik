<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\TeamContext;
use App\Services\TeamManagementService;
use Illuminate\Http\Request;

class TeamResponsibleController extends Controller
{
    public function update(Request $request, Team $team, TeamContext $context, TeamManagementService $service)
    {
        $context->authorize($request->user(), $team, 'update teams');
        $data = $request->validate([
            'responsible_id' => ['required', 'integer', 'exists:users,id'], 'confirmation' => ['accepted'],
        ]);
        $service->transfer($request->user(), $team, (int) $data['responsible_id']);

        return back()->with('success', 'Responsable del equipo actualizado');
    }
}

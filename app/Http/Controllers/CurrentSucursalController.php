<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CurrentSucursalController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
        ], [], [
            'sucursal_id' => 'sucursal',
        ]);

        $sucursal = Sucursal::findOrFail($validated['sucursal_id']);

        $actor = $request->user();
        $original = $actor->current_sucursal_id;
        $team = $actor->current_team_id;
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $sucursal, $actor, $original, $team) {
            abort_unless($request->user()->switchSucursal($sucursal), 403, 'No tienes acceso a la sucursal seleccionada.');

            if ((int) $original !== (int) $sucursal->id) {
                app(\App\Services\ActivityLogService::class)->log(
                    \App\Enums\ActivityEvent::ActiveBranchChanged, 'Sucursal activa cambiada', $actor,
                    ['related' => ['type' => 'sucursal', 'id' => $sucursal->id], 'result' => 'changed'],
                    $actor, teamId: $team, sucursalId: $original, inheritActorContext: false,
                );
            }
        });
        $path = parse_url($request->headers->get('referer', ''), PHP_URL_PATH) ?? '';
        $destination = match (true) {
            $path === '/mi-trabajo' => 'solicitudes.trabajo',
            $path === '/evaluacion-solicitudes' => 'evaluacion-solicitudes.index',
            $path === '/cumplimiento' => 'cumplimiento.index',
            $path === '/clientes/expedientes' => 'clientes.expedientes.index',
            str_starts_with($path, '/solicitudes') => 'solicitudes.index',
            str_starts_with($path, '/creditos') => 'creditos.index',
            str_starts_with($path, '/consultas-sic'), str_contains($path, 'historial-crediticio') => 'clientes.historial-crediticio.index',
            str_starts_with($path, '/clientes') => 'clientes.index',
            default => null,
        };

        return $destination ? redirect()->route($destination, status: 303) : back(303);
    }
}

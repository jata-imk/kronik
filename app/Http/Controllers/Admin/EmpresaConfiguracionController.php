<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEmpresaConfiguracionRequest;
use App\Models\RegimenFiscal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmpresaConfiguracionController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        $configuracion = $team->empresaConfiguracion()->firstOrCreate([], [
            'moneda' => 'MXN',
            'zona_horaria' => 'America/Mexico_City',
            'folio_credito_siguiente' => 1,
            'integraciones' => [
                'circulo_credito' => [
                    'habilitado' => false,
                    'env_prefix' => 'CDC',
                ],
                'geocoding' => [
                    'habilitado' => false,
                    'env_key' => 'GEOCODING_API_KEY',
                ],
            ],
        ]);

        return Inertia::render('Admin/EmpresaConfiguracion/Index', [
            'team' => $team->only(['id', 'name']),
            'configuracion' => $configuracion,
            'regimenesFiscales' => RegimenFiscal::query()
                ->orderBy('clave')
                ->get(['id', 'clave', 'descripcion', 'fisica', 'moral']),
            'timezones' => [
                'America/Mexico_City',
                'America/Cancun',
                'America/Monterrey',
                'America/Tijuana',
                'UTC',
            ],
        ]);
    }

    public function update(UpdateEmpresaConfiguracionRequest $request)
    {
        $team = $request->user()->currentTeam;
        $configuracion = $team->empresaConfiguracion()->firstOrNew();
        $before = $configuracion->exists ? $configuracion->getOriginal() : [];

        $data = $request->validated();
        $data['activated_at'] = $data['activa'] ? ($configuracion->activated_at ?? now()) : null;

        $configuracion->fill($data);
        $configuracion->team()->associate($team);
        $configuracion->save();

        activity()
            ->causedBy($request->user())
            ->performedOn($configuracion)
            ->withProperties([
                'team_id' => $team->id,
                'before' => $before,
                'after' => $configuracion->fresh()->toArray(),
            ])
            ->log('Configuración de empresa actualizada');

        return redirect()->route('admin.empresa-configuracion.index')
            ->with('success', 'Configuración de empresa actualizada');
    }
}

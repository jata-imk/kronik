<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEmpresaConfiguracionRequest;
use App\Models\EmpresaConfiguracion;
use App\Models\Pais;
use App\Models\RegimenFiscal;
use DateTimeZone;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmpresaConfiguracionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role_or_permission:Super Admin|read configuracion-empresa', only: ['index']),
            new Middleware('role_or_permission:Super Admin|update configuracion-empresa', only: ['update']),
        ];
    }

    public function index()
    {
        $configuracion = EmpresaConfiguracion::firstOrCreate(
            ['singleton_key' => 'default'],
            ['estatus' => 'borrador'],
        );

        $payload = $configuracion->toArray();
        $integraciones = $configuracion->integraciones ?? [];
        $payload['integraciones'] = [
            'circulo_credito_host' => $integraciones['circulo_credito_host'] ?? null,
            'circulo_credito_sandbox' => $integraciones['circulo_credito_sandbox'] ?? true,
            'circulo_credito_api_key_configurada' => ! empty($integraciones['circulo_credito_api_key']),
        ];

        return Inertia::render('Admin/ConfiguracionEmpresa/Index', [
            'configuracion' => $payload,
            'tiposPersona' => [
                ['value' => 'fisica', 'label' => 'Persona física'],
                ['value' => 'moral', 'label' => 'Persona moral'],
            ],
            'regimenesFiscales' => RegimenFiscal::query()
                ->orderBy('clave')
                ->get(['id', 'clave', 'descripcion', 'fisica', 'moral']),
            'paises' => Pais::query()
                ->orderBy('nombre_es')
                ->get(['id', 'nombre_es', 'codigo_iso', 'emoji']),
            'zonasHorarias' => collect(DateTimeZone::listIdentifiers())
                ->map(fn (string $identifier) => ['value' => $identifier, 'label' => str_replace('_', ' ', $identifier)])
                ->values(),
        ]);
    }

    public function update(UpdateEmpresaConfiguracionRequest $request)
    {
        $fields = $request->validated();

        $configuracion = EmpresaConfiguracion::firstOrCreate(
            ['singleton_key' => 'default'],
            ['estatus' => 'borrador'],
        );

        $integraciones = $configuracion->integraciones ?? [];
        $incomingIntegraciones = $fields['integraciones'] ?? [];
        $integraciones['circulo_credito_host'] = $incomingIntegraciones['circulo_credito_host'] ?? null;
        $integraciones['circulo_credito_sandbox'] = $incomingIntegraciones['circulo_credito_sandbox'] ?? true;

        if (! empty($incomingIntegraciones['circulo_credito_api_key'])) {
            $integraciones['circulo_credito_api_key'] = $incomingIntegraciones['circulo_credito_api_key'];
        }

        $fields['integraciones'] = $integraciones;
        $fields['singleton_key'] = 'default';

        $before = $this->auditPayload($configuracion);
        $configuracion->update($fields);

        activity()
            ->performedOn($configuracion)
            ->causedBy(Auth::user())
            ->event('empresa.updated')
            ->withProperties([
                'before' => $before,
                'after' => $this->auditPayload($configuracion->fresh()),
            ])
            ->log('Configuracion de empresa actualizada');

        return redirect()->back()->with('success', 'Configuracion de empresa actualizada');
    }

    private function auditPayload(EmpresaConfiguracion $configuracion): array
    {
        $payload = $configuracion->toArray();
        $payload['integraciones'] = [
            'circulo_credito_host' => $configuracion->integraciones['circulo_credito_host'] ?? null,
            'circulo_credito_sandbox' => $configuracion->integraciones['circulo_credito_sandbox'] ?? true,
            'circulo_credito_api_key_configurada' => ! empty($configuracion->integraciones['circulo_credito_api_key'] ?? null),
        ];

        return $payload;
    }
}

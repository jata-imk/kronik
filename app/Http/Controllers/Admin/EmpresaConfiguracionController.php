<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
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
            'circulo_credito_api_key_configurada' => !empty($integraciones['circulo_credito_api_key']),
        ];

        return Inertia::render('Admin/ConfiguracionEmpresa/Index', [
            'configuracion' => $payload,
        ]);
    }

    public function update(Request $request)
    {
        $fields = $request->validate([
            'razon_social' => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'regimen_fiscal' => ['nullable', 'string', 'max:255'],
            'domicilio_fiscal' => ['nullable', 'array'],
            'domicilio_fiscal.calle' => ['nullable', 'string', 'max:255'],
            'domicilio_fiscal.numero_exterior' => ['nullable', 'string', 'max:50'],
            'domicilio_fiscal.numero_interior' => ['nullable', 'string', 'max:50'],
            'domicilio_fiscal.colonia' => ['nullable', 'string', 'max:127'],
            'domicilio_fiscal.municipio' => ['nullable', 'string', 'max:127'],
            'domicilio_fiscal.estado' => ['nullable', 'string', 'max:127'],
            'domicilio_fiscal.codigo_postal' => ['nullable', 'string', 'max:15'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:127'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
            'moneda' => ['required', 'string', 'size:3'],
            'zona_horaria' => ['required', 'string', 'max:64'],
            'pais_base' => ['required', 'string', 'size:2'],
            'logotipo_path' => ['nullable', 'string', 'max:255'],
            'parametros_operativos' => ['nullable', 'array'],
            'parametros_operativos.dias_gracia_default' => ['nullable', 'integer', 'min:0', 'max:365'],
            'parametros_operativos.hora_corte_operativo' => ['nullable', 'string', 'max:8'],
            'integraciones' => ['nullable', 'array'],
            'integraciones.circulo_credito_host' => ['nullable', 'url', 'max:255'],
            'integraciones.circulo_credito_sandbox' => ['nullable', 'boolean'],
            'integraciones.circulo_credito_api_key' => ['nullable', 'string', 'max:255'],
            'estatus' => ['required', 'string', 'in:borrador,activa,suspendida'],
        ]);

        $configuracion = EmpresaConfiguracion::firstOrCreate(
            ['singleton_key' => 'default'],
            ['estatus' => 'borrador'],
        );

        $integraciones = $configuracion->integraciones ?? [];
        $incomingIntegraciones = $fields['integraciones'] ?? [];
        $integraciones['circulo_credito_host'] = $incomingIntegraciones['circulo_credito_host'] ?? null;
        $integraciones['circulo_credito_sandbox'] = $incomingIntegraciones['circulo_credito_sandbox'] ?? true;

        if (!empty($incomingIntegraciones['circulo_credito_api_key'])) {
            $integraciones['circulo_credito_api_key'] = $incomingIntegraciones['circulo_credito_api_key'];
        }

        $fields['integraciones'] = $integraciones;
        $fields['singleton_key'] = 'default';

        $before = $this->auditPayload($configuracion);
        $configuracion->update($fields);

        activity()
            ->performedOn($configuracion)
            ->causedBy(Auth::user())
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
            'circulo_credito_api_key_configurada' => !empty($configuracion->integraciones['circulo_credito_api_key'] ?? null),
        ];

        return $payload;
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\ClienteDocumentoEstado;
use App\Enums\ClienteDocumentoTipo;
use App\Enums\ClienteGarantiaTipo;
use App\Enums\ClienteReferenciaTipo;
use App\Enums\ClienteVinculoRol;
use App\Enums\ConsentimientoSicMedio;
use App\Http\Requests\Clientes\UpdateClienteKycRequest;
use App\Models\Cliente;
use App\Models\EmpresaConfiguracion;
use App\Services\ClienteExpedienteService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ClienteExpedienteController extends Controller
{
    public function show(Cliente $cliente)
    {
        Gate::authorize('view', $cliente);

        $cliente->load([
            'paisNacimiento',
            'datosFiscales.regimenFiscal',
            'documentos.revisor:id,name',
            'referencias',
            'vinculos.vinculado.datosFiscales',
            'vinculosEntrantes.cliente.datosFiscales',
            'garantias.propietario.datosFiscales',
            'consentimientosSic.registrador:id,name',
        ]);

        $actuales = $cliente->documentos->where('es_actual', true);
        $perfil = collect([
            $cliente->ocupacion,
            $cliente->actividad_economica,
            $cliente->ingresos_mensuales,
            $cliente->egresos_mensuales,
            $cliente->origen_recursos,
        ])->filter(fn ($value) => filled($value))->count();

        $relaciones = $cliente->vinculos
            ->map(fn ($vinculo) => [
                'id' => $vinculo->id,
                'direccion' => 'saliente',
                'rol' => $vinculo->rol->value,
                'notas' => $vinculo->notas,
                'cliente' => $vinculo->vinculado,
                'puede_eliminar' => true,
            ])
            ->concat($cliente->vinculosEntrantes->map(fn ($vinculo) => [
                'id' => $vinculo->id,
                'direccion' => 'entrante',
                'rol' => $vinculo->rol->value,
                'notas' => $vinculo->notas,
                'cliente' => $vinculo->cliente,
                'puede_eliminar' => false,
            ]))
            ->values();

        return Inertia::render('Clientes/Expediente', [
            'cliente' => $cliente,
            'relaciones' => $relaciones,
            'clientesDisponibles' => Cliente::query()
                ->whereKeyNot($cliente->id)
                ->with('datosFiscales:id,cliente_id,rfc')
                ->orderBy('primer_nombre')
                ->get(['id', 'primer_nombre', 'segundo_nombre', 'apellido_paterno', 'apellido_materno']),
            'resumen' => [
                'perfil_completado' => $perfil,
                'perfil_total' => 5,
                'documentos_recibidos' => $actuales->whereNotNull('path')->count(),
                'documentos_validados' => $actuales->where('estado', ClienteDocumentoEstado::Validado)->count(),
                'documentos_requeridos' => count(ClienteDocumentoTipo::requeridos()),
            ],
            'opciones' => [
                'documentos' => $this->options(ClienteDocumentoTipo::cases(), [
                    'ine' => 'INE',
                    'comprobante_domicilio' => 'Comprobante de domicilio',
                    'constancia_fiscal' => 'Constancia fiscal',
                    'comprobante_ingresos' => 'Comprobante de ingresos',
                    'adicional' => 'Documento adicional',
                ]),
                'estados_documento' => $this->options(ClienteDocumentoEstado::cases()),
                'referencias' => $this->options(ClienteReferenciaTipo::cases()),
                'vinculos' => $this->options(ClienteVinculoRol::cases()),
                'garantias' => $this->options(ClienteGarantiaTipo::cases()),
                'medios_consentimiento' => $this->options(ConsentimientoSicMedio::cases()),
                'moneda' => EmpresaConfiguracion::query()->value('moneda') ?? 'MXN',
            ],
            'can' => [
                'update' => request()->user()->can('update', $cliente),
            ],
        ]);
    }

    public function updateProfile(UpdateClienteKycRequest $request, Cliente $cliente, ClienteExpedienteService $service)
    {
        $service->updateProfile($cliente, $request->validated());

        return back()->with('success', 'Perfil economico actualizado');
    }

    private function options(array $cases, array $labels = []): array
    {
        return collect($cases)->map(fn ($case) => [
            'value' => $case->value,
            'label' => $labels[$case->value] ?? str($case->value)->replace('_', ' ')->title()->toString(),
        ])->all();
    }
}

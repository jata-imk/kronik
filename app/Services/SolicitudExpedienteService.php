<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ClienteDocumento;

class SolicitudExpedienteService
{
    public function huella(Cliente $cliente): string
    {
        $documentos = ClienteDocumento::where('cliente_id', $cliente->id)->where('es_actual', true)
            ->orderBy('id')->get(['id', 'tipo', 'version', 'estado', 'vence_en', 'revisado_en', 'tamano_bytes']);

        return hash('sha256', json_encode([
            'cliente' => $this->identidad($cliente), 'documentos' => $documentos->toArray(),
        ], JSON_THROW_ON_ERROR));
    }

    public function datosCliente(Cliente $cliente): array
    {
        return $cliente->only(['primer_nombre', 'segundo_nombre', 'apellido_paterno', 'apellido_materno',
            'fecha_nacimiento', 'ocupacion', 'actividad_economica', 'ingresos_mensuales', 'egresos_mensuales', 'origen_recursos']);
    }

    public function identidad(Cliente $cliente): array
    {
        return [
            'persona' => $this->datosCliente($cliente),
            'fiscales' => $cliente->datosFiscales()->first()?->only(['tipo_persona', 'regimen_fiscal_id', 'curp', 'rfc', 'razon_social']),
            'domicilios' => $cliente->direcciones()->orderBy('id')->get(['id', 'tipo', 'pais_id', 'codigo_postal_id',
                'linea_uno', 'linea_dos', 'linea_tres', 'division_admin_uno_id', 'division_admin_dos_id', 'division_admin_tres_id'])->toArray(),
        ];
    }
}

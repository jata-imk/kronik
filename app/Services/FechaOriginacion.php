<?php

namespace App\Services;

use App\Models\ProductoVersion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Fechas civiles del escenario; nunca representan el instante de captura. */
final class FechaOriginacion
{
    public function __construct(private readonly FechaEmpresa $fecha) {}

    public function problema(?string $fecha, ?ProductoVersion $version): ?string
    {
        if (Validator::make(['fecha' => $fecha], ['fecha' => 'required|date_format:Y-m-d'])->fails()) {
            return 'Indica una fecha efectiva válida con formato AAAA-MM-DD.';
        }
        if ($fecha < $this->fecha->hoy()->toDateString() && config('originacion.desembolsos_qa_habilitados') !== true) {
            return 'Las fechas pasadas requieren habilitar el registro de desembolsos históricos exclusivamente en QA.';
        }
        if (! $version?->vigente_desde || $fecha < $version->vigente_desde->toDateString()) {
            return 'La fecha efectiva no puede ser anterior a la vigencia de la versión seleccionada. Selecciona condiciones aplicables; no se modifica una versión existente.';
        }

        return null;
    }

    public function validarActivacion(string $fecha, bool $confirmacionHistorica): void
    {
        Validator::make(['vigente_desde' => $fecha], ['vigente_desde' => 'required|date_format:Y-m-d'])->validate();
        if ($fecha < $this->fecha->hoy()->toDateString()) {
            if (config('originacion.desembolsos_qa_habilitados') !== true) {
                throw ValidationException::withMessages(['vigente_desde' => 'La fecha de vigencia debe ser hoy o una fecha futura según la zona horaria de la empresa.']);
            }
            if (! $confirmacionHistorica) {
                throw ValidationException::withMessages(['confirmacion_historica' => 'Confirma la vigencia pasada exclusivamente para el caso sintético QA. La activación se registra en el momento real.']);
            }
        }
    }
}

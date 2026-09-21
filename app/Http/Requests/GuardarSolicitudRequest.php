<?php

namespace App\Http\Requests;

use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Models\Solicitud;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        $solicitud = $this->route('solicitud');

        return $solicitud ? $this->user()->can('update', $solicitud) : $this->user()->can('create', Solicitud::class);
    }

    public function rules(): array
    {
        return [
            'cliente_id' => $this->isMethod('post') ? 'required|integer|exists:clientes,id' : 'prohibited',
            'clave_creacion' => $this->isMethod('post') ? 'required|uuid' : 'prohibited',
            'lock_version' => $this->isMethod('post') ? 'prohibited' : 'required|integer|min:0',
            'producto_version_id' => 'nullable|integer|exists:producto_versiones,id',
            'monto' => ['nullable', 'regex:/^\d{1,12}(\.\d{1,2})?$/', 'numeric', 'gt:0'],
            'plazo' => 'nullable|integer|min:1|max:600',
            'periodicidad' => ['nullable', Rule::enum(PeriodicidadCredito::class)],
            'metodo' => ['nullable', Rule::enum(MetodoAmortizacion::class)],
            'destino' => 'nullable|string|max:2000',
            'fecha_estimada' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function messages(): array
    {
        return [
            'monto.regex' => 'Escribe un monto positivo con máximo dos decimales.',
            'cliente_id.prohibited' => 'No se puede cambiar el cliente de una solicitud.',
            'lock_version.required' => 'Actualiza la solicitud antes de guardar.',
        ];
    }
}

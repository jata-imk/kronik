<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cliente')) ?? false;
    }

    public function rules(): array
    {
        return [
            'ocupacion' => ['present', 'nullable', 'string', 'max:127'],
            'actividad_economica' => ['present', 'nullable', 'string', 'max:255'],
            'ingresos_mensuales' => ['present', 'nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'egresos_mensuales' => ['present', 'nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'origen_recursos' => ['present', 'nullable', 'string', 'max:2000'],
        ];
    }
}

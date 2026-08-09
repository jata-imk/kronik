<?php

namespace App\Http\Requests\Clientes;

use App\Enums\ClienteDocumentoEstado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteDocumentoEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cliente')) ?? false;
    }

    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                Rule::in([
                    ClienteDocumentoEstado::Validado->value,
                    ClienteDocumentoEstado::Rechazado->value,
                    ClienteDocumentoEstado::Vencido->value,
                ]),
            ],
            'motivo_rechazo' => ['nullable', 'required_if:estado,rechazado', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_rechazo.required_if' => 'Indique el motivo por el que se rechaza el documento.',
        ];
    }
}

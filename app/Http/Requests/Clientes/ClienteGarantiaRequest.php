<?php

namespace App\Http\Requests\Clientes;

use App\Enums\ClienteGarantiaTipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteGarantiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cliente')) ?? false;
    }

    public function rules(): array
    {
        return [
            'propietario_cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'tipo' => ['required', Rule::enum(ClienteGarantiaTipo::class)],
            'descripcion' => ['required', 'string', 'max:255'],
            'valor_estimado' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'moneda' => ['required', 'string', 'size:3'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

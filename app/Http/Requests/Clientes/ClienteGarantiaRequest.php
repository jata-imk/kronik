<?php

namespace App\Http\Requests\Clientes;

use App\Enums\ClienteGarantiaTipo;
use App\Models\ClienteVinculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteGarantiaRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $cliente = $this->route('cliente');

        if ((int) $this->input('propietario_cliente_id') === (int) $cliente?->id) {
            $this->merge(['propietario_cliente_id' => null]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cliente')) ?? false;
    }

    public function rules(): array
    {
        $cliente = $this->route('cliente');
        $relacionados = ClienteVinculo::query()
            ->where('cliente_id', $cliente->id)
            ->pluck('cliente_vinculado_id')
            ->merge(ClienteVinculo::query()
                ->where('cliente_vinculado_id', $cliente->id)
                ->pluck('cliente_id'))
            ->unique()
            ->values()
            ->all();

        return [
            'propietario_cliente_id' => ['nullable', 'integer', Rule::in($relacionados)],
            'tipo' => ['required', Rule::enum(ClienteGarantiaTipo::class)],
            'descripcion' => ['required', 'string', 'max:255'],
            'valor_estimado' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'moneda' => ['required', 'string', 'size:3'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'propietario_cliente_id.in' => 'El propietario debe ser el titular o un cliente relacionado.',
        ];
    }
}

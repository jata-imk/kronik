<?php

namespace App\Http\Requests\Clientes;

use App\Enums\ClienteVinculoRol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteVinculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cliente')) ?? false;
    }

    public function rules(): array
    {
        $cliente = $this->route('cliente');

        return [
            'cliente_vinculado_id' => [
                'required',
                'integer',
                'exists:clientes,id',
                Rule::notIn([$cliente->id]),
                Rule::unique('cliente_vinculos')->where(fn ($query) => $query
                    ->where('cliente_id', $cliente->id)
                    ->where('rol', $this->input('rol'))),
            ],
            'rol' => ['required', Rule::enum(ClienteVinculoRol::class)],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('transfer clientes') ?? false;
    }

    public function rules(): array
    {
        return [
            'sucursal_id' => [
                'required',
                'integer',
                Rule::exists('sucursales', 'id')->where(fn ($query) => $query->where('activa', true)),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['sucursal_id' => 'sucursal de destino'];
    }
}

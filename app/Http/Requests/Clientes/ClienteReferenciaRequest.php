<?php

namespace App\Http\Requests\Clientes;

use App\Enums\ClienteReferenciaTipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteReferenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cliente')) ?? false;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(ClienteReferenciaTipo::class)],
            'nombre' => ['required', 'string', 'max:255'],
            'relacion' => ['nullable', 'required_if:tipo,personal', 'string', 'max:127'],
            'empresa' => ['nullable', 'required_if:tipo,laboral', 'string', 'max:255'],
            'puesto' => ['nullable', 'string', 'max:127'],
            'telefono_codigo_pais' => ['nullable', 'string', 'max:4'],
            'telefono' => ['required', 'string', 'max:15'],
            'email' => ['nullable', 'email', 'max:127'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Clientes;

use App\Enums\ClienteDocumentoTipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cliente')) ?? false;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(ClienteDocumentoTipo::class)],
            'nombre' => ['nullable', 'required_if:tipo,adicional', 'string', 'max:127'],
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'reemplaza_documento_id' => ['nullable', 'integer', 'exists:cliente_documentos,id'],
            'vence_en' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

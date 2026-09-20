<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsultarDocumentoGeneradoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cliente')) ?? false;
    }

    public function rules(): array
    {
        return [
            'version_id' => ['required', 'integer', 'exists:documento_plantilla_versiones,id'],
            'garantia_id' => ['nullable', 'integer', 'exists:cliente_garantias,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'version_id' => 'versión de plantilla',
            'garantia_id' => 'garantía',
        ];
    }
}

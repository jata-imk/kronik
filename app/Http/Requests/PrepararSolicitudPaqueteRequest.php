<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrepararSolicitudPaqueteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('preparePackage', $this->route('solicitud'));
    }

    public function rules(): array
    {
        return [
            'version_id' => ['required', 'integer', 'exists:documento_plantilla_versiones,id'],
            'lock_version' => ['required', 'integer', 'min:0'],
            'idempotency_key' => ['required', 'uuid'],
            'confirmacion_qa' => ['required', 'accepted'],
        ];
    }

    public function attributes(): array
    {
        return ['version_id' => 'versión del contrato', 'idempotency_key' => 'identificador de generación',
            'confirmacion_qa' => 'confirmación de uso exclusivo en QA'];
    }

    public function messages(): array
    {
        return ['confirmacion_qa.accepted' => 'Confirma que el paquete es de prueba y no tiene validez contractual.'];
    }
}

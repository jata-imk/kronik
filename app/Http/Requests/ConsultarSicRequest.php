<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsultarSicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create circulo-credito') && $this->user()->can('viewAny', \App\Models\Cliente::class);
    }

    public function rules(): array
    {
        return ['cliente' => $this->route('cliente') ? ['nullable'] : ['required', 'integer', 'exists:clientes,id'],
            'api' => ['required', Rule::in(array_keys(config('sic.servicios')))], 'consentimiento_id' => ['required', 'integer'],
            'solicitud_revision_id' => ['nullable', 'integer'], 'idempotency_key' => ['required', 'uuid'], 'confirmacion_qa' => ['accepted']];
    }

    public function attributes(): array
    {
        return ['cliente' => 'cliente', 'api' => 'servicio SIC', 'consentimiento_id' => 'consentimiento SIC', 'solicitud_revision_id' => 'revisión de solicitud'];
    }

    public function messages(): array
    {
        return ['confirmacion_qa.accepted' => 'Confirma que consultarás datos sintéticos en el sandbox de Círculo de Crédito.',
            'api.in' => 'Selecciona uno de los tres servicios SIC implementados.'];
    }
}

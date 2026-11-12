<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompletarCapturaHistoricaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pay', $this->route('credito'));
    }

    public function rules(): array
    {
        return ['confirmacion_qa' => ['required', 'accepted'], 'confirmacion_historial_completo' => ['required', 'accepted']];
    }

    public function messages(): array
    {
        return ['confirmacion_qa.required' => 'Confirma que se trata de una captura sintética QA.',
            'confirmacion_qa.accepted' => 'Confirma que se trata de una captura sintética QA.',
            'confirmacion_historial_completo.required' => 'Confirma que incorporaste todos los pagos históricos conocidos.',
            'confirmacion_historial_completo.accepted' => 'Confirma que incorporaste todos los pagos históricos conocidos.'];
    }
}

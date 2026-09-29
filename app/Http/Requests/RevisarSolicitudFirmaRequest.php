<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RevisarSolicitudFirmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviewSignature', $this->route('solicitud'));
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('motivo'))) {
            $this->merge(['motivo' => trim($this->input('motivo'))]);
        }
    }

    public function rules(): array
    {
        return ['estado_firma' => ['required', Rule::in(['aceptada', 'rechazada'])],
            'motivo' => ['required_if:estado_firma,rechazada', 'nullable', 'string', 'min:10', 'max:2000'],
            'confirmacion_revision' => ['accepted_if:estado_firma,aceptada'],
            'confirmacion_qa' => ['accepted'], 'lock_version' => ['required', 'integer', 'min:0']];
    }

    public function messages(): array
    {
        return ['confirmacion_revision.accepted_if' => 'Confirma que comparaste todas las páginas, condiciones, identidad y firmas con el original.',
            'confirmacion_qa.accepted' => 'Confirma que la revisión es exclusivamente QA, sin autorización de dinero real.'];
    }
}

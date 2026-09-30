<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDesembolsoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('disburse', $this->route('solicitud'));
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('referencia'))) {
            $this->merge(['referencia' => trim($this->input('referencia'))]);
        }
    }

    public function rules(): array
    {
        return ['fecha_desembolso' => ['required', 'date_format:Y-m-d'], 'importe' => ['required', 'regex:/^\d{1,14}(\.\d{1,2})?$/', 'gt:0'],
            'referencia' => ['required', 'string', 'min:5', 'max:120'], 'idempotency_key' => ['required', 'uuid'],
            'lock_version' => ['required', 'integer', 'min:0'], 'confirmacion_qa' => ['accepted']];
    }

    public function messages(): array
    {
        return ['importe.regex' => 'Escribe el importe sin separadores, positivo y con máximo dos decimales.',
            'confirmacion_qa.accepted' => 'Confirma que registras una transferencia sintética de QA, sin mover dinero real.'];
    }

    public function attributes(): array
    {
        return ['fecha_desembolso' => 'fecha del desembolso', 'referencia' => 'referencia única de transferencia', 'importe' => 'importe transferido'];
    }
}

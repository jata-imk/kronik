<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimularCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'decimal:0,4', 'gt:0'],
            'periodicidad' => ['required', Rule::in(['semanal', 'quincenal', 'mensual'])],
            'plazo' => ['required', 'integer', 'min:1', 'max:600'],
            'metodo' => ['required', Rule::in(['cuota_nivelada', 'capital_fijo'])],
            'fecha' => ['required', 'date'],
        ];
    }

    public function attributes(): array
    {
        return ['monto' => 'monto', 'periodicidad' => 'periodicidad', 'plazo' => 'plazo', 'metodo' => 'método de amortización', 'fecha' => 'fecha de disposición'];
    }
}

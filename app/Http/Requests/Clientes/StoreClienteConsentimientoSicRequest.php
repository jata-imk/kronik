<?php

namespace App\Http\Requests\Clientes;

use App\Enums\ConsentimientoSicMedio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteConsentimientoSicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cliente')) ?? false;
    }

    public function rules(): array
    {
        return [
            'medio' => ['required', Rule::enum(ConsentimientoSicMedio::class)],
            'otorgado_en' => ['required', 'date', 'before_or_equal:now'],
            'vence_en' => ['nullable', 'date', 'after_or_equal:otorgado_en'],
            'evidencia' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

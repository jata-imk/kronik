<?php

namespace App\Http\Requests;

use App\Services\FechaEmpresa;
use Illuminate\Foundation\Http\FormRequest;

class ActivarProductoVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hoy = app(FechaEmpresa::class)->hoy()->toDateString();

        return [
            'vigente_desde' => ['required', 'date_format:Y-m-d', "after_or_equal:$hoy"],
        ];
    }

    public function attributes(): array
    {
        return ['vigente_desde' => 'fecha de vigencia'];
    }

    public function messages(): array
    {
        return [
            'vigente_desde.required' => 'Seleccione la fecha en que entrará en vigor la versión.',
            'vigente_desde.date_format' => 'La fecha de vigencia debe tener el formato AAAA-MM-DD.',
            'vigente_desde.after_or_equal' => 'La fecha de vigencia debe ser hoy o una fecha futura según la zona horaria de la empresa.',
        ];
    }
}

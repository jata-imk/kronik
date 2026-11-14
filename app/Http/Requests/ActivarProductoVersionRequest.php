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
            'vigente_desde' => ['required', 'date_format:Y-m-d', ...(config('originacion.desembolsos_qa_habilitados') === true ? [] : ["after_or_equal:$hoy"])],
            'confirmacion_historica' => [\Illuminate\Validation\Rule::when(is_string($this->input('vigente_desde')) && $this->input('vigente_desde') < $hoy && config('originacion.desembolsos_qa_habilitados') === true, ['required', 'accepted'], ['nullable', 'boolean'])],
        ];
    }

    public function attributes(): array
    {
        return ['vigente_desde' => 'fecha de vigencia'];
    }

    public function messages(): array
    {
        return [
            'confirmacion_historica.required' => 'Confirma la vigencia pasada exclusivamente para el caso sintético QA.',
            'confirmacion_historica.accepted' => 'Confirma la vigencia pasada exclusivamente para el caso sintético QA.',
            'vigente_desde.required' => 'Seleccione la fecha en que entrará en vigor la versión.',
            'vigente_desde.date_format' => 'La fecha de vigencia debe tener el formato AAAA-MM-DD.',
            'vigente_desde.after_or_equal' => 'La fecha de vigencia debe ser hoy o una fecha futura según la zona horaria de la empresa.',
        ];
    }
}

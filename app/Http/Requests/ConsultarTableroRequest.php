<?php

namespace App\Http\Requests;

use App\Services\FechaEmpresa;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsultarTableroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $hoy = app(FechaEmpresa::class)->hoy()->toDateString();
        $desde = $this->input('desde');
        $desdeComparacion = is_string($desde) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $desde)
            ? $desde : app(FechaEmpresa::class)->hoy()->startOfMonth()->toDateString();

        return [
            'corte' => ['bail', 'nullable', 'date_format:Y-m-d', 'before_or_equal:'.$hoy, function ($atributo, $valor, $fallo) use ($hoy) {
                if ($valor !== $hoy && ! CarbonImmutable::parse($valor, 'UTC')->isLastOfMonth()) {
                    $fallo('Selecciona hoy o el último día de un mes cerrado.');
                }
            }],
            'desde' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$hoy],
            'hasta' => ['bail', 'nullable', 'date_format:Y-m-d', 'before_or_equal:'.$hoy, 'after_or_equal:'.$desdeComparacion],
            'detalle' => ['nullable', Rule::in(['capital', 'vencido', 'atraso', 'cobros', 'desembolsos', 'proximos'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['corte' => 'fecha de corte', 'desde' => 'inicio del periodo', 'hasta' => 'fin del periodo', 'detalle' => 'indicador', 'page' => 'página'];
    }
}

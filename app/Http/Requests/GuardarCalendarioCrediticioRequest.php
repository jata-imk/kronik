<?php

namespace App\Http\Requests;

use App\Models\CalendarioCrediticioVersion;
use App\Services\Credito\CalendarioCrediticioService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class GuardarCalendarioCrediticioRequest extends FormRequest
{
    private CalendarioCrediticioVersion $versionCalendario;

    public function authorize(): bool
    {
        if (! $this->user()->can(CalendarioCrediticioService::CONSULTAR) || ! $this->user()->can(CalendarioCrediticioService::EDITAR)) {
            return false;
        }
        if (($id = $this->route('versionCalendario')) !== null) {
            $this->versionCalendario = CalendarioCrediticioVersion::findOrFail($id);
        }

        return true;
    }

    public function versionCalendario(): CalendarioCrediticioVersion
    {
        return $this->versionCalendario;
    }

    public function rules(): array
    {
        return [
            'revision' => ['sometimes', 'required', 'integer', 'min:1'],
            'configuracion' => ['required', 'array:desde,hasta,semana_habil,feriados,excepciones,fuente'],
            'configuracion.desde' => ['required', 'date_format:Y-m-d'],
            'configuracion.hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:configuracion.desde'],
            'configuracion.semana_habil' => ['required', 'array', 'min:1', 'max:7'],
            'configuracion.semana_habil.*' => ['integer', 'between:1,7', 'distinct'],
            'configuracion.fuente' => ['required', 'string', 'max:255'],
            'configuracion.feriados' => ['present', 'array', 'max:730'],
            'configuracion.feriados.*' => ['required', 'array:fecha,nombre'],
            'configuracion.excepciones' => ['present', 'array', 'max:730'],
            'configuracion.excepciones.*' => ['required', 'array:fecha,nombre,habil'],
            'configuracion.excepciones.*.habil' => ['required', 'boolean'],
            'configuracion.feriados.*.fecha' => ['required', 'date_format:Y-m-d'],
            'configuracion.excepciones.*.fecha' => ['required', 'date_format:Y-m-d'],
            'configuracion.feriados.*.nombre' => ['required', 'string', 'max:120'],
            'configuracion.excepciones.*.nombre' => ['required', 'string', 'max:120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'revision' => 'revisión del borrador',
            'configuracion' => 'configuración del calendario',
            'configuracion.desde' => 'inicio de cobertura',
            'configuracion.hasta' => 'fin de cobertura',
            'configuracion.semana_habil' => 'días hábiles de la semana',
            'configuracion.semana_habil.*' => 'día hábil de la semana',
            'configuracion.fuente' => 'fuente de las fechas',
            'configuracion.feriados' => 'feriados',
            'configuracion.feriados.*' => 'feriado',
            'configuracion.feriados.*.fecha' => 'fecha del feriado',
            'configuracion.feriados.*.nombre' => 'nombre del feriado',
            'configuracion.excepciones' => 'excepciones',
            'configuracion.excepciones.*' => 'excepción',
            'configuracion.excepciones.*.fecha' => 'fecha de la excepción',
            'configuracion.excepciones.*.nombre' => 'motivo de la excepción',
            'configuracion.excepciones.*.habil' => 'tipo de excepción',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $configuracion = $this->input('configuracion');
            foreach (['feriados', 'excepciones'] as $campo) {
                $filas = data_get($configuracion, $campo);
                if (! is_array($filas)) {
                    continue;
                }
                $fechas = [];
                foreach ($filas as $indice => $fila) {
                    $fecha = data_get($fila, 'fecha');
                    if (is_string($fecha) && ! $validator->errors()->has("configuracion.{$campo}.{$indice}.fecha")) {
                        $fechas[] = $fecha;
                    }
                }
                if (count($fechas) !== count(array_unique($fechas))) {
                    $validator->errors()->add("configuracion.{$campo}.fechas_repetidas", "Hay fechas repetidas en {$campo}. Usa una fecha distinta en cada fila.");
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            foreach (['feriados', 'excepciones'] as $campo) {
                foreach ($configuracion[$campo] as $indice => $dia) {
                    if ($dia['fecha'] < $configuracion['desde'] || $dia['fecha'] > $configuracion['hasta']) {
                        $validator->errors()->add("configuracion.{$campo}.{$indice}.fecha", 'La fecha debe estar dentro de la cobertura revisada.');
                    }
                }
            }
        }];
    }
}

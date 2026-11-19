<?php

namespace App\Http\Requests;

use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Enums\PoliticaInteresBullet;
use App\Services\Credito\CalendarioCrediticioService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PreviaCalendarioCrediticioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(CalendarioCrediticioService::CONSULTAR);
    }

    public function rules(): array
    {
        return [
            'inicio' => ['required', 'date_format:Y-m-d'],
            'periodicidad' => ['required', Rule::enum(PeriodicidadCredito::class)],
            'metodo' => ['required', Rule::enum(MetodoAmortizacion::class)],
            'politica_bullet' => ['exclude_unless:metodo,bullet', 'required', Rule::enum(PoliticaInteresBullet::class)],
            'plazo' => ['required', 'integer', 'between:1,12'],
            'gracia' => ['required', 'integer', 'between:0,30'],
        ];
    }
}

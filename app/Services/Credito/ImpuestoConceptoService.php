<?php

namespace App\Services\Credito;

use App\Support\Decimal;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Proyección aritmética; no determina causación, acreditamiento ni declaración fiscal. */
final class ImpuestoConceptoService
{
    public function calcular(?array $politica, string $importe, string $concepto): array
    {
        if (! $politica || ($politica['tratamiento'] ?? 'no_definido') === 'no_definido') {
            throw ValidationException::withMessages(['fiscalidad' => "Define el tratamiento fiscal de este concepto: {$concepto}. Edita el borrador o crea una nueva versión del producto."]);
        }
        $validator = Validator::make($politica, [
            'tratamiento' => ['required', 'in:gravado,exento,no_causa'],
            'tasa' => ['nullable', 'required_if:tratamiento,gravado', 'prohibited_unless:tratamiento,gravado', 'decimal:0,8', 'between:0,100'],
            'base' => ['nullable', 'required_if:tratamiento,gravado', 'prohibited_unless:tratamiento,gravado', 'in:importe_concepto'],
        ]);
        if ($validator->fails()) {
            throw ValidationException::withMessages(['fiscalidad' => "Revisa el tratamiento de {$concepto}. Indica la tasa y la base del impuesto para este concepto gravado; en los demás tratamientos deben quedar vacías."]);
        }
        $base = Decimal::round($importe);
        $gravado = $politica['tratamiento'] === 'gravado';

        return [
            'concepto' => $concepto,
            'tratamiento' => $politica['tratamiento'],
            'tasa' => $gravado ? (string) $politica['tasa'] : null,
            'base' => $gravado ? $politica['base'] : null,
            'importe_concepto' => $base,
            'impuesto' => $gravado ? Decimal::round(Decimal::div(Decimal::mul($base, (string) $politica['tasa']), '100')) : '0.00',
        ];
    }
}

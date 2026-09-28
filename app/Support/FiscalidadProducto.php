<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Configuration only: this does not authorize operations or calculate taxes. */
final class FiscalidadProducto
{
    public static function rules(array $version = []): array
    {
        $hasCommissionPolicy = collect($version['comisiones'] ?? [])->contains(
            fn ($item) => is_array($item) && ! empty($item['fiscalidad'])
        );
        $rules = [
            'version.fiscalidad' => ['nullable', Rule::requiredIf($hasCommissionPolicy), 'array:uso,referencia,ordinario,moratorio', 'min:1'],
            'version.fiscalidad.uso' => ['required_with:version.fiscalidad', 'in:prueba,institucional'],
            'version.fiscalidad.referencia' => ['nullable', 'required_if:version.fiscalidad.uso,institucional', 'string', 'max:1000'],
        ];
        foreach (['version.fiscalidad.ordinario', 'version.fiscalidad.moratorio', 'version.comisiones.*.fiscalidad'] as $path) {
            $rules[$path] = str_contains($path, 'comisiones')
                ? ['nullable', 'array:tratamiento,tasa,base', 'min:1']
                : ['required_with:version.fiscalidad', 'array:tratamiento,tasa,base', 'min:1'];
            $rules["$path.tratamiento"] = ["required_with:$path", 'in:no_definido,gravado,exento,no_causa'];
            $rules["$path.tasa"] = ['nullable', "required_if:$path.tratamiento,gravado", "prohibited_unless:$path.tratamiento,gravado", 'decimal:0,8', 'between:0,100'];
            $rules["$path.base"] = ['nullable', "required_if:$path.tratamiento,gravado", "prohibited_unless:$path.tratamiento,gravado", 'in:importe_concepto'];
        }

        return $rules;
    }

    public static function validate(array $version): void
    {
        Validator::make(['version' => $version], self::rules($version))->validate();
    }
}

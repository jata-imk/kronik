<?php

namespace App\Services\Credito;

use App\Enums\ConvencionInteres;
use App\Enums\VersionMotorCredito;
use App\Models\ProductoVersion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class CapacidadesProducto
{
    public static function condiciones(ProductoVersion $version): array
    {
        if ($version->snapshot !== null) {
            return $version->snapshot;
        }
        $version->loadMissing(['reglas', 'periodicidades']);

        return ['motor_calculo_version' => VersionMotorCredito::actual()->value,
            'reglas' => array_filter($version->reglas?->toArray() ?? [], fn ($valor, $clave) => ! in_array($clave, ['base_ordinaria', 'politica_interes_bullet'], true) || $valor !== null, ARRAY_FILTER_USE_BOTH), 'periodicidades' => $version->periodicidades->toArray()];
    }

    public static function reglas(string $prefix = 'version.', ?VersionMotorCredito $motor = null, bool $configuracion = false): array
    {
        $motor ??= VersionMotorCredito::actual();

        return [
            $prefix.'periodicidades' => ['required', 'array', 'min:1'],
            $prefix.'periodicidades.*.periodicidad' => ['required', Rule::in(CatalogoCapacidadesCredito::valores('periodicidades', $motor, $configuracion)), 'distinct'],
            $prefix.'reglas.metodos_amortizacion' => ['required', 'array', 'min:1'],
            $prefix.'reglas.metodos_amortizacion.*' => ['required', Rule::in(CatalogoCapacidadesCredito::valores('metodos_amortizacion', $motor, $configuracion)), 'distinct'],
            $prefix.'reglas.convencion_interes' => ['sometimes', 'required', 'string', Rule::in(CatalogoCapacidadesCredito::valores('convenciones_interes', $motor, $configuracion))],
            $prefix.'reglas.base_ordinaria' => ['sometimes', 'required', Rule::in(['saldo_insoluto'])],
            $prefix.'reglas.base_moratoria' => ['sometimes', 'required', Rule::in(CatalogoCapacidadesCredito::valores('bases_moratorias', $motor, $configuracion))],
            $prefix.'reglas.politica_interes_bullet' => ['sometimes', 'required', Rule::in(CatalogoCapacidadesCredito::valores('politicas_interes_bullet', $motor, $configuracion))],
            $prefix.'reglas.aplicacion_prepago' => ['required', Rule::in(CatalogoCapacidadesCredito::valores('aplicaciones_prepago', $motor, $configuracion))],
        ];
    }

    public static function validar(array $condiciones, VersionMotorCredito $motor, string $prefix = '', bool $configuracion = false): void
    {
        // Mantener el error histórico de convención en su clave original.
        $motor->validarConvencion(ConvencionInteres::desdeProducto($condiciones));
        Validator::make($prefix === '' ? $condiciones : ['version' => $condiciones], self::reglas($prefix, $motor, $configuracion), [], [
            $prefix.'reglas.base_ordinaria' => 'base ordinaria',
            $prefix.'reglas.base_moratoria' => 'base moratoria',
            $prefix.'reglas.politica_interes_bullet' => 'política de intereses Bullet',
            $prefix.'periodicidades.*.periodicidad' => 'periodicidad',
            $prefix.'reglas.metodos_amortizacion.*' => 'método de amortización',
            $prefix.'reglas.metodos_amortizacion' => 'métodos de amortización',
            $prefix.'reglas.aplicacion_prepago' => 'aplicación del prepago',
        ])->after(fn ($validator) => self::validarCombinacion($validator, $condiciones, $prefix))->validate();
    }

    public static function validarCombinacion(\Illuminate\Validation\Validator $validator, array $condiciones, string $prefix = ''): void
    {
        $reglas = $condiciones['reglas'] ?? [];
        if (! is_array($reglas)) {
            return;
        }
        $metodos = $reglas['metodos_amortizacion'] ?? [];
        if (! is_array($metodos) || array_filter($metodos, fn ($metodo) => ! is_string($metodo))) {
            return;
        }
        $bullet = in_array('bullet', $metodos, true);
        if ($bullet && ($reglas['base_ordinaria'] ?? null) !== 'saldo_insoluto') {
            $validator->errors()->add($prefix.'reglas.base_ordinaria', 'Selecciona saldo insoluto como base ordinaria Bullet.');
        }
        $combinacion = CatalogoCapacidadesCredito::combinaciones()[$bullet ? 'bullet' : 'actuales'];
        if (array_diff($metodos, $combinacion['metodos'])) {
            $validator->errors()->add($prefix.'reglas.metodos_amortizacion', 'Configura Bullet en una versión separada de los otros métodos.');
        }
        $base = $reglas['base_moratoria'] ?? ($bullet ? null : 'capital_vencido');
        if (! in_array($base, $combinacion['bases_moratorias'], true)) {
            $validator->errors()->add($prefix.'reglas.base_moratoria', 'Selecciona una base moratoria compatible con el método.');
        }
        if (! in_array($reglas['aplicacion_prepago'] ?? null, $combinacion['aplicaciones_prepago'], true)) {
            $validator->errors()->add($prefix.'reglas.aplicacion_prepago', 'Selecciona una aplicación del prepago compatible con el método.');
        }
        if ($bullet && ! in_array($reglas['politica_interes_bullet'] ?? null, ['periodicos', 'al_vencimiento'], true)) {
            $validator->errors()->add($prefix.'reglas.politica_interes_bullet', 'Selecciona cuándo son exigibles los intereses Bullet.');
        } elseif (! $bullet && array_key_exists('politica_interes_bullet', $reglas)) {
            $validator->errors()->add($prefix.'reglas.politica_interes_bullet', 'La política de intereses Bullet solo aplica al método Bullet.');
        }
    }

    public static function paraVersion(ProductoVersion $version): array
    {
        return self::desdeCondiciones(self::condiciones($version));
    }

    public static function desdeCondiciones(array $condiciones): array
    {
        $opciones = array_fill_keys(array_keys(CatalogoCapacidadesCredito::catalogo()), []);
        try {
            $motor = VersionMotorCredito::desdeSnapshot($condiciones);
            self::validar($condiciones, $motor);
        } catch (ValidationException $error) {
            return ['motor_calculo_version' => $condiciones['motor_calculo_version'] ?? null, 'opciones' => $opciones, 'problemas' => array_merge(...array_values($error->errors()))];
        }
        $compatibles = CatalogoCapacidadesCredito::paraMotor($motor);
        $reglas = $condiciones['reglas'];
        $periodos = array_column($compatibles['periodicidades'], null, 'value');
        foreach ($condiciones['periodicidades'] as $periodo) {
            $opciones['periodicidades'][] = [...$periodo, ...$periodos[$periodo['periodicidad']]];
        }
        $metodos = array_column($compatibles['metodos_amortizacion'], null, 'value');
        $opciones['metodos_amortizacion'] = array_map(fn ($codigo) => $metodos[$codigo], $reglas['metodos_amortizacion']);
        $opciones['convenciones_interes'] = array_values(array_filter($compatibles['convenciones_interes'], fn ($o) => $o['value'] === ConvencionInteres::desdeProducto($condiciones)->value));
        $opciones['aplicaciones_prepago'] = ($reglas['permite_prepago_parcial'] ?? false) ? array_values(array_filter($compatibles['aplicaciones_prepago'], fn ($o) => $o['value'] === $reglas['aplicacion_prepago'])) : [];

        $opciones['bases_ordinarias'] = array_values(array_filter($compatibles['bases_ordinarias'], fn ($o) => $o['value'] === ($reglas['base_ordinaria'] ?? 'saldo_insoluto')));
        $opciones['bases_moratorias'] = array_values(array_filter($compatibles['bases_moratorias'], fn ($o) => $o['value'] === ($reglas['base_moratoria'] ?? 'capital_vencido')));
        $opciones['politicas_interes_bullet'] = array_values(array_filter($compatibles['politicas_interes_bullet'], fn ($o) => $o['value'] === ($reglas['politica_interes_bullet'] ?? null)));

        return ['motor_calculo_version' => $motor->value, 'opciones' => $opciones, 'problemas' => []];
    }
}

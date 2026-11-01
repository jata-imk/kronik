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
            'reglas' => $version->reglas?->toArray() ?? [], 'periodicidades' => $version->periodicidades->toArray()];
    }

    public static function reglas(string $prefix = 'version.', ?VersionMotorCredito $motor = null): array
    {
        $motor ??= VersionMotorCredito::actual();

        return [
            $prefix.'periodicidades' => ['required', 'array', 'min:1'],
            $prefix.'periodicidades.*.periodicidad' => ['required', Rule::in(CatalogoCapacidadesCredito::valores('periodicidades', $motor)), 'distinct'],
            $prefix.'reglas.metodos_amortizacion' => ['required', 'array', 'min:1'],
            $prefix.'reglas.metodos_amortizacion.*' => ['required', Rule::in(CatalogoCapacidadesCredito::valores('metodos_amortizacion', $motor)), 'distinct'],
            $prefix.'reglas.convencion_interes' => ['sometimes', 'required', 'string', Rule::in(CatalogoCapacidadesCredito::valores('convenciones_interes', $motor))],
            $prefix.'reglas.aplicacion_prepago' => ['required', Rule::in(CatalogoCapacidadesCredito::valores('aplicaciones_prepago', $motor))],
        ];
    }

    public static function validar(array $condiciones, VersionMotorCredito $motor, string $prefix = ''): void
    {
        // Mantener el error histórico de convención en su clave original.
        $motor->validarConvencion(ConvencionInteres::desdeProducto($condiciones));
        Validator::make($prefix === '' ? $condiciones : ['version' => $condiciones], self::reglas($prefix, $motor), [], [
            $prefix.'periodicidades.*.periodicidad' => 'periodicidad',
            $prefix.'reglas.metodos_amortizacion.*' => 'método de amortización',
            $prefix.'reglas.metodos_amortizacion' => 'métodos de amortización',
            $prefix.'reglas.aplicacion_prepago' => 'aplicación del prepago',
        ])->validate();
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

        return ['motor_calculo_version' => $motor->value, 'opciones' => $opciones, 'problemas' => []];
    }
}

<?php

namespace App\Enums;

use Illuminate\Validation\ValidationException;

/** Solo contiene convenciones habilitadas; no es el catálogo de propuestas futuras. */
enum ConvencionInteres: string
{
    case DiasReales360 = 'dias_reales_360';

    public static function desdeReglas(array $reglas): self
    {
        return self::desdeEvidencia($reglas);
    }

    public static function desdeProducto(array $producto): self
    {
        return self::desdeReglas($producto['reglas'] ?? []);
    }

    public static function desdeCronograma(array $cronograma): self
    {
        return self::desdeEvidencia($cronograma);
    }

    private static function desdeEvidencia(array $evidencia): self
    {
        // Antes de explicitar la marca, el único algoritmo existente era Act/360.
        $valor = array_key_exists('convencion_interes', $evidencia)
            ? $evidencia['convencion_interes'] : self::DiasReales360->value;
        if (! is_string($valor) || ! $convencion = self::tryFrom($valor)) {
            throw ValidationException::withMessages(['convencion_interes' => 'La convención de días de este registro no está soportada. Solicita revisión sin modificar sus condiciones históricas.']);
        }

        return $convencion;
    }
}

<?php

namespace App\Services\Credito\Amortizacion;

use App\Enums\ConvencionInteres;
use App\Enums\PoliticaInteresBullet;
use App\Services\Credito\InteresPorPeriodo;
use App\Support\Decimal;

/** Proyección pura: conserva los intervalos sin pago para el CAT. */
final class ExigibilidadInteresBullet
{
    public function __construct(private readonly InteresPorPeriodo $intereses) {}

    public function aplicar(ResultadoAmortizacion $resultado, PoliticaInteresBullet $politica, ConvencionInteres $convencion): ResultadoAmortizacion
    {
        if ($resultado->tabla === [] || count($resultado->devengos) !== count($resultado->tabla)) {
            throw new AmortizacionInvalida('Faltan los segmentos de devengo Bullet para determinar su exigibilidad.');
        }
        $tabla = $resultado->tabla;
        $numerador = '0';
        foreach ($tabla as $index => &$fila) {
            $numerador = Decimal::add($numerador, $resultado->devengos[$index]['numerador']);
            $fila['interes'] = $politica === PoliticaInteresBullet::Periodicos
                ? Decimal::round($this->intereses->desdeNumerador($resultado->devengos[$index]['numerador'], $convencion))
                : ($index === array_key_last($tabla) ? Decimal::round($this->intereses->desdeNumerador($numerador, $convencion)) : '0.00');
            $fila['pago_total'] = Decimal::add($fila['capital'], $fila['interes'], 2);
            $fila['pago_cat'] = $fila['pago_total'];
        }
        unset($fila);

        return new ResultadoAmortizacion($tabla, $resultado->base, $resultado->devengos);
    }
}

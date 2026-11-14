<?php

namespace App\Services\Credito;

use App\Support\Decimal as D;
use Carbon\CarbonImmutable;

/** Traduce resultados del motor y calendario conservado; no calcula intereses. */
final class ProyectorLecturaCredito
{
    public const VERSION = 1;

    public function __construct(private MotorPagos $motor, private ImpuestoConceptoService $impuestos) {}

    public function calcular(array $estado, array $cronograma, string $corte): array
    {
        $estado = $this->motor->avanzar($estado, $corte);
        $resumen = $this->motor->resumen($estado);
        $filas = [];
        foreach ($resumen['conceptos'] as $concepto) {
            $this->agregar($filas, $concepto['fecha'], 'exigible', $concepto['tipo'], $concepto['importe'], $concepto['impuesto']);
        }
        if ($resumen['estado_operativo'] !== 'liquidado') {
            foreach ($cronograma['tabla'] as $fila) {
                if ($fila['fecha'] <= $corte || $fila['numero'] < 1) {
                    continue;
                }
                $cuota = $estado['cuotas'][$fila['numero']] ?? null;
                if (! $cuota) {
                    continue;
                }
                $this->agregar($filas, $fila['fecha'], 'programada', 'capital', $cuota['capital'], '0.00');
                // El cronograma regenerado Bullet ya contiene interés neto de pagos previos.
                $pagado = ($estado['metodo'] ?? null) === 'bullet' && ($estado['cronograma_version'] ?? 1) > 1
                    ? '0.00' : ($estado['cargos'][$fila['numero'].':ordinario']['pagado'] ?? '0.00');
                $interes = D::sub((string) ($fila['interes'] ?? '0.00'), $pagado, 2);
                $interes = D::compare($interes, '0') > 0 ? $interes : '0.00';
                $impuesto = $this->impuestos->calcular($estado['producto']['fiscalidad']['ordinario'], $interes, 'ordinario')['impuesto'];
                $this->agregar($filas, $fila['fecha'], 'programada', 'ordinario', $interes, $impuesto);
            }
            foreach ($estado['cargos'] as $cargo) {
                if ($cargo['tipo'] !== 'comision' || ! ($cargo['vigente'] ?? true) || $cargo['fecha'] <= $corte) {
                    continue;
                }
                $importe = D::sub(D::round($cargo['generado']), $cargo['pagado'], 2);
                $impuesto = $this->impuestos->calcular($cargo['fiscalidad'], D::round($cargo['generado']), $cargo['concepto'])['impuesto'];
                $this->agregar($filas, $cargo['fecha'], 'programada', 'comision', $importe, D::sub($impuesto, $cargo['impuesto_pagado'], 2));
            }
        }
        $vencido = '0.00';
        $dias = 0;
        foreach ($filas as $fila) {
            if ($fila['naturaleza'] === 'exigible' && $fila['fecha'] < $corte && D::compare($fila['total'], '0') > 0) {
                $vencido = D::add($vencido, $fila['total'], 2);
                $dias = max($dias, (int) CarbonImmutable::parse($fila['fecha'], 'UTC')->diffInDays(CarbonImmutable::parse($corte, 'UTC')));
            }
        }

        return ['estado_motor' => $estado, 'estado' => $resumen['estado_operativo'], 'capital' => $resumen['capital_insoluto'],
            'exigible' => $resumen['exigible'], 'vencido' => $vencido, 'dias_atraso' => $dias, 'obligaciones' => array_values($filas)];
    }

    private function agregar(array &$filas, string $fecha, string $naturaleza, string $tipo, string $importe, string $impuesto): void
    {
        $clave = $fecha.'|'.$naturaleza;
        $filas[$clave] ??= ['fecha' => $fecha, 'naturaleza' => $naturaleza, 'capital' => '0.00', 'ordinario' => '0.00',
            'moratorio' => '0.00', 'comisiones' => '0.00', 'impuestos' => '0.00', 'total' => '0.00'];
        $campo = $tipo === 'comision' ? 'comisiones' : $tipo;
        $filas[$clave][$campo] = D::add($filas[$clave][$campo], $importe, 2);
        $filas[$clave]['impuestos'] = D::add($filas[$clave]['impuestos'], $impuesto, 2);
        $filas[$clave]['total'] = D::add($filas[$clave]['total'], D::add($importe, $impuesto, 2), 2);
    }
}

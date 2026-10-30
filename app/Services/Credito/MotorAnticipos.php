<?php

namespace App\Services\Credito;

use App\Enums\MetodoAmortizacion;
use App\Enums\VersionMotorCredito;
use App\Support\Decimal as D;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/** Pure calculator: accrued debt first, event charges second, future capital last. */
final class MotorAnticipos
{
    public function __construct(private MotorPagos $pagos, private ImpuestoConceptoService $impuestos, private TablaAmortizacionCreditoSimple $amortizacion, private InteresAct360 $intereses) {}

    public function calcular(array $antes, array $tabla, string $tipo, ?string $importe, array $opcionales = []): array
    {
        $reglas = $antes['producto']['reglas'];
        if (($antes['estado_operativo'] ?? null) === 'liquidado' || D::compare($this->pagos->resumen($antes)['capital_insoluto'], '0') <= 0) {
            $this->error('El crédito ya no tiene capital pendiente. Revisa su cierre y recibos.');
        }
        $ultimaFecha = max(array_column($antes['cuotas'], 'fecha'));
        $evento = $tipo === 'prepago' ? 'prepago' : ($antes['fecha'] < $ultimaFecha ? 'liquidacion_anticipada' : 'liquidacion_ordinaria');
        $elegibles = [];
        foreach ($antes['producto']['comisiones'] ?? [] as $i => $c) {
            if (! $c['obligatoria'] && ($c['evento_cobro'] ?? null) === $evento) {
                $elegibles[] = (string) $i;
            }
        }
        if (array_diff(array_map('strval', $opcionales), $elegibles)) {
            $this->error('Selecciona únicamente comisiones opcionales aplicables a este evento contractual.');
        }
        if ($tipo === 'prepago' && ! $reglas['permite_prepago_parcial']) {
            $this->error('El contrato no permite anticipos parciales.');
        }
        if ($evento === 'liquidacion_anticipada' && ! $reglas['permite_liquidacion_anticipada']) {
            $this->error('El contrato no permite liquidación anticipada.');
        }
        $estado = $antes;
        // Current-period accrued interest is owed even if its installment is not due.
        foreach ($estado['cargos'] as &$cargo) {
            if (in_array($cargo['tipo'], ['ordinario', 'moratorio'], true) && ($cargo['vigente'] ?? true)) {
                $cargo['fecha'] = min($cargo['fecha'], $estado['fecha']);
            }
        }
        unset($cargo);
        $obligatorio = $this->pagos->resumen($estado)['exigible'];
        $asignaciones = [];
        if (D::compare($obligatorio, '0') > 0) {
            $aplicado = $this->pagos->aplicar($estado, $obligatorio);
            $estado = $aplicado['estado'];
            $asignaciones = $aplicado['asignaciones'];
        }
        $futuro = $this->pagos->resumen($estado)['capital_insoluto'];
        $insoluto = $this->pagos->resumen($antes)['capital_insoluto'];
        $montoOriginal = $tabla['escenario']['monto_solicitado'];
        $coste = function (string $capital) use ($antes, $evento, $opcionales, $montoOriginal, $insoluto, $obligatorio): string {
            $fees = $this->comisiones($antes['producto'], $evento, $capital, $montoOriginal, $insoluto, $opcionales);

            return D::add(D::add($obligatorio, $capital, 2), $this->totalComisiones($fees), 2);
        };
        if ($tipo === 'liquidacion') {
            $capital = $futuro;
            $total = $coste($capital);
            if ($importe !== null && D::compare($importe, $total) !== 0) {
                $this->error('La liquidación debe coincidir con la cotización: '.$total.' MXN. No se recorta ni registra saldo a favor.');
            }
        } else {
            if ($importe === null || D::compare($importe, $obligatorio) <= 0 || D::compare($futuro, '0') <= 0) {
                $this->error('El importe debe cubrir vencidos y devengos y dejar capital para anticipar.');
            }
            $low = '0';
            $high = D::mul($futuro, '100', 0);
            while (D::compare($low, $high, 0) < 0) {
                $mid = D::div(D::add(D::add($low, $high, 0), '1', 0), '2', 0);
                if (D::compare($coste(D::div($mid, '100', 2)), $importe) <= 0) {
                    $low = $mid;
                } else {
                    $high = D::sub($mid, '1', 0);
                }
            }
            $capital = D::div($low, '100', 2);
            $total = $coste($capital);
            if (D::compare($total, $importe) !== 0) {
                $this->error('El importe no admite distribución exacta. Totales próximos: '.$total.' y '.$coste(D::add($capital, '0.01', 2)).' MXN.');
            }
            if (D::compare($capital, $futuro) >= 0) {
                $this->error('Este importe liquida el capital. Selecciona Liquidación para revisar todos sus cargos.');
            }
            if (D::compare($capital, '0') <= 0 || D::compare($capital, (string) ($reglas['monto_minimo_prepago'] ?? '0')) < 0) {
                $this->error('El capital anticipado debe ser positivo y cumplir el mínimo contractual.');
            }
        }
        $fees = $this->comisiones($antes['producto'], $evento, $capital, $montoOriginal, $insoluto, $opcionales);
        $version = (int) ($antes['cronograma_version'] ?? 1) + 1;
        foreach ($fees as $i => $fee) {
            $estado['cargos']['evento:'.$version.':'.$i] = ['cuota' => 0, 'fecha' => $estado['fecha'], 'tipo' => 'comision',
                'concepto' => $fee['concepto'], 'generado' => $fee['importe'], 'fiscalidad' => $fee['fiscalidad'],
                'pagado' => '0.00', 'impuesto_pagado' => '0.00', 'contabilizado' => '0.00', 'impuesto_contabilizado' => '0.00'];
        }
        foreach ($estado['cargos'] as &$cargo) {
            if ($cargo['tipo'] === 'capital' && ($cargo['vigente'] ?? true)) {
                $cargo['fecha'] = $estado['fecha'];
            }
        }
        unset($cargo);
        $resto = D::sub($total, $obligatorio, 2);
        if (D::compare($resto, '0') > 0) {
            $aplicado = $this->pagos->aplicar($estado, $resto);
            $estado = $aplicado['estado'];
            $asignaciones = [...$asignaciones, ...$aplicado['asignaciones']];
        }
        foreach ($estado['cargos'] as &$cargo) {
            if (($cargo['vigente'] ?? true) && ($cargo['fecha'] <= $estado['fecha'] || in_array($cargo['tipo'], ['ordinario', 'moratorio'], true))) {
                $cargo['contabilizado'] = D::round($cargo['generado']);
                $cargo['impuesto_contabilizado'] = $cargo['fiscalidad'] === null ? '0.00' : $this->impuestos->calcular($cargo['fiscalidad'], $cargo['contabilizado'], $cargo['concepto'])['impuesto'];
            }
            $cargo['vigente'] = false; // Preserved history; future debt belongs to the new schedule.
        }
        unset($cargo);
        $saldo = $this->pagos->resumen($estado)['capital_insoluto'];
        $nuevo = $this->recalcular($antes, $tabla, $saldo, $reglas['aplicacion_prepago']);
        $offset = max(array_keys($antes['cuotas']));
        foreach ($nuevo['tabla'] as &$fila) {
            $fila['numero'] += $offset;
        }
        unset($fila);
        $nuevo['escenario'] = [...$tabla['escenario'], 'fecha_disposicion' => $antes['fecha'], 'saldo_financiado' => $saldo];
        if (D::compare($saldo, '0') > 0) {
            $iniciado = $this->pagos->iniciar($antes['producto'], $nuevo);
            $estado['cuotas'] = $iniciado['cuotas'];
            $estado['cargos'] = [...$estado['cargos'], ...$iniciado['cargos']];
        } else {
            $estado['cuotas'] = [];
        }
        $estado['formato'] = 2;
        $estado['cronograma_version'] = $version;
        $estado['estado_operativo'] = D::compare($saldo, '0') === 0 ? 'liquidado' : 'activo';
        $sum = array_reduce($asignaciones, fn ($s, $a) => D::add($s, $a['total'], 2), '0.00');
        if (D::compare($sum, $total) !== 0 || D::compare($saldo, D::sub($insoluto, array_reduce($asignaciones, fn ($s, $a) => $a['tipo'] === 'capital' ? D::add($s, $a['importe'], 2) : $s, '0.00'), 2)) !== 0) {
            $this->error('La distribución no conserva los importes. No se registró la operación.');
        }

        $comparacion = [['etiqueta' => $antes['fecha'], 'antes' => $insoluto, 'despues' => $saldo]];
        $fechas = array_values(array_unique([...array_column($antes['cuotas'], 'fecha'), ...array_column($nuevo['tabla'], 'fecha')]));
        sort($fechas);
        foreach ($fechas as $fecha) {
            if ($fecha <= $antes['fecha']) {
                continue;
            }
            $comparacion[] = ['etiqueta' => $fecha,
                'antes' => array_reduce($antes['cuotas'], fn ($s, $c) => $c['fecha'] > $fecha ? D::add($s, $c['capital'], 2) : $s, '0.00'),
                'despues' => array_reduce($nuevo['tabla'], fn ($s, $c) => $c['fecha'] > $fecha ? D::add($s, $c['capital'], 2) : $s, '0.00')];
        }

        $proyeccion = ['aplicacion' => $reglas['aplicacion_prepago'], 'cuotas' => count($nuevo['tabla']),
            'ultimo_vencimiento' => $nuevo['tabla'] ? end($nuevo['tabla'])['fecha'] : null,
            'intereses' => array_reduce($nuevo['tabla'], fn ($s, $f) => D::add($s, $f['interes'], 2), '0.00'),
            'comisiones' => array_reduce($nuevo['tabla'], fn ($s, $f) => D::add($s, $f['comisiones'], 2), '0.00'),
            'impuestos' => array_reduce($nuevo['tabla'], fn ($s, $f) => D::add($s, $f['impuestos'], 2), '0.00')];

        return ['proyeccion' => $proyeccion, 'comparacion' => $comparacion, 'importe' => $total, 'capital_anticipado' => $capital, 'asignaciones' => $asignaciones,
            'antes' => $this->pagos->resumen($antes), 'despues' => $this->pagos->resumen($estado),
            'cronograma' => $nuevo, 'estado' => $estado, 'evento' => $evento];
    }

    private function comisiones(array $producto, string $evento, string $capital, string $original, string $insoluto, array $opcionales): array
    {
        $fees = [];
        foreach ($producto['comisiones'] ?? [] as $i => $c) {
            if (! in_array($c['momento_cobro'], ['evento', 'liquidacion'], true)) {
                continue;
            }
            if (empty($c['evento_cobro']) && D::compare((string) $c['importe'], '0') > 0) {
                $this->error('El contrato conserva una comisión de evento sin aplicación explícita. No se puede reinterpretar su versión histórica.');
            }
            if (($c['evento_cobro'] ?? null) !== $evento || (! $c['obligatoria'] && ! in_array((string) $i, array_map('strval', $opcionales), true))) {
                continue;
            }
            $base = match ($c['base_calculo']) {
                'capital_anticipado' => $capital, 'capital_insoluto' => $insoluto, 'monto_credito' => $original, 'no_aplica' => '0',
                default => $this->error('La base de comisión del contrato no está soportada.'),
            };
            $amount = D::round($c['tipo_importe'] === 'porcentaje' ? D::div(D::mul($base, (string) $c['importe']), '100') : (string) $c['importe']);
            $tax = $this->impuestos->calcular($c['fiscalidad'] ?? null, $amount, $c['concepto']['nombre']);
            $fees[] = ['concepto' => $c['concepto']['nombre'], 'importe' => $amount, 'impuesto' => $tax['impuesto'], 'fiscalidad' => $c['fiscalidad']];
        }

        return $fees;
    }

    private function totalComisiones(array $fees): string
    {
        return array_reduce($fees, fn ($s, $f) => D::add($s, D::add($f['importe'], $f['impuesto'], 2), 2), '0.00');
    }

    private function recalcular(array $state, array $tabla, string $saldo, string $modo): array
    {
        $fechas = array_values(array_filter(array_column($state['cuotas'], 'fecha'), fn ($f) => $f > $state['fecha']));
        $metodo = MetodoAmortizacion::from($tabla['metodo']);
        $motor = VersionMotorCredito::desdeSnapshot($state['producto']);
        $base = $tabla['cuota_base'] ?? null;
        if ($base === null) {
            $primera = collect($tabla['tabla'])->first(fn ($f) => $f['numero'] > 0);
            $base = $metodo === MetodoAmortizacion::CapitalFijo ? $primera['capital'] : D::add($primera['capital'], $primera['interes'], 2);
        }
        $filas = [];
        if (D::compare($saldo, '0') > 0) {
            if (! $fechas) {
                $this->error('No quedan vencimientos futuros para un anticipo parcial. Revisa pago o liquidación.');
            }
            $inicio = CarbonImmutable::parse($state['fecha'], 'UTC');
            if ($modo === 'reducir_pago') {
                $calculo = $this->amortizacion->calcular($saldo, $state['producto']['tasa_ordinaria_anual'], $inicio,
                    array_map(fn ($f) => CarbonImmutable::parse($f, 'UTC'), $fechas), $metodo, $motor);
                $filas = $calculo['tabla'];
                $base = $calculo['cuota_redondeada'] ?? ($filas[0]['capital'] ?? '0.00');
            } else {
                foreach ($fechas as $fecha) {
                    $fin = CarbonImmutable::parse($fecha, 'UTC');
                    $dias = (int) $inicio->diffInDays($fin);
                    $interes = D::round($motor === VersionMotorCredito::Historico
                        ? D::div(D::mul(D::mul($saldo, $state['producto']['tasa_ordinaria_anual']), (string) $dias), '36000')
                        : $this->intereses->calcular($saldo, $state['producto']['tasa_ordinaria_anual'], $dias));
                    $capital = $metodo === MetodoAmortizacion::CapitalFijo ? $base : D::sub($base, $interes, 2);
                    if (D::compare($capital, '0') <= 0) {
                        $this->error('La cuota contractual no amortiza el saldo recalculado. Solicita revisión.');
                    }
                    if (D::compare($capital, $saldo) > 0) {
                        $capital = $saldo;
                    }
                    $saldoInicial = $saldo;
                    $saldo = D::sub($saldo, $capital, 2);
                    $filas[] = ['numero' => count($filas) + 1, 'fecha' => $fecha, 'dias' => $dias, 'capital' => $capital,
                        'interes' => $interes, 'saldo_inicial' => $saldoInicial, 'saldo_final' => $saldo, 'comisiones' => '0.00', 'pago_total' => D::add($capital, $interes, 2)];
                    $inicio = $fin;
                    if (D::compare($saldo, '0') === 0) {
                        break;
                    }
                }
                if (D::compare($saldo, '0') > 0) {
                    $this->error('El calendario contractual no alcanza a amortizar el saldo. No se extendió el plazo.');
                }
            }
        }
        foreach ($filas as &$f) {
            $comisiones = [];
            foreach ($state['cargos'] as $c) {
                if ($c['tipo'] === 'comision' && $c['fecha'] === $f['fecha'] && ($c['vigente'] ?? true)) {
                    $comisiones[] = ['concepto' => $c['concepto'], 'importe' => D::round($c['generado']), 'fiscalidad' => $c['fiscalidad'], 'modalidad' => null];
                }
            }
            $f['comisiones_detalle'] = $comisiones;
            $f['comisiones'] = array_reduce($comisiones, fn ($s, $c) => D::add($s, $c['importe'], 2), '0.00');
            $f['impuestos'] = $this->impuestos->calcular($state['producto']['fiscalidad']['ordinario'], $f['interes'], 'ordinario')['impuesto'];
            foreach ($comisiones as $c) {
                $f['impuestos'] = D::add($f['impuestos'], $this->impuestos->calcular($c['fiscalidad'], $c['importe'], $c['concepto'])['impuesto'], 2);
            }
            $f['pago_total'] = D::add(D::add(D::add($f['capital'], $f['interes'], 2), $f['comisiones'], 2), $f['impuestos'], 2);
        }
        unset($f);

        return ['formato' => 2, 'motor_calculo_version' => $motor->value, 'metodo' => $metodo->value, 'cuota_base' => $base, 'tabla' => $filas];
    }

    private function error(string $message): never
    {
        throw ValidationException::withMessages(['pago' => $message]);
    }
}

<?php

namespace App\Services\Credito;

use App\Support\Decimal as D;
use App\Support\PoliticaMora;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/** Pure QA ledger calculator. Never uses projected interest as accrued debt. */
final class MotorPagos
{
    public function __construct(private ImpuestoConceptoService $impuestos, private RepartoProporcional $reparto) {}

    public function iniciar(array $producto, array $tabla): array
    {
        if (empty($producto['politica_mora'])) {
            $this->error('Este crédito no conserva una política de atraso explícita. No se pueden asignar reglas nuevas al contrato histórico.');
        }
        PoliticaMora::validate($producto);
        foreach (['ordinario', 'moratorio'] as $concepto) {
            $this->impuestos->calcular($producto['fiscalidad'][$concepto] ?? null, '0', $concepto);
        }
        $state = ['formato' => 1, 'fecha' => $tabla['escenario']['fecha_disposicion'], 'producto' => $producto, 'cuotas' => [], 'cargos' => []];
        foreach ($tabla['tabla'] as $fila) {
            $numero = $fila['numero'];
            if ($numero > 0) {
                $state['cuotas'][$numero] = ['numero' => $numero, 'fecha' => $fila['fecha'], 'capital' => $fila['capital'],
                    'gracia_ordinario' => '0', 'gracia_moratorio' => '0', 'mora_activada' => false];
                foreach (['moratorio', 'ordinario'] as $concepto) {
                    $state['cargos'][$numero.':'.$concepto] = $this->cargo($numero, $fila['fecha'], $concepto, $concepto === 'ordinario' ? 'Interés ordinario' : 'Interés moratorio', '0', $producto['fiscalidad'][$concepto]);
                }
                $state['cargos'][$numero.':capital'] = $this->cargo($numero, $fila['fecha'], 'capital', 'Capital', $fila['capital'], null);
            }
            foreach ($fila['comisiones_detalle'] ?? [] as $index => $comision) {
                if ($numero === 0 && $comision['modalidad'] !== 'pago_separado') {
                    continue; // Already withheld or included in financed principal, not a second charge.
                }
                $state['cargos'][$numero.':comision:'.$index] = $this->cargo($numero, $fila['fecha'], 'comision', $comision['concepto'], $comision['importe'],
                    array_intersect_key($comision['fiscalidad'], array_flip(['tratamiento', 'tasa', 'base'])));
            }
        }

        return $state;
    }

    public function avanzar(array $state, string $fecha): array
    {
        if ($fecha < $state['fecha']) {
            $this->error('No puedes registrar este pago con esa fecha porque el crédito tiene movimientos posteriores.');
        }
        $producto = $state['producto'];
        $policy = $producto['politica_mora'];
        $diasGracia = (int) $producto['dias_gracia_mora'];
        $fin = CarbonImmutable::parse($fecha, 'UTC')->startOfDay();
        for ($dia = CarbonImmutable::parse($state['fecha'], 'UTC')->addDay()->startOfDay(); $dia->lte($fin); $dia = $dia->addDay()) {
            $fechaDia = $dia->toDateString();
            $periodo = null;
            foreach ($state['cuotas'] as $cuota) {
                if ($cuota['fecha'] >= $fechaDia) {
                    $periodo = $cuota['numero'];
                    break;
                }
            }
            foreach ($state['cuotas'] as &$cuota) {
                if (D::compare($cuota['capital'], '0') <= 0) {
                    continue;
                }
                $numero = $cuota['numero'];
                $vencida = $fechaDia > $cuota['fecha'];
                $fueraGracia = $fechaDia > CarbonImmutable::parse($cuota['fecha'], 'UTC')->addDays($diasGracia)->toDateString();
                $ordinario = D::div(D::mul($cuota['capital'], (string) $producto['tasa_ordinaria_anual']), '36000');
                $moratorio = D::div(D::mul($cuota['capital'], (string) $producto['tasa_moratoria_anual']), '36000');
                if ($vencida && ! $fueraGracia) {
                    $cuota['gracia_ordinario'] = D::add($cuota['gracia_ordinario'], $ordinario);
                    $cuota['gracia_moratorio'] = D::add($cuota['gracia_moratorio'], $moratorio);
                }
                if ($fueraGracia && ! $cuota['mora_activada']) {
                    if ($policy['gracia'] === 'retroactiva') {
                        $state['cargos'][$numero.':moratorio']['generado'] = D::add($state['cargos'][$numero.':moratorio']['generado'], $cuota['gracia_moratorio']);
                        if ($policy['intereses'] === 'sustituye') {
                            $state['cargos'][$numero.':ordinario']['generado'] = D::sub($state['cargos'][$numero.':ordinario']['generado'], $cuota['gracia_ordinario']);
                        }
                    }
                    $cuota['mora_activada'] = true;
                }
                if ($fueraGracia) {
                    $state['cargos'][$numero.':moratorio']['generado'] = D::add($state['cargos'][$numero.':moratorio']['generado'], $moratorio);
                }
                if (! $fueraGracia || $policy['intereses'] === 'ambos') {
                    $key = ($vencida ? $numero : $periodo).':ordinario';
                    $state['cargos'][$key]['generado'] = D::add($state['cargos'][$key]['generado'], $ordinario);
                }
            }
            unset($cuota);
        }
        $state['fecha'] = $fecha;

        return $state;
    }

    public function resumen(array $state): array
    {
        $filas = [];
        $total = '0.00';
        $capital = '0.00';
        $vencido = '0.00';
        $diasAtraso = 0;
        foreach ($state['cuotas'] as $cuota) {
            $capital = D::add($capital, $cuota['capital'], 2);
            if ($cuota['fecha'] < $state['fecha'] && D::compare($cuota['capital'], '0') > 0) {
                $vencido = D::add($vencido, $cuota['capital'], 2);
                $diasAtraso = max($diasAtraso, (int) CarbonImmutable::parse($cuota['fecha'], 'UTC')->diffInDays(CarbonImmutable::parse($state['fecha'], 'UTC')));
            }
        }
        foreach ($state['cargos'] as $key => $cargo) {
            [$base, $impuesto] = $this->pendiente($cargo);
            if ($cargo['fecha'] <= $state['fecha'] && D::compare(D::add($base, $impuesto), '0') > 0) {
                $filas[] = ['clave' => $key, 'cuota' => $cargo['cuota'], 'fecha' => $cargo['fecha'], 'tipo' => $cargo['tipo'], 'concepto' => $cargo['concepto'],
                    'importe' => $base, 'impuesto' => $impuesto, 'total' => D::add($base, $impuesto, 2)];
                $total = D::add($total, D::add($base, $impuesto), 2);
            }
        }
        $orden = ['comision' => 0, 'moratorio' => 1, 'ordinario' => 2, 'capital' => 3];
        usort($filas, fn ($a, $b) => [$a['cuota'], $orden[$a['tipo']], $a['clave']] <=> [$b['cuota'], $orden[$b['tipo']], $b['clave']]);

        return ['fecha' => $state['fecha'], 'capital_insoluto' => $capital, 'capital_vencido' => $vencido, 'dias_atraso' => $diasAtraso,
            'exigible' => $total, 'conceptos' => $filas];
    }

    public function aplicar(array $state, string $importe): array
    {
        if (! preg_match('/^\d{1,14}(\.\d{1,2})?$/D', $importe) || D::compare($importe, '0') <= 0) {
            $this->error('Indica un pago mayor a cero, con hasta dos decimales.');
        }
        $resumen = $this->resumen($state);
        if (D::compare($importe, $resumen['exigible']) > 0) {
            $this->error('El importe excede las obligaciones exigibles. No se registra un pago parcial del importe recibido ni un anticipo automático; gestiona el excedente fuera del sistema.');
        }
        $restante = $importe;
        $asignaciones = [];
        $cuotasAfectadas = [];
        foreach ($resumen['conceptos'] as $fila) {
            if (D::compare($restante, '0') <= 0) {
                break;
            }
            $abono = D::compare($restante, $fila['total']) > 0 ? $fila['total'] : $restante;
            $r = $this->reparto->repartir($abono, $fila['importe'], $fila['impuesto']);
            $cargo = &$state['cargos'][$fila['clave']];
            $cargo['pagado'] = D::add($cargo['pagado'], $r['concepto'], 2);
            $cargo['impuesto_pagado'] = D::add($cargo['impuesto_pagado'], $r['impuesto'], 2);
            if ($fila['tipo'] === 'capital') {
                $state['cuotas'][$fila['cuota']]['capital'] = D::sub($state['cuotas'][$fila['cuota']]['capital'], $r['concepto'], 2);
            }
            unset($cargo);
            $asignaciones[] = [...$fila, 'importe' => $r['concepto'], 'impuesto' => $r['impuesto'], 'total' => D::round($abono)];
            $cuotasAfectadas[$fila['cuota']] = true;
            $restante = D::sub($restante, $abono, 2);
        }

        // Validate the proposed result before returning any posting instructions.
        // Older obligations may be paid without touching a newer grace-period quota.
        if ($state['producto']['politica_mora']['gracia'] === 'retroactiva'
            && $state['producto']['politica_mora']['intereses'] === 'sustituye') {
            foreach ($state['cuotas'] as $cuota) {
                $finGracia = CarbonImmutable::parse($cuota['fecha'], 'UTC')->addDays((int) $state['producto']['dias_gracia_mora'])->toDateString();
                if (isset($cuotasAfectadas[$cuota['numero']]) && $state['fecha'] > $cuota['fecha']
                    && $state['fecha'] <= $finGracia && D::compare($cuota['capital'], '0') > 0) {
                    $this->error('Este producto combina gracia B con moratorio que sustituye al ordinario. Durante la gracia solo se admite cubrir por completo la cuota afectada, incluidos sus intereses, comisiones e impuestos. No se registró el pago parcial. Si ya recibiste dinero, solicita revisión al responsable; no cambies su fecha ni importe para eludir el bloqueo. La compensación trazable está pendiente de implementación.');
                }
            }
        }

        return ['estado' => $state, 'asignaciones' => $asignaciones, 'resumen' => $this->resumen($state)];
    }

    private function pendiente(array $cargo): array
    {
        $generado = D::round($cargo['generado']);
        $impuesto = $cargo['fiscalidad'] === null ? '0.00' : $this->impuestos->calcular($cargo['fiscalidad'], $generado, $cargo['concepto'])['impuesto'];
        $base = D::sub($generado, $cargo['pagado'], 2);
        $tax = D::sub($impuesto, $cargo['impuesto_pagado'], 2);
        if (D::compare($base, '0') < 0 || D::compare($tax, '0') < 0) {
            $this->error('La sustitución retroactiva requiere compensar importes ya pagados. No se modifica la historia automáticamente.');
        }

        return [$base, $tax];
    }

    private function cargo(int $cuota, string $fecha, string $tipo, string $concepto, string $generado, ?array $fiscalidad): array
    {
        return compact('cuota', 'fecha', 'tipo', 'concepto', 'generado', 'fiscalidad') + ['pagado' => '0.00', 'impuesto_pagado' => '0.00',
            'contabilizado' => $tipo === 'capital' ? D::round($generado) : '0.00', 'impuesto_contabilizado' => '0.00'];
    }

    private function error(string $message): never
    {
        throw ValidationException::withMessages(['pago' => $message]);
    }
}

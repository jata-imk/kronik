<?php

namespace App\Services\Credito;

use App\Enums\ConvencionInteres;
use App\Enums\PoliticaInteresBullet;
use App\Enums\VersionMotorCredito;
use App\Support\Decimal as D;
use App\Support\PoliticaMora;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/** Pure QA ledger calculator. Never uses projected interest as accrued debt. */
final class MotorPagos
{
    public function __construct(private ImpuestoConceptoService $impuestos, private RepartoProporcional $reparto, private InteresPorPeriodo $intereses) {}

    public function iniciar(array $producto, array $tabla): array
    {
        VersionMotorCredito::desdeSnapshot($producto)->validarConvencion(ConvencionInteres::desdeProducto($producto));
        if (ConvencionInteres::desdeProducto($producto) !== ConvencionInteres::desdeCronograma($tabla)) {
            $this->error('El producto y el calendario conservan convenciones de días diferentes. Solicita revisión de la evidencia.');
        }
        if (VersionMotorCredito::desdeSnapshot($producto) !== VersionMotorCredito::desdeSnapshot($tabla)) {
            $this->error('El producto y el calendario conservan versiones de cálculo diferentes. Solicita revisión de la evidencia.');
        }
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

        if (($tabla['metodo'] ?? null) === 'bullet' || in_array('bullet', $producto['reglas']['metodos_amortizacion'] ?? [], true)) {
            $this->iniciarBullet($state, $tabla);
        }

        return $state;
    }

    public function avanzar(array $state, string $fecha): array
    {
        $convencion = ConvencionInteres::desdeProducto($state['producto']);
        $motor = VersionMotorCredito::desdeSnapshot($state['producto']);
        $motor->validarConvencion($convencion);
        $bullet = $this->esBullet($state);
        if ($fecha < $state['fecha']) {
            $this->error('No puedes registrar este pago con esa fecha porque el crédito tiene movimientos posteriores.');
        }
        if (($state['estado_operativo'] ?? null) === 'liquidado') {
            $state['fecha'] = $fecha;

            return $state;
        }
        $producto = $state['producto'];
        $racional = $motor === VersionMotorCredito::Act360Racional;
        $policy = $producto['politica_mora'];
        $diasGracia = (int) $producto['dias_gracia_mora'];
        $fin = CarbonImmutable::parse($fecha, 'UTC')->startOfDay();
        for ($dia = CarbonImmutable::parse($state['fecha'], 'UTC')->addDay()->startOfDay(); $dia->lte($fin); $dia = $dia->addDay()) {
            if ($bullet) {
                $this->avanzarDiaBullet($state, $dia, $convencion);

                continue;
            }
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
                $ordinario = $racional ? $this->intereses->numerador($cuota['capital'], (string) $producto['tasa_ordinaria_anual'], $dia->subDay(), $dia, $convencion)
                    : D::div(D::mul($cuota['capital'], (string) $producto['tasa_ordinaria_anual']), '36000');
                $moratorio = $racional ? $this->intereses->numerador($cuota['capital'], (string) $producto['tasa_moratoria_anual'], $dia->subDay(), $dia, $convencion)
                    : D::div(D::mul($cuota['capital'], (string) $producto['tasa_moratoria_anual']), '36000');
                if ($vencida && ! $fueraGracia) {
                    $this->acumular($cuota, 'gracia_ordinario', $ordinario, $racional, $convencion);
                    $this->acumular($cuota, 'gracia_moratorio', $moratorio, $racional, $convencion);
                }
                if ($fueraGracia && ! $cuota['mora_activada']) {
                    if ($policy['gracia'] === 'retroactiva') {
                        $this->acumular($state['cargos'][$numero.':moratorio'], 'generado', $racional ? ($cuota['gracia_moratorio_numerador'] ?? '0') : $cuota['gracia_moratorio'], $racional, $convencion);
                        if ($policy['intereses'] === 'sustituye') {
                            $retroactivo = $racional ? ($cuota['gracia_ordinario_numerador'] ?? '0') : $cuota['gracia_ordinario'];
                            $this->acumular($state['cargos'][$numero.':ordinario'], 'generado', D::sub('0', $retroactivo), $racional, $convencion);
                        }
                    }
                    $cuota['mora_activada'] = true;
                }
                if ($fueraGracia) {
                    $this->acumular($state['cargos'][$numero.':moratorio'], 'generado', $moratorio, $racional, $convencion);
                }
                if (! $fueraGracia || $policy['intereses'] === 'ambos') {
                    $key = ($vencida ? $numero : $periodo).':ordinario';
                    $this->acumular($state['cargos'][$key], 'generado', $ordinario, $racional, $convencion);
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
        $atrasoBullet = [];
        if ($this->esBullet($state)) {
            $obligaciones = $this->obligacionesBullet($state, $state['fecha']);
            $interes = '0.00';
            $pendiente = '0.00';
            foreach ($obligaciones as $numero => $obligacion) {
                $interes = D::add($interes, $this->pendiente($state['cargos'][$numero.':ordinario'])[0], 2);
                $pendiente = D::add($pendiente, $obligacion['base'], 2);
                $diasAtraso = max($diasAtraso, (int) CarbonImmutable::parse($state['cuotas'][$numero]['fecha'], 'UTC')->diffInDays(CarbonImmutable::parse($state['fecha'], 'UTC')));
            }
            $atrasoBullet = ['interes_vencido' => $interes, 'obligacion_vencida' => $pendiente];
        }
        foreach ($state['cargos'] as $key => $cargo) {
            if (($cargo['vigente'] ?? true) === false) {
                continue;
            }
            [$base, $impuesto] = $this->pendiente($cargo);
            if ($cargo['fecha'] <= $state['fecha'] && D::compare(D::add($base, $impuesto), '0') > 0) {
                $filas[] = ['clave' => $key, 'cuota' => $cargo['cuota'], 'fecha' => $cargo['fecha'], 'tipo' => $cargo['tipo'], 'concepto' => $cargo['concepto'],
                    'importe' => $base, 'impuesto' => $impuesto, 'total' => D::add($base, $impuesto, 2)];
                $total = D::add($total, D::add($base, $impuesto), 2);
            }
        }
        $orden = ['comision' => 0, 'moratorio' => 1, 'ordinario' => 2, 'capital' => 3];
        usort($filas, fn ($a, $b) => [$a['cuota'], $orden[$a['tipo']], $a['clave']] <=> [$b['cuota'], $orden[$b['tipo']], $b['clave']]);

        return ['fecha' => $state['fecha'], 'cronograma_version' => $state['cronograma_version'] ?? 1, 'estado_operativo' => $state['estado_operativo'] ?? (D::compare($capital, '0') === 0 && D::compare($total, '0') === 0 ? 'liquidado' : 'activo'), 'capital_insoluto' => $capital, 'capital_vencido' => $vencido, 'dias_atraso' => $diasAtraso,
            'exigible' => $total, 'conceptos' => $filas, ...$atrasoBullet];
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
            if ($this->esBullet($state)) {
                $pendientes = $this->obligacionesBullet($state, $state['fecha']);
                foreach ($pendientes as $numero => $obligacion) {
                    if (! $obligacion['fuera_gracia'] && isset($cuotasAfectadas[$numero])) {
                        $this->error('Durante la gracia retroactiva con sustitución debes cubrir por completo la obligación Bullet afectada. No se registró el pago parcial.');
                    }
                }
                foreach ($state['gracia_bullet'] as $registro) {
                    if ($registro['ordinario'] !== null && isset($cuotasAfectadas[$registro['ordinario']['cuota']])
                        && array_intersect_key($pendientes, $registro['obligaciones']) !== []) {
                        $this->error('El interés de gracia está pendiente de una posible sustitución retroactiva. Cubre primero la obligación Bullet que origina el atraso. No se registró el pago.');
                    }
                }

                return ['estado' => $state, 'asignaciones' => $asignaciones, 'resumen' => $this->resumen($state)];
            }
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

    private function iniciarBullet(array &$state, array $tabla): void
    {
        $reglas = $state['producto']['reglas'] ?? [];
        $this->validarCondicionesBullet($state['producto']);
        if (($tabla['metodo'] ?? null) !== 'bullet'
            || count($state['cuotas']) !== count(array_filter($tabla['tabla'], fn ($fila) => $fila['numero'] > 0))) {
            $this->error('Las condiciones y el calendario Bullet deben conservar el método y números de cuota únicos. Solicita revisión de la evidencia.');
        }
        $ultima = array_key_last($state['cuotas']);
        $anterior = $state['fecha'];
        if ($ultima === null || D::compare($state['cuotas'][$ultima]['capital'], '0') <= 0) {
            $this->error('El calendario Bullet debe conservar capital positivo al vencimiento final.');
        }
        foreach ($state['cuotas'] as $numero => $cuota) {
            if ($cuota['fecha'] <= $anterior || ($numero !== $ultima && D::compare($cuota['capital'], '0') !== 0)) {
                $this->error('El calendario Bullet requiere fechas crecientes y capital únicamente al vencimiento final.');
            }
            $anterior = $cuota['fecha'];
            if ($reglas['politica_interes_bullet'] === 'al_vencimiento') {
                $state['cargos'][$numero.':ordinario']['fecha'] = $state['cuotas'][$ultima]['fecha'];
            }
        }
        $state['metodo'] = 'bullet';
        $state['capital_inicial'] = $state['cuotas'][$ultima]['capital'];
        $state['gracia_bullet'] = [];
    }

    private function esBullet(array $state): bool
    {
        $bullet = ($state['metodo'] ?? null) === 'bullet';
        if (in_array('bullet', $state['producto']['reglas']['metodos_amortizacion'] ?? [], true)
            && (! $bullet || ! isset($state['capital_inicial'], $state['gracia_bullet'])
                || VersionMotorCredito::desdeSnapshot($state['producto']) !== VersionMotorCredito::Act360Racional)) {
            $this->error('La evidencia Bullet no conserva su método, capital inicial o control de gracia. Solicita revisión sin reconstruirlos desde saldos actuales.');
        }
        if ($bullet) {
            $this->validarCondicionesBullet($state['producto']);
            foreach ($state['cargos'] as $cargo) {
                if (($cargo['vigente'] ?? true) && in_array($cargo['tipo'], ['ordinario', 'moratorio'], true)
                    && ! isset($cargo['generado_numerador']) && D::compare($cargo['generado'], '0') !== 0) {
                    $this->error('La evidencia del devengo no conserva su acumulador de cálculo. Solicita revisión sin reconstruirlo desde importes redondeados.');
                }
            }
        }

        return $bullet;
    }

    private function validarCondicionesBullet(array $producto): void
    {
        $reglas = $producto['reglas'] ?? [];
        if (($reglas['metodos_amortizacion'] ?? []) !== ['bullet']
            || VersionMotorCredito::desdeSnapshot($producto) !== VersionMotorCredito::Act360Racional
            || ($reglas['base_ordinaria'] ?? null) !== 'saldo_insoluto'
            || ! in_array($reglas['base_moratoria'] ?? null, ['saldo_insoluto', 'capital_inicial', 'obligacion_vencida'], true)
            || ! in_array($reglas['politica_interes_bullet'] ?? null, array_column(PoliticaInteresBullet::cases(), 'value'), true)
            || ($reglas['aplicacion_prepago'] ?? null) !== 'conservar_fecha') {
            $this->error('Las condiciones Bullet deben conservar motor 2, política de intereses y bases compatibles explícitas. Solicita revisión de la evidencia.');
        }
    }

    /** Obligaciones de capital e interés ordinario; impuestos, comisiones y mora no integran la base. */
    private function obligacionesBullet(array $state, string $fecha): array
    {
        $obligaciones = [];
        foreach ($state['cuotas'] as $numero => $cuota) {
            if ($cuota['fecha'] >= $fecha) {
                continue;
            }
            $base = '0.00';
            $capital = '0.00';
            foreach (['capital', 'ordinario'] as $tipo) {
                $cargo = $state['cargos'][$numero.':'.$tipo];
                if (($cargo['vigente'] ?? true) === false || $cargo['fecha'] >= $fecha) {
                    continue;
                }
                [$importe] = $this->pendiente($cargo);
                if ($tipo === 'capital') {
                    $capital = $importe;
                }
                $base = D::add($base, $importe, 2);
            }
            if (D::compare($base, '0') > 0) {
                $ordinario = $state['cargos'][$numero.':ordinario'];
                $obligaciones[$numero] = ['base' => $base, 'capital' => $capital,
                    'ordinario_numerador' => $ordinario['generado_numerador'] ?? '0', 'ordinario_pagado' => $ordinario['pagado'],
                    'fuera_gracia' => $fecha > CarbonImmutable::parse($cuota['fecha'], 'UTC')->addDays((int) $state['producto']['dias_gracia_mora'])->toDateString()];
            }
        }

        return $obligaciones;
    }

    private function avanzarDiaBullet(array &$state, CarbonImmutable $dia, ConvencionInteres $convencion): void
    {
        $producto = $state['producto'];
        $policy = $producto['politica_mora'];
        $individual = $producto['reglas']['base_moratoria'] === 'obligacion_vencida';
        $pendientes = $this->obligacionesBullet($state, $dia->toDateString());
        $activas = array_filter($pendientes, fn ($o) => $o['fuera_gracia']);
        $capital = array_reduce($state['cuotas'], fn ($s, $c) => D::add($s, $c['capital'], 2), '0.00');
        $baseGlobal = $producto['reglas']['base_moratoria'] === 'capital_inicial' ? $state['capital_inicial'] : $capital;
        $numerador = fn ($base, $tasa) => $this->intereses->numerador($base, (string) $tasa, $dia->subDay(), $dia, $convencion);

        // Sustituir primero: un interés provisional puede haber alcanzado otro vencimiento durante la gracia.
        foreach (array_keys($state['gracia_bullet']) as $fecha) {
            $registro = $state['gracia_bullet'][$fecha];
            if ($policy['intereses'] !== 'sustituye' || $registro['ordinario'] === null
                || array_intersect_key($registro['obligaciones'], $activas) === []) {
                continue;
            }
            $ordinario = $registro['ordinario'];
            $this->acumular($state['cargos'][$ordinario['cuota'].':ordinario'], 'generado', D::sub('0', $ordinario['numerador']), true, $convencion);
            $state['gracia_bullet'][$fecha]['ordinario'] = null;
            if ($individual) {
                foreach ($state['gracia_bullet'] as $fechaBase => &$baseDiferida) {
                    if ($fechaBase > $fecha && isset($baseDiferida['obligaciones'][$ordinario['cuota']])) {
                        $evidencia = &$baseDiferida['obligaciones'][$ordinario['cuota']];
                        $evidencia['ordinario_numerador'] = D::sub($evidencia['ordinario_numerador'], $ordinario['numerador']);
                        unset($evidencia);
                    }
                }
                unset($baseDiferida);
            }
        }
        $pendientes = $this->obligacionesBullet($state, $dia->toDateString());
        $activas = array_filter($pendientes, fn ($o) => $o['fuera_gracia']);
        $enGracia = array_diff_key($pendientes, $activas);

        // Cada día de gracia conserva la base de ese día; los pagos posteriores no la reemplazan.
        foreach ($state['gracia_bullet'] as $fecha => &$registro) {
            $registro['obligaciones'] = array_intersect_key($registro['obligaciones'], $pendientes);
            $elegibles = array_intersect_key($registro['obligaciones'], $activas);
            if ($elegibles !== []) {
                if ($individual) {
                    foreach ($elegibles as $numero => $evidencia) {
                        $base = D::add($evidencia['capital'], D::sub(D::round($this->intereses->desdeNumerador($evidencia['ordinario_numerador'], $convencion)), $evidencia['ordinario_pagado'], 2), 2);
                        $this->acumular($state['cargos'][$numero.':moratorio'], 'generado', $numerador($base, $producto['tasa_moratoria_anual']), true, $convencion);
                    }
                } else {
                    $this->acumular($state['cargos'][array_key_first($elegibles).':moratorio'], 'generado', $registro['moratorio_global'], true, $convencion);
                    $registro['obligaciones'] = [];
                }
                $registro['obligaciones'] = array_diff_key($registro['obligaciones'], $elegibles);
            }
            if ($registro['obligaciones'] === []) {
                unset($state['gracia_bullet'][$fecha]);
            }
        }
        unset($registro);

        if ($activas !== []) {
            if ($individual) {
                foreach ($activas as $numero => $obligacion) {
                    $this->acumular($state['cargos'][$numero.':moratorio'], 'generado', $numerador($obligacion['base'], $producto['tasa_moratoria_anual']), true, $convencion);
                }
            } else {
                $this->acumular($state['cargos'][array_key_first($activas).':moratorio'], 'generado', $numerador($baseGlobal, $producto['tasa_moratoria_anual']), true, $convencion);
            }
        }

        $ordinario = null;
        if ($activas === [] || $policy['intereses'] === 'ambos') {
            $numero = array_key_last($state['cuotas']);
            if ($producto['reglas']['politica_interes_bullet'] === 'periodicos') {
                foreach ($state['cuotas'] as $cuota) {
                    if ($cuota['fecha'] >= $dia->toDateString()) {
                        $numero = $cuota['numero'];
                        break;
                    }
                }
            }
            $incremento = $numerador($capital, $producto['tasa_ordinaria_anual']);
            $this->acumular($state['cargos'][$numero.':ordinario'], 'generado', $incremento, true, $convencion);
            $ordinario = ['cuota' => $numero, 'numerador' => $incremento];
        }
        if ($policy['gracia'] === 'retroactiva' && $enGracia !== [] && ($individual || $activas === [])) {
            $state['gracia_bullet'][$dia->toDateString()] = [
                'obligaciones' => $enGracia,
                'moratorio_global' => $individual ? '0' : $numerador($baseGlobal, $producto['tasa_moratoria_anual']),
                'ordinario' => $ordinario];
        }
    }

    private function acumular(array &$registro, string $campo, string $incremento, bool $racional, ConvencionInteres $convencion): void
    {
        if (! $racional) {
            $registro[$campo] = D::add($registro[$campo], $incremento);

            return;
        }
        $clave = $campo.'_numerador';
        if (! isset($registro[$clave]) && D::compare($registro[$campo], '0') !== 0) {
            $this->error('La evidencia del devengo no conserva su acumulador de cálculo. Solicita revisión sin reconstruirlo desde importes redondeados.');
        }
        $registro[$clave] = D::add($registro[$clave] ?? '0', $incremento);
        $registro[$campo] = $this->intereses->desdeNumerador($registro[$clave], $convencion);
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

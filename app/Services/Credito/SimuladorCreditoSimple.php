<?php

namespace App\Services\Credito;

use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Models\ProductoVersion;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class SimuladorCreditoSimple
{
    public function __construct(
        private readonly CatInformativoService $catService,
        private readonly CalendarioCreditoSimple $calendario,
        private readonly TablaAmortizacionCreditoSimple $amortizacion,
        private readonly ResolverComisionesCreditoSimple $resolverComisiones,
    ) {}

    /** @return array<string, mixed> */
    public function simular(ProductoVersion $version, string $monto, PeriodicidadCredito $periodicidad, int $plazo, MetodoAmortizacion $metodo, CarbonImmutable $fecha, array $comisionesOpcionales = [], bool $incluirFormula = false): array
    {
        $version->loadMissing(['periodicidades', 'reglas', 'comisiones.concepto']);
        $resolucion = $this->resolverComisiones->resolver($version, $comisionesOpcionales);
        $comisiones = $resolucion['aplicadas'];
        $iniciales = $comisiones->filter->esInicial();
        $financiadas = $this->totalPorModalidad($iniciales, $monto, 'financiada');
        $saldoFinanciado = Decimal::round(Decimal::add($monto, $financiadas));

        $this->validar($version, $saldoFinanciado, $periodicidad, $plazo, $metodo);

        $fechas = $this->calendario->fechas($fecha, $periodicidad, $plazo);
        $resultado = $this->amortizacion->calcular($saldoFinanciado, (string) $version->tasa_ordinaria_anual, $fecha, $fechas, $metodo);
        $tabla = $resultado['tabla'];
        $cadaPago = $comisiones->where('momento_cobro', 'cada_pago');
        $acumulados = ['capital' => '0.00', 'interes' => '0.00', 'comisiones' => '0.00', 'pagado' => '0.00'];

        $pagoSeparado = $this->totalPorModalidad($iniciales, $monto, 'pago_separado');
        $retenidas = $this->totalPorModalidad($iniciales, $monto, 'descuento_desembolso');
        $comisionesIniciales = Decimal::round(Decimal::add(Decimal::add($pagoSeparado, $retenidas), $financiadas));
        $flujoNetoInicial = Decimal::round(Decimal::sub(Decimal::sub($monto, $pagoSeparado), $retenidas));
        $efectivoEntregado = Decimal::round(Decimal::sub($monto, $retenidas));
        $pagoInicial = $pagoSeparado;
        $acumulados['comisiones'] = $comisionesIniciales;
        $acumulados['pagado'] = $pagoSeparado;

        $filaCero = [
            'numero' => 0,
            'tipo' => 'disposicion',
            'fecha' => $fecha->toDateString(),
            'dias' => 0,
            'saldo_inicial' => '0.00',
            'disposicion' => $saldoFinanciado,
            'capital' => '0.00',
            'interes' => '0.00',
            'comisiones' => $comisionesIniciales,
            'comisiones_detalle' => $this->detalle($iniciales, $monto),
            'pago_total' => $pagoInicial,
            'saldo_final' => $saldoFinanciado,
            'saldo' => $saldoFinanciado,
            'efectivo_entregado' => $efectivoEntregado,
            'flujo_neto_cliente' => $flujoNetoInicial,
            ...$this->camposAcumulados($acumulados),
        ];

        foreach ($tabla as &$fila) {
            $detalle = $this->detalle($cadaPago, $monto);
            $totalComisiones = array_reduce($detalle, fn (string $total, array $item) => Decimal::add($total, $item['importe']), '0');
            $totalCat = array_reduce($detalle, fn (string $total, array $item) => $item['incluye_cat'] ? Decimal::add($total, $item['importe']) : $total, '0');
            $fila['comisiones'] = Decimal::round($totalComisiones);
            $fila['comisiones_detalle'] = $detalle;
            $fila['pago_total'] = Decimal::round(Decimal::add($fila['pago_total'], $totalComisiones));
            $fila['pago_cat'] = Decimal::round(Decimal::add($fila['pago_cat'], $totalCat));

            foreach (['capital', 'interes', 'comisiones'] as $campo) {
                $acumulados[$campo] = Decimal::round(Decimal::add($acumulados[$campo], $fila[$campo]));
            }
            $acumulados['pagado'] = Decimal::round(Decimal::add($acumulados['pagado'], $fila['pago_total']));
            $fila += $this->camposAcumulados($acumulados);
        }
        unset($fila);

        [$montoRecibidoCat, $pagosCat] = $this->flujosCatBase($version, $monto, $periodicidad, $plazo, $metodo, $fecha, $resolucion['obligatorias']);
        $cat = $version->cat_aplica
            ? $this->catService->calcular($montoRecibidoCat, $pagosCat, $periodicidad)
            : null;

        $totalIntereses = $this->sumar($tabla, 'interes');
        $totalPagosPeriodicos = $this->sumar($tabla, 'pago_total');
        $totalPagar = Decimal::round(Decimal::add($totalPagosPeriodicos, $pagoInicial));
        $totalComisiones = Decimal::round(Decimal::add($comisionesIniciales, $this->sumar($tabla, 'comisiones')));

        $respuesta = [
            'monto' => Decimal::round($monto),
            'monto_recibido_cat' => $montoRecibidoCat,
            'comisiones_iniciales' => $comisionesIniciales,
            'periodicidad' => $periodicidad->value,
            'plazo' => $plazo,
            'metodo' => $metodo->value,
            'cat' => $cat,
            'cat_contexto' => 'base_obligatorio',
            'cat_leyenda' => $cat === null ? $version->cat_no_aplica_motivo : 'CAT base informativo del producto. Incluye únicamente cargos obligatorios del curso normal, sin IVA.',
            'total_intereses' => $totalIntereses,
            'total_comisiones' => $totalComisiones,
            'total_pagar' => $totalPagar,
            'escenario' => [
                'fecha_disposicion' => $fecha->toDateString(),
                'monto_solicitado' => Decimal::round($monto),
                'saldo_financiado' => $saldoFinanciado,
                'efectivo_entregado' => $efectivoEntregado,
                'flujo_neto_inicial' => $flujoNetoInicial,
            ],
            'totales' => [
                'capital_financiado' => $saldoFinanciado,
                'intereses' => $totalIntereses,
                'comisiones' => $totalComisiones,
                'pagos_periodicos' => $totalPagosPeriodicos,
                'pago_separado_inicial' => $pagoSeparado,
                'retenido_desembolso' => $retenidas,
                'financiado_comisiones' => $financiadas,
                'obligaciones' => $totalPagar,
            ],
            'comisiones_opcionales_seleccionadas' => $resolucion['opcionalesSeleccionadas']->map(fn ($item) => [
                'id' => $item->id,
                'concepto' => $item->concepto?->nombre,
                'momento' => $item->esInicial() ? 'inicio' : $item->momento_cobro,
                'importe' => Decimal::round($item->calcular($monto)),
            ])->values()->all(),
            'comisiones_excluidas' => $resolucion['excluidas'],
            'tabla' => [$filaCero, ...array_map(fn (array $fila) => collect($fila)->except('pago_cat')->all(), $tabla)],
        ];

        if ($incluirFormula) {
            $respuesta['formula_debug'] = $this->formula($version, $saldoFinanciado, $resultado, $tabla);
        }

        return $respuesta;
    }

    private function validar(ProductoVersion $version, string $saldoFinanciado, PeriodicidadCredito $periodicidad, int $plazo, MetodoAmortizacion $metodo): void
    {
        $config = $version->periodicidades->firstWhere('periodicidad', $periodicidad->value);
        $errores = [];
        if (Decimal::compare($saldoFinanciado, (string) $version->monto_minimo) < 0 || Decimal::compare($saldoFinanciado, (string) $version->monto_maximo) > 0) {
            $errores['monto'] = 'El saldo total financiado, incluidas las comisiones financiadas, debe estar dentro del rango del producto.';
        }
        if (! $config || $plazo < $config->plazo_minimo || $plazo > $config->plazo_maximo) {
            $errores['plazo'] = 'El plazo no está permitido para la periodicidad seleccionada.';
        }
        if (! in_array($metodo->value, $version->reglas?->metodos_amortizacion ?? [], true)) {
            $errores['metodo'] = 'El método de amortización no está habilitado para esta versión.';
        }
        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    private function totalPorModalidad(Collection $comisiones, string $monto, string $modalidad): string
    {
        return Decimal::round($comisiones->reduce(
            fn (string $total, $comision) => $comision->modalidadInicial() === $modalidad ? Decimal::add($total, $comision->calcular($monto)) : $total,
            '0',
        ));
    }

    private function detalle(Collection $comisiones, string $monto): array
    {
        return $comisiones->map(fn ($comision) => [
            'id' => $comision->id,
            'concepto' => $comision->concepto?->nombre ?? 'Comisión',
            'clave' => $comision->concepto?->clave,
            'importe' => Decimal::round($comision->calcular($monto)),
            'momento' => $comision->esInicial() ? 'inicio' : $comision->momento_cobro,
            'modalidad' => $comision->modalidadInicial(),
            'incluye_cat' => (bool) $comision->incluye_cat,
            'obligatoria' => (bool) $comision->obligatoria,
        ])->values()->all();
    }

    /** @return array{0: string, 1: array<int, string>} */
    private function flujosCatBase(ProductoVersion $version, string $monto, PeriodicidadCredito $periodicidad, int $plazo, MetodoAmortizacion $metodo, CarbonImmutable $fecha, Collection $comisiones): array
    {
        $iniciales = $comisiones->filter->esInicial();
        $financiadas = $this->totalPorModalidad($iniciales, $monto, 'financiada');
        $saldo = Decimal::round(Decimal::add($monto, $financiadas));
        $fechas = $this->calendario->fechas($fecha, $periodicidad, $plazo);
        $resultado = $this->amortizacion->calcular($saldo, (string) $version->tasa_ordinaria_anual, $fecha, $fechas, $metodo);
        $cadaPago = $comisiones->where('momento_cobro', 'cada_pago');
        $pagos = array_map(function (array $fila) use ($cadaPago, $monto): string {
            $comisionesCat = $cadaPago->reduce(
                fn (string $total, $comision) => $comision->incluye_cat ? Decimal::add($total, $comision->calcular($monto)) : $total,
                '0',
            );

            return Decimal::round(Decimal::add($fila['pago_cat'], $comisionesCat));
        }, $resultado['tabla']);
        $inicialCat = $iniciales->reduce(
            fn (string $total, $comision) => $comision->incluye_cat ? Decimal::add($total, $comision->calcular($monto)) : $total,
            '0',
        );

        return [Decimal::round(Decimal::sub($saldo, $inicialCat)), $pagos];
    }

    private function sumar(array $filas, string $campo): string
    {
        return Decimal::round(array_reduce($filas, fn (string $total, array $fila) => Decimal::add($total, $fila[$campo]), '0'));
    }

    private function camposAcumulados(array $acumulados): array
    {
        return [
            'capital_acumulado' => $acumulados['capital'],
            'interes_acumulado' => $acumulados['interes'],
            'comisiones_acumuladas' => $acumulados['comisiones'],
            'pagado_acumulado' => $acumulados['pagado'],
        ];
    }

    private function formula(ProductoVersion $version, string $saldoFinanciado, array $resultado, array $tabla): array
    {
        return [
            'convencion' => 'días reales / 360',
            'calendario' => 'Semanal suma 7 días; quincenal suma 15 días; mensual conserva el día de la disposición. Si la disposición es fin de mes, todos los vencimientos permanecen en fin de mes; un día inexistente se ajusta solo para ese mes.',
            'metodo' => 'El interés de cada periodo usa el saldo inicial y sus días reales; la última cuota liquida el residuo.',
            'interes' => 'Iₖ = Sₖ₋₁ × (tasa anual / 100) × (díasₖ / 360)',
            'cuota_nivelada' => 'C = P ÷ Σ[1 ÷ Π(1 + iⱼ)]',
            'capital_fijo' => 'A = P ÷ n; la última amortización toma el saldo restante.',
            'saldo_financiado' => $saldoFinanciado,
            'tasa_anual' => (string) $version->tasa_ordinaria_anual,
            'cuota_exacta' => $resultado['cuota_exacta'],
            'cuota_redondeada' => $resultado['cuota_redondeada'],
            'redondeo' => 'Half-up a centavos en interés, capital, comisión, pago y saldo de cada fila.',
            'simbolos' => [
                ['simbolo' => 'Iₖ', 'significado' => 'Interés ordinario generado durante el periodo k.'],
                ['simbolo' => 'Sₖ₋₁', 'significado' => 'Saldo insoluto al inicio del periodo k.'],
                ['simbolo' => 'dₖ', 'significado' => 'Días naturales transcurridos durante el periodo k.'],
                ['simbolo' => 'P', 'significado' => 'Principal o saldo total financiado al inicio.'],
                ['simbolo' => 'C', 'significado' => 'Cuota base nivelada, antes de sumar comisiones.'],
                ['simbolo' => 'A', 'significado' => 'Amortización fija de capital por periodo.'],
                ['simbolo' => 'n', 'significado' => 'Número total de pagos.'],
                ['simbolo' => 'iⱼ', 'significado' => 'Factor de interés del periodo j: tasa anual × días del periodo ÷ 360.'],
                ['simbolo' => 'Σ', 'significado' => 'Suma de todos los factores indicados.'],
                ['simbolo' => 'Π', 'significado' => 'Producto acumulado de los factores indicados.'],
            ],
            'periodos' => array_map(fn (array $fila) => [
                'numero' => $fila['numero'],
                'dias' => $fila['dias'],
                'sustitucion_interes' => "{$fila['saldo_inicial']} × ".(string) $version->tasa_ordinaria_anual."% × {$fila['dias']}/360 = {$fila['interes']}",
            ], $tabla),
        ];
    }
}

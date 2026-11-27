<?php

namespace App\Services\Credito;

use App\Enums\ConvencionInteres;
use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Enums\PoliticaInteresBullet;
use App\Enums\VersionMotorCredito;
use App\Models\CalendarioCrediticioVersion;
use App\Services\FechaEmpresa;
use App\Support\Decimal;
use Carbon\CarbonImmutable;

final class PreviaCalendarioCrediticio
{
    public function __construct(
        private readonly CalendarioCreditoSimple $nominal,
        private readonly CalendarioCrediticioService $calendario,
        private readonly TablaAmortizacionCreditoSimple $amortizacion,
        private readonly MotorPagos $pagos,
        private readonly InteresPorPeriodo $interes,
        private readonly FechaEmpresa $fecha,
    ) {}

    public function calcular(CalendarioCrediticioVersion $version, array $datos): array
    {
        $inicio = CarbonImmutable::parse($datos['inicio'], $this->fecha->zonaHoraria());
        $periodicidad = PeriodicidadCredito::from($datos['periodicidad']);
        $metodo = MetodoAmortizacion::from($datos['metodo']);
        $politica = isset($datos['politica_bullet']) ? PoliticaInteresBullet::from($datos['politica_bullet']) : null;
        $fechas = $this->nominal->fechas($inicio, $periodicidad, $datos['plazo']);
        $cambios = $this->calendario->ajustar($version, $fechas)['fechas'];
        $ajustadas = array_map(fn ($fila) => CarbonImmutable::parse($fila['fecha'], $inicio->getTimezone()), $cambios);

        return [
            'cambios' => $cambios,
            'original' => $this->escenario($inicio, $fechas, $metodo, $politica, $datos['gracia']),
            'ajustado' => $this->escenario($inicio, $ajustadas, $metodo, $politica, $datos['gracia']),
        ];
    }

    private function escenario(CarbonImmutable $inicio, array $fechas, MetodoAmortizacion $metodo, ?PoliticaInteresBullet $politica, int $gracia): array
    {
        $tabla = $this->amortizacion->calcular('12000', '36', $inicio, $fechas, $metodo, VersionMotorCredito::Act360Racional,
            ConvencionInteres::DiasReales360, $politica)['tabla'];
        $producto = [
            'motor_calculo_version' => 2, 'tasa_ordinaria_anual' => '36', 'tasa_moratoria_anual' => '18', 'dias_gracia_mora' => $gracia,
            'politica_mora' => ['gracia' => 'efectiva', 'intereses' => 'ambos'],
            'reglas' => ['metodos_amortizacion' => [$metodo->value], 'convencion_interes' => 'dias_reales_360',
                'politica_interes_bullet' => $politica?->value, 'base_ordinaria' => 'saldo_insoluto', 'base_moratoria' => 'obligacion_vencida', 'aplicacion_prepago' => 'conservar_fecha'],
            'fiscalidad' => ['ordinario' => ['tratamiento' => 'no_causa'], 'moratorio' => ['tratamiento' => 'no_causa']],
        ];
        $estado = $this->pagos->iniciar($producto, [
            'motor_calculo_version' => 2, 'convencion_interes' => 'dias_reales_360', 'metodo' => $metodo->value,
            'politica_interes_bullet' => $politica?->value, 'escenario' => ['fecha_disposicion' => $inicio->toDateString(), 'saldo_financiado' => '12000.00'],
            'tabla' => $tabla,
        ]);
        $filas = [];
        $limiteAnterior = $inicio;
        foreach ($tabla as $indice => $fila) {
            $fecha = $fechas[$indice];
            $exigible = Decimal::compare($fila['pago_total'], '0') > 0;
            $devengo = $politica === PoliticaInteresBullet::AlVencimiento
                ? Decimal::round($this->interes->calcular('12000', '36', $limiteAnterior, $fecha, ConvencionInteres::DiasReales360))
                : $fila['interes'];
            $limiteAnterior = $fecha;
            $filas[] = [
                'devengado' => $devengo,
                ...array_intersect_key($fila, array_flip(['fecha', 'interes', 'capital', 'dias'])),
                'exigible' => $exigible,
                'atraso_desde' => $exigible ? $fecha->addDay()->toDateString() : null,
                'gracia_hasta' => $exigible && $gracia > 0 ? $fecha->addDays($gracia)->toDateString() : null,
                'mora_desde' => $exigible ? $fecha->addDays($gracia + 1)->toDateString() : null,
            ];
        }
        $ultimo = end($filas);
        $corte = $this->pagos->resumen($this->pagos->avanzar($estado, $ultimo['mora_desde'] ?? $ultimo['fecha']));

        return ['periodos' => $filas, 'corte' => array_intersect_key($corte, array_flip(['fecha', 'dias_atraso', 'exigible']))];
    }
}

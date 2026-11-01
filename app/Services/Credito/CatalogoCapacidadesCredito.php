<?php

namespace App\Services\Credito;

use App\Enums\ConvencionInteres;
use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Enums\VersionMotorCredito;

/** Declaraciones operativas, independientes de productos y persistencia. */
final class CatalogoCapacidadesCredito
{
    public static function catalogo(): array
    {
        $opcion = static fn ($value, $label, $ayuda) => compact('value', 'label', 'ayuda');

        return [
            'metodos_amortizacion' => [
                $opcion(MetodoAmortizacion::CuotaNivelada->value, 'Cuota nivelada', 'Mantiene el pago base; las comisiones e impuestos se suman por separado y la última cuota absorbe el residuo.'),
                $opcion(MetodoAmortizacion::CapitalFijo->value, 'Capital fijo', 'Distribuye el principal en partes iguales; el interés se calcula sobre el saldo pendiente.'),
            ],
            'periodicidades' => [
                $opcion(PeriodicidadCredito::Semanal->value, 'Semanal', 'Vencimientos cada 7 días.'),
                $opcion(PeriodicidadCredito::Quincenal->value, 'Quincenal', 'Vencimientos cada 15 días; no corresponde a quincenas del calendario.'),
                $opcion(PeriodicidadCredito::Mensual->value, 'Mensual', 'Conserva el día de la disposición. El fin de mes permanece en fin de mes; un día inexistente se ajusta solo ese mes.'),
            ],
            'convenciones_interes' => [$opcion(ConvencionInteres::DiasReales360->value, 'Días reales / 360', 'Interés sobre saldo insoluto, tasa anual y días naturales del periodo, con base anual 360.')],
            'aplicaciones_prepago' => [
                $opcion('reducir_plazo', 'Reducir plazo', 'Conserva la base contractual y reduce los vencimientos pendientes.'),
                $opcion('reducir_pago', 'Reducir pago', 'Recalcula el pago sobre el saldo pendiente conservando los vencimientos.'),
            ],
        ];
    }

    public static function paraMotor(VersionMotorCredito $motor): array
    {
        // Listas explícitas: añadir un descriptor no habilita un algoritmo histórico.
        $permitidas = match ($motor) {
            VersionMotorCredito::Historico, VersionMotorCredito::Act360Racional => [
                'metodos_amortizacion' => ['cuota_nivelada', 'capital_fijo'],
                'periodicidades' => ['semanal', 'quincenal', 'mensual'],
                'aplicaciones_prepago' => ['reducir_plazo', 'reducir_pago'],
            ],
        };
        $catalogo = self::catalogo();
        foreach ($permitidas as $dimension => $valores) {
            $catalogo[$dimension] = array_values(array_filter($catalogo[$dimension], fn ($opcion) => in_array($opcion['value'], $valores, true)));
        }
        $catalogo['convenciones_interes'] = array_values(array_filter($catalogo['convenciones_interes'], fn ($opcion) => $motor->admiteConvencion(ConvencionInteres::from($opcion['value']))));

        return $catalogo;
    }

    public static function valores(string $dimension, ?VersionMotorCredito $motor = null): array
    {
        return array_column(($motor ? self::paraMotor($motor) : self::catalogo())[$dimension], 'value');
    }

    public static function paraVue(): array
    {
        return ['catalogo' => self::catalogo(), 'configuracion' => [
            'motor_calculo_version' => VersionMotorCredito::actual()->value,
            'opciones' => self::paraMotor(VersionMotorCredito::actual()),
            'predeterminados' => ['periodicidad' => 'mensual', 'metodo' => 'cuota_nivelada', 'convencion_interes' => 'dias_reales_360', 'aplicacion_prepago' => 'reducir_plazo'],
        ]];
    }
}

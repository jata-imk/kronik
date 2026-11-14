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
                $opcion(MetodoAmortizacion::Bullet->value, 'Bullet', 'Capital al vencimiento e intereses periódicos o al vencimiento, sin capitalización. El anticipo conserva el vencimiento.'),
                $opcion(MetodoAmortizacion::CapitalFijo->value, 'Capital fijo', 'Distribuye el principal en partes iguales; el interés se calcula sobre el saldo pendiente.'),
            ],
            'politicas_interes_bullet' => [
                $opcion('periodicos', 'Intereses periódicos', 'El interés es exigible en cada vencimiento; el capital vence al final.'),
                $opcion('al_vencimiento', 'Intereses al vencimiento', 'El interés se acumula sin capitalizar y es exigible junto con el capital al final.'),
            ],
            'bases_ordinarias' => [$opcion('saldo_insoluto', 'Saldo insoluto', 'Principal pendiente de pago; los intereses no se capitalizan.')],
            'bases_moratorias' => [
                $opcion('capital_vencido', 'Capital vencido', 'Base conservada de los métodos actuales.'),
                $opcion('saldo_insoluto', 'Saldo insoluto', 'Principal pendiente; la obligación vencida impagada dispara la mora, sin multiplicar esta base por cuota.'),
                $opcion('capital_inicial', 'Capital inicial', 'Principal inicial; la obligación vencida impagada dispara la mora, sin multiplicar esta base por cuota.'),
                $opcion('obligacion_vencida', 'Obligación vencida impagada', 'Solo el remanente impagado de las obligaciones vencidas; puede ser interés antes de vencer el capital.'),
            ],
            'periodicidades' => [
                $opcion(PeriodicidadCredito::Semanal->value, 'Semanal', 'Vencimientos cada 7 días.'),
                $opcion(PeriodicidadCredito::Quincenal->value, 'Quincenal', 'Vencimientos cada 15 días; no corresponde a quincenas del calendario.'),
                $opcion(PeriodicidadCredito::Mensual->value, 'Mensual', 'Conserva el día de la disposición. El fin de mes permanece en fin de mes; un día inexistente se ajusta solo ese mes.'),
            ],
            'convenciones_interes' => [$opcion(ConvencionInteres::DiasReales360->value, 'Días reales / 360', 'Interés sobre saldo insoluto, tasa anual y días naturales del periodo, con base anual 360.')],
            'aplicaciones_prepago' => [
                $opcion('conservar_fecha', 'Conservar vencimiento', 'Cubre exigibles y reduce principal sin cobrar el devengado aún no exigible ni cambiar su vencimiento.'),
                $opcion('reducir_plazo', 'Reducir plazo', 'Conserva la base contractual y reduce los vencimientos pendientes.'),
                $opcion('reducir_pago', 'Reducir pago', 'Recalcula el pago sobre el saldo pendiente conservando los vencimientos.'),
            ],
        ];
    }

    public static function paraMotor(VersionMotorCredito $motor, bool $configuracion = false): array
    {
        // Listas explícitas: añadir un descriptor no habilita un algoritmo histórico.
        $permitidas = match ($motor) {
            VersionMotorCredito::Historico, VersionMotorCredito::Act360Racional => [
                'metodos_amortizacion' => ['cuota_nivelada', 'capital_fijo'],
                'periodicidades' => ['semanal', 'quincenal', 'mensual'],
                'aplicaciones_prepago' => ['reducir_plazo', 'reducir_pago'],
            ],
        };
        $permitidas += ['politicas_interes_bullet' => [], 'bases_ordinarias' => ['saldo_insoluto'], 'bases_moratorias' => ['capital_vencido']];
        if ($motor === VersionMotorCredito::Act360Racional) {
            $permitidas['metodos_amortizacion'][] = 'bullet';
            $permitidas['politicas_interes_bullet'] = ['periodicos', 'al_vencimiento'];
            $permitidas['bases_moratorias'] = ['capital_vencido', 'saldo_insoluto', 'capital_inicial', 'obligacion_vencida'];
            $permitidas['aplicaciones_prepago'][] = 'conservar_fecha';
        }
        $catalogo = self::catalogo();
        foreach ($permitidas as $dimension => $valores) {
            $catalogo[$dimension] = array_values(array_filter($catalogo[$dimension], fn ($opcion) => in_array($opcion['value'], $valores, true)));
        }
        $catalogo['convenciones_interes'] = array_values(array_filter($catalogo['convenciones_interes'], fn ($opcion) => $motor->admiteConvencion(ConvencionInteres::from($opcion['value']))));

        return $catalogo;
    }

    public static function valores(string $dimension, ?VersionMotorCredito $motor = null, bool $configuracion = false): array
    {
        return array_column(($motor ? self::paraMotor($motor, $configuracion) : self::catalogo())[$dimension], 'value');
    }

    public static function combinaciones(): array
    {
        return [
            'bullet' => ['metodos' => ['bullet'], 'bases_moratorias' => ['saldo_insoluto', 'capital_inicial', 'obligacion_vencida'], 'aplicaciones_prepago' => ['conservar_fecha'], 'operable' => true,
                'aviso' => 'El capital vence al final. El interés devengado aún no exigible conserva su vencimiento tras un anticipo y no se capitaliza.'],
            'actuales' => ['metodos' => ['cuota_nivelada', 'capital_fijo'], 'bases_moratorias' => ['capital_vencido'], 'aplicaciones_prepago' => ['reducir_plazo', 'reducir_pago'], 'operable' => true],
        ];
    }

    public static function paraVue(bool $prepararBorradores = false): array
    {
        return ['catalogo' => self::catalogo(), 'configuracion' => [
            'motor_calculo_version' => VersionMotorCredito::actual()->value,
            'opciones' => self::paraMotor(VersionMotorCredito::actual(), configuracion: $prepararBorradores),
            'combinaciones' => self::combinaciones(),
            'predeterminados' => ['periodicidad' => 'mensual', 'metodo' => 'cuota_nivelada', 'convencion_interes' => 'dias_reales_360', 'aplicacion_prepago' => 'reducir_plazo'],
        ]];
    }
}

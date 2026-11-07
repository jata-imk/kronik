<?php

namespace App\Services\Credito;

use App\Enums\ConvencionInteres;
use App\Enums\MetodoAmortizacion;
use App\Enums\PoliticaInteresBullet;
use App\Enums\VersionMotorCredito;
use App\Services\Credito\Amortizacion\AmortizacionInvalida;
use App\Services\Credito\Amortizacion\CalculadorAmortizacion;
use App\Services\Credito\Amortizacion\CondicionesAmortizacion;
use App\Services\Credito\Amortizacion\ExigibilidadInteresBullet;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/** Adaptador del contrato de tabla existente al cálculo reutilizable. */
final class TablaAmortizacionCreditoSimple
{
    public function __construct(private readonly CalculadorAmortizacion $calculador, private readonly ExigibilidadInteresBullet $exigibilidad) {}

    public function calcular(string $principal, string $tasaAnualPorcentaje, CarbonImmutable $inicio, array $fechas, MetodoAmortizacion $metodo, VersionMotorCredito $motor = VersionMotorCredito::Historico, ConvencionInteres $convencion = ConvencionInteres::DiasReales360, ?PoliticaInteresBullet $politica = null): array
    {
        $motor->validarConvencion($convencion);
        try {
            if (($metodo === MetodoAmortizacion::Bullet) !== ($politica !== null)) {
                throw new AmortizacionInvalida('La política de intereses debe estar definida exclusivamente para Bullet.');
            }
            $resultado = $this->calculador->calcular(new CondicionesAmortizacion($principal, $tasaAnualPorcentaje, $inicio, $fechas, $metodo, $motor, $convencion));

            return ($politica === null ? $resultado : $this->exigibilidad->aplicar($resultado, $politica, $convencion))->toArray();
        } catch (AmortizacionInvalida $error) {
            throw ValidationException::withMessages(['metodo' => $error->getMessage()]);
        }
    }
}

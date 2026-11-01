<?php

namespace App\Services\Credito;

use App\Enums\ConvencionInteres;
use App\Enums\MetodoAmortizacion;
use App\Enums\VersionMotorCredito;
use App\Services\Credito\Amortizacion\AmortizacionInvalida;
use App\Services\Credito\Amortizacion\CalculadorAmortizacion;
use App\Services\Credito\Amortizacion\CondicionesAmortizacion;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/** Adaptador del contrato de tabla existente al cálculo reutilizable. */
final class TablaAmortizacionCreditoSimple
{
    public function __construct(private readonly CalculadorAmortizacion $calculador) {}

    public function calcular(string $principal, string $tasaAnualPorcentaje, CarbonImmutable $inicio, array $fechas, MetodoAmortizacion $metodo, VersionMotorCredito $motor = VersionMotorCredito::Historico, ConvencionInteres $convencion = ConvencionInteres::DiasReales360): array
    {
        $motor->validarConvencion($convencion);
        try {
            return $this->calculador->calcular(new CondicionesAmortizacion($principal, $tasaAnualPorcentaje, $inicio, $fechas, $metodo, $motor, $convencion))->toArray();
        } catch (AmortizacionInvalida $error) {
            throw ValidationException::withMessages(['metodo' => $error->getMessage()]);
        }
    }
}

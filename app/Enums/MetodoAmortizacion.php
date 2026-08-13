<?php

namespace App\Enums;

enum MetodoAmortizacion: string
{
    case CuotaNivelada = 'cuota_nivelada';
    case CapitalFijo = 'capital_fijo';
}

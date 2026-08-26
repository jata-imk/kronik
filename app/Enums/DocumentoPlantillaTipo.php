<?php

namespace App\Enums;

enum DocumentoPlantillaTipo: string
{
    case ConsentimientoSic = 'consentimiento_sic';
    case Garantia = 'garantia';
    case Contrato = 'contrato';

    public function label(): string
    {
        return match ($this) {
            self::ConsentimientoSic => 'Consentimiento SIC',
            self::Garantia => 'Garantía',
            self::Contrato => 'Contrato',
        };
    }
}

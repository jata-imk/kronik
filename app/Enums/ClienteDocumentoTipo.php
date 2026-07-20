<?php

namespace App\Enums;

enum ClienteDocumentoTipo: string
{
    case Ine = 'ine';
    case ComprobanteDomicilio = 'comprobante_domicilio';
    case ConstanciaFiscal = 'constancia_fiscal';
    case ComprobanteIngresos = 'comprobante_ingresos';
    case Adicional = 'adicional';

    public static function requeridos(): array
    {
        return [
            self::Ine,
            self::ComprobanteDomicilio,
            self::ConstanciaFiscal,
            self::ComprobanteIngresos,
        ];
    }
}

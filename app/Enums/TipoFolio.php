<?php

namespace App\Enums;

enum TipoFolio: string
{
    case Solicitud = 'solicitud';
    case Contrato = 'contrato';
    case Credito = 'credito';
    case Recibo = 'recibo';

    public function codigo(): string
    {
        return match ($this) {
            self::Solicitud => 'SOL', self::Contrato => 'CTO', self::Credito => 'CRE', self::Recibo => 'REC',
        };
    }

    public function modelo(): string
    {
        return match ($this) {
            self::Solicitud => \App\Models\Solicitud::class, self::Contrato => \App\Models\SolicitudPaquete::class,
            self::Credito => \App\Models\Credito::class, self::Recibo => \App\Models\CreditoPago::class,
        };
    }
}

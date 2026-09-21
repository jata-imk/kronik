<?php

namespace App\Services\SICs;

use Illuminate\Validation\ValidationException;

final class ConsultaSicNoDisponible
{
    public const MENSAJE = 'Las consultas SIC están temporalmente deshabilitadas. Falta validar la integración contratada, su seguridad y la autorización aplicable. No se realizó ninguna consulta.';

    public static function rechazar(): never
    {
        throw ValidationException::withMessages(['sic' => self::MENSAJE]);
    }
}

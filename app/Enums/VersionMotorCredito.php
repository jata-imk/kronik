<?php

namespace App\Enums;

use Illuminate\Validation\ValidationException;

enum VersionMotorCredito: int
{
    case Historico = 1;
    case Act360Racional = 2;

    public static function actual(): self
    {
        return self::Act360Racional;
    }

    public static function desdeSnapshot(array $snapshot): self
    {
        $valor = array_key_exists('motor_calculo_version', $snapshot) ? $snapshot['motor_calculo_version'] : self::Historico->value;
        if (! is_int($valor) || ! $version = self::tryFrom($valor)) {
            throw ValidationException::withMessages(['credito' => 'La versión del motor de este registro no está soportada. Solicita revisión sin modificar su evidencia histórica.']);
        }

        return $version;
    }
}

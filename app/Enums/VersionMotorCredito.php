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

    public function admiteConvencion(ConvencionInteres $convencion): bool
    {
        return match ($this) {
            self::Historico, self::Act360Racional => $convencion === ConvencionInteres::DiasReales360,
        };
    }

    public function validarConvencion(ConvencionInteres $convencion): void
    {
        if (! $this->admiteConvencion($convencion)) {
            throw ValidationException::withMessages(['convencion_interes' => 'La convención de días no es compatible con la versión del motor de este registro. Solicita revisión sin modificar su evidencia histórica.']);
        }
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

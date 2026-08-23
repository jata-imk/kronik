<?php

namespace App\Enums;

enum PeriodicidadCredito: string
{
    case Semanal = 'semanal';
    case Quincenal = 'quincenal';
    case Mensual = 'mensual';

    public function periodosAnuales(): int
    {
        return match ($this) {
            self::Semanal => 52, self::Quincenal => 24, self::Mensual => 12
        };
    }
}

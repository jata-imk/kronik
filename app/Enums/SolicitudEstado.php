<?php

namespace App\Enums;

enum SolicitudEstado: string
{
    case Borrador = 'borrador';
    case EnRevision = 'en_revision';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::EnRevision => 'En revisión',
        };
    }
}

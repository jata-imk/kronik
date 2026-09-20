<?php

namespace App\Enums;

enum SolicitudEstado: string
{
    case Borrador = 'borrador';
    case EnRevision = 'en_revision';
    case Devuelta = 'devuelta';
    case Rechazada = 'rechazada';
    case Cancelada = 'cancelada';
    case Aprobada = 'aprobada';

    public function editable(): bool
    {
        return in_array($this, [self::Borrador, self::Devuelta], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::EnRevision => 'En revisión',
            self::Devuelta => 'Devuelta para corrección',
            self::Rechazada => 'Rechazada',
            self::Cancelada => 'Cancelada',
            self::Aprobada => 'Aprobada',
        };
    }
}

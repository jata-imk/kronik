<?php

namespace App\Enums;

enum OperatorNotificationEvent: string
{
    case ApplicationAssigned = 'solicitud.asignada';
    case ApplicationReturned = 'solicitud.devuelta';
    case DocumentReady = 'documento.disponible';

    public function priority(): string
    {
        return match ($this) {
            self::ApplicationReturned => 'high',
            default => 'normal',
        };
    }
}

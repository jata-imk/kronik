<?php

namespace App\Enums;

enum ClienteDocumentoEstado: string
{
    case Pendiente = 'pendiente';
    case Recibido = 'recibido';
    case Validado = 'validado';
    case Rechazado = 'rechazado';
    case Vencido = 'vencido';
}

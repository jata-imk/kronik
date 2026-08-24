<?php

namespace App\Enums;

enum DocumentoGeneradoEstado: string
{
    case Pendiente = 'pendiente';
    case Procesando = 'procesando';
    case Generado = 'generado';
    case Fallido = 'fallido';
}

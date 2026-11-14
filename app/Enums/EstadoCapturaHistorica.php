<?php

namespace App\Enums;

enum EstadoCapturaHistorica: string
{
    case NoAplica = 'no_aplica';
    case Pendiente = 'pendiente';
    case Completa = 'completa';
}

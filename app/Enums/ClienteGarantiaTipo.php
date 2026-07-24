<?php

namespace App\Enums;

enum ClienteGarantiaTipo: string
{
    case Prendaria = 'prendaria';
    case Hipotecaria = 'hipotecaria';
    case Liquida = 'liquida';
    case Otra = 'otra';
}

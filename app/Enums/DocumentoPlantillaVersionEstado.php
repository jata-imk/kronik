<?php

namespace App\Enums;

enum DocumentoPlantillaVersionEstado: string
{
    case Borrador = 'borrador';
    case Activa = 'activa';
    case Retirada = 'retirada';
}

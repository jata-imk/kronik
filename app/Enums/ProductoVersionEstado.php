<?php

namespace App\Enums;

enum ProductoVersionEstado: string
{
    case Borrador = 'borrador';
    case Programada = 'programada';
    case Activa = 'activa';
    case Retirada = 'retirada';
}

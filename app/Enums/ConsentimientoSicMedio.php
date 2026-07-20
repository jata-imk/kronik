<?php

namespace App\Enums;

enum ConsentimientoSicMedio: string
{
    case FirmaAutografa = 'firma_autografa';
    case FirmaElectronica = 'firma_electronica';
    case CorreoElectronico = 'correo_electronico';
    case Otro = 'otro';
}

<?php

namespace App\Services\SICs\CirculoDeCredito\RCFicoScore\Model;

use App\Services\SICs\CirculoDeCredito\RCFicoScore\ObjectSerializer;

class CatalogoSexo
{

    const F = 'F';
    const M = 'M';


    public static function getAllowableEnumValues()
    {
        return [
            self::F,
            self::M,
        ];
    }
}

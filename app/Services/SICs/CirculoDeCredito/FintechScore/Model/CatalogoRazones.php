<?php

namespace App\Services\SICs\CirculoDeCredito\FintechScore\Model;

class CatalogoRazones
{

    const E0 = 'E0';
    const E1 = 'E1';
    const E2 = 'E2';


    public static function getAllowableEnumValues()
    {
        return [
            self::E0,
            self::E1,
            self::E2,
        ];
    }
}

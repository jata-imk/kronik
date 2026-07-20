<?php

namespace App\Services\SICs\CirculoDeCredito\FicoScorev2\Model;

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

<?php

namespace App\Services\SICs\CirculoDeCredito\FicoScorev2\Model;

class CatalogoTipoDomicilio
{

    const N = 'N';
    const O = 'O';
    const C = 'C';
    const P = 'P';
    const E = 'E';


    public static function getAllowableEnumValues()
    {
        return [
            self::N,
            self::O,
            self::C,
            self::P,
            self::E,
        ];
    }
}

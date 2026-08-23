<?php

namespace App\Support;

use InvalidArgumentException;

final class Decimal
{
    public const SCALE = 16;

    public static function add(string $a, string $b, int $scale = self::SCALE): string
    {
        return bcadd($a, $b, $scale);
    }

    public static function sub(string $a, string $b, int $scale = self::SCALE): string
    {
        return bcsub($a, $b, $scale);
    }

    public static function mul(string $a, string $b, int $scale = self::SCALE): string
    {
        return bcmul($a, $b, $scale);
    }

    public static function div(string $a, string $b, int $scale = self::SCALE): string
    {
        if (bccomp($b, '0', self::SCALE) === 0) {
            throw new InvalidArgumentException('No se puede dividir entre cero.');
        }

        return bcdiv($a, $b, $scale);
    }

    public static function compare(string $a, string $b, int $scale = self::SCALE): int
    {
        return bccomp($a, $b, $scale);
    }

    public static function round(string $value, int $scale = 2): string
    {
        $increment = '0.'.str_repeat('0', $scale).'5';
        $adjusted = str_starts_with($value, '-') ? bcsub($value, $increment, $scale + 1) : bcadd($value, $increment, $scale + 1);

        return bcadd($adjusted, '0', $scale);
    }
}

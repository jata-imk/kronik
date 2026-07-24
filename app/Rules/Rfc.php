<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Rfc implements ValidationRule
{
    private const CHARACTER_VALUES = '0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ Ñ';

    public function __construct(private readonly ?string $tipoPersona) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('El :attribute debe ser una cadena de texto.');

            return;
        }

        $rfc = mb_strtoupper(trim($value), 'UTF-8');
        $expectedLength = $this->tipoPersona === 'fisica' ? 13 : 12;

        if (! in_array($this->tipoPersona, ['fisica', 'moral'], true)) {
            $fail('Seleccione el tipo de persona antes de capturar el RFC.');

            return;
        }

        if (mb_strlen($rfc) !== $expectedLength) {
            $fail("El :attribute de una persona {$this->tipoPersona} debe tener {$expectedLength} caracteres.");

            return;
        }

        $pattern = $this->tipoPersona === 'fisica'
            ? '/^[A-ZÑ&]{4}[0-9]{6}[A-Z0-9]{3}$/u'
            : '/^[A-ZÑ&]{3}[0-9]{6}[A-Z0-9]{3}$/u';

        if (! preg_match($pattern, $rfc)) {
            $fail('El :attribute no tiene la estructura establecida por el SAT.');

            return;
        }

        $dateOffset = $this->tipoPersona === 'fisica' ? 4 : 3;
        $date = substr($rfc, $dateOffset, 6);

        if (! checkdate((int) substr($date, 2, 2), (int) substr($date, 4, 2), 2000 + (int) substr($date, 0, 2))) {
            $fail('La fecha incluida en el :attribute no es una fecha de calendario válida.');

            return;
        }

        if (! hash_equals($this->checkDigit($rfc), substr($rfc, -1))) {
            $fail('El dígito verificador del :attribute no es válido.');
        }
    }

    private function checkDigit(string $rfc): string
    {
        $normalized = mb_strlen($rfc) === 12 ? " {$rfc}" : $rfc;
        $body = mb_substr($normalized, 0, 12);
        $sum = 0;

        foreach (mb_str_split($body) as $index => $character) {
            $value = mb_strpos(self::CHARACTER_VALUES, $character);
            $sum += $value * (13 - $index);
        }

        return match ($digit = 11 - ($sum % 11)) {
            11 => '0',
            10 => 'A',
            default => (string) $digit,
        };
    }
}

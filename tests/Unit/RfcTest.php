<?php

use App\Rules\Rfc;

function rfcErrors(string $rfc, ?string $tipoPersona): array
{
    $errors = [];

    (new Rfc($tipoPersona))->validate(
        'RFC',
        $rfc,
        function (string $message) use (&$errors): void {
            $errors[] = $message;
        },
    );

    return $errors;
}

test('accepts valid RFCs according to the person type', function () {
    expect(rfcErrors('GALA910418AB8', 'fisica'))->toBeEmpty()
        ->and(rfcErrors('KDE260101AB1', 'moral'))->toBeEmpty();
});

test('normalizes case and surrounding whitespace for validation', function () {
    expect(rfcErrors('  kde260101ab1  ', 'moral'))->toBeEmpty();
});

test('rejects a length that does not match the person type', function () {
    expect(rfcErrors('GALA910418AB8', 'moral'))->not->toBeEmpty()
        ->and(rfcErrors('KDE260101AB1', 'fisica'))->not->toBeEmpty();
});

test('rejects impossible dates embedded in the RFC', function () {
    expect(rfcErrors('KDE261332AB1', 'moral'))
        ->toContain('La fecha incluida en el :attribute no es una fecha de calendario válida.');
});

test('rejects an incorrect check digit', function () {
    expect(rfcErrors('KDE260101AB2', 'moral'))
        ->toContain('El dígito verificador del :attribute no es válido.');
});

test('requires the person type', function () {
    expect(rfcErrors('KDE260101AB1', null))
        ->toContain('Seleccione el tipo de persona antes de capturar el RFC.');
});

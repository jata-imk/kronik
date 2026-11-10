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

test('rejects generic national and foreign RFCs with a business message', function (string $rfc) {
    expect(rfcErrors($rfc, 'fisica'))
        ->toContain('Captura un RFC propio; no se admiten RFC genéricos en este formulario.');
})->with(['XAXX010101000', 'XEXX010101000']);

test('solo permite genéricos explícitos al habilitarlos con un tipo válido', function (string $rfc, string $tipo) {
    $errors = [];
    (new Rfc($tipo, permitirGenericos: true))->validate('RFC', mb_strtolower(" $rfc "), function ($message) use (&$errors) {
        $errors[] = $message;
    });
    expect($errors)->toBeEmpty();
})->with(Rfc::genericos())->with(['fisica', 'moral']);

test('la excepción de genéricos no permite omitir o falsear tipo ni inventar otros RFC', function (mixed $tipo, string $rfc) {
    $errors = [];
    (new Rfc($tipo, permitirGenericos: true))->validate('RFC', $rfc, function ($message) use (&$errors) {
        $errors[] = $message;
    });
    expect($errors)->not->toBeEmpty();
})->with([[null, 'XAXX010101000'], [[], 'XEXX010101000'], ['invalido', 'XEXX010101000'], ['moral', 'GALA910418AB8'], ['fisica', 'GALA910418AB9'], ['fisica', 'XAXX010101001']]);

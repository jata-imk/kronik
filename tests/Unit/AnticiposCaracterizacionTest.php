<?php

use App\Services\Credito\MotorAnticipos;

uses(Tests\TestCase::class);

test('ambos modos y métodos de anticipo conservan el resultado completo de 56026d0', function () {
    $casos = json_decode(file_get_contents(__DIR__.'/../Fixtures/anticipos-56026d0.json'), true, flags: JSON_THROW_ON_ERROR);
    foreach ($casos as $c) {
        expect(app(MotorAnticipos::class)->calcular($c['estado'], $c['tabla'], 'prepago', '1500.00'))->toBe($c['resultado']);
    }
});

<?php

use Database\Seeders\E2eSeeder;

test('E2E fixtures refuse to run outside the isolated E2E environment', function () {
    expect(fn () => app(E2eSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'E2eSeeder solo puede ejecutarse en el entorno e2e aislado.');
});

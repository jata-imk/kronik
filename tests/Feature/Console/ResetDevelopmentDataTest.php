<?php

use App\Models\EmpresaConfiguracion;
use App\Models\Sic;
use App\Models\SicApi;
use App\Models\Sucursal;
use App\Models\User;

it('resets development data and recreates demo records', function () {
    User::factory()->withPersonalTeam()->create([
        'email' => 'discard@example.test',
    ]);

    $this->artisan('dev:reset-data')
        ->assertSuccessful();

    expect(User::where('email', 'discard@example.test')->exists())->toBeFalse()
        ->and(User::where('email', 'test@example.com')->exists())->toBeTrue()
        ->and(EmpresaConfiguracion::where('singleton_key', 'default')->exists())->toBeTrue()
        ->and(Sucursal::where('clave', 'MATRIZ')->exists())->toBeTrue()
        ->and(Sic::where('clave', 'circulo-credito')->count())->toBe(1)
        ->and(SicApi::where('clave', 'fico_score_v2')->count())->toBe(1);

    $this->artisan('db:seed', ['--class' => Database\Seeders\SystemSeeder::class])
        ->assertSuccessful();

    expect(Sic::where('clave', 'circulo-credito')->count())->toBe(1)
        ->and(SicApi::where('clave', 'fico_score_v2')->count())->toBe(1);
});

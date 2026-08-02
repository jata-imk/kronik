<?php

use App\Models\CodigoPostal;
use App\Models\DivisionAdministrativa;
use App\Models\EmpresaConfiguracion;
use App\Models\Pais;
use App\Models\RegimenFiscal;
use App\Models\Sic;
use App\Models\SicApi;
use App\Models\Sucursal;
use App\Models\User;

it('resets development data and recreates demo records', function () {
    $pais = Pais::create([
        'nombre_es' => 'Mexico',
        'nombre_us' => 'Mexico',
        'codigo_iso' => 'MX',
        'codigo_iso3' => 'MEX',
    ]);
    $regimen = RegimenFiscal::create([
        'clave' => '601',
        'descripcion' => 'General de Ley Personas Morales',
        'fisica' => false,
        'moral' => true,
        'fecha_inicio_vigencia' => '2022-01-01',
        'fecha_fin_vigencia' => '2099-12-31',
    ]);
    $estado = DivisionAdministrativa::create([
        'pais_id' => $pais->id,
        'nombre' => 'Yucatan',
        'codigo' => '31',
        'nivel' => 1,
        'tipo' => 'estado',
    ]);
    $codigoPostal = CodigoPostal::create([
        'codigo' => '97000',
        'pais_id' => $pais->id,
        'division_admin_id' => $estado->id,
    ]);

    User::factory()->withPersonalTeam()->create([
        'email' => 'discard@example.test',
    ]);

    $this->artisan('dev:reset-data')
        ->assertSuccessful();

    expect(User::where('email', 'discard@example.test')->exists())->toBeFalse()
        ->and(User::where('email', 'test@example.com')->exists())->toBeTrue()
        ->and(User::where('email', 'consulta.clientes@example.test')->exists())->toBeTrue()
        ->and(User::where('email', 'editor.expedientes@example.test')->exists())->toBeTrue()
        ->and(User::where('email', 'sin.acceso.clientes@example.test')->exists())->toBeTrue()
        ->and(EmpresaConfiguracion::where('singleton_key', 'default')->exists())->toBeTrue()
        ->and(Sucursal::where('clave', 'MATRIZ')->exists())->toBeTrue()
        ->and(Sic::where('clave', 'circulo-credito')->count())->toBe(1)
        ->and(SicApi::where('clave', 'fico_score_v2')->count())->toBe(1)
        ->and(Pais::find($pais->id))->not->toBeNull()
        ->and(RegimenFiscal::find($regimen->id))->not->toBeNull()
        ->and(DivisionAdministrativa::find($estado->id))->not->toBeNull()
        ->and(CodigoPostal::find($codigoPostal->id))->not->toBeNull();

    $this->artisan('db:seed', ['--class' => Database\Seeders\SystemSeeder::class])
        ->assertSuccessful();

    expect(Sic::where('clave', 'circulo-credito')->count())->toBe(1)
        ->and(SicApi::where('clave', 'fico_score_v2')->count())->toBe(1);
});

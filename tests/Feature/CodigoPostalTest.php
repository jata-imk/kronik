<?php

use App\Models\CodigoPostal;
use App\Models\DivisionAdministrativa;
use App\Models\Pais;

beforeEach(function () {
    $this->actingAs(actingAsSuperAdmin());

    $pais = Pais::create([
        'nombre_es' => 'México',
        'codigo_iso' => 'MX',
        'codigo_iso3' => 'MEX',
    ]);

    $estado = DivisionAdministrativa::create([
        'pais_id' => $pais->id,
        'nombre' => 'Yucatán',
        'nivel' => 1,
        'tipo' => 'estado',
    ]);
    $municipio = DivisionAdministrativa::create([
        'pais_id' => $pais->id,
        'nombre' => 'Mérida',
        'nivel' => 2,
        'division_padre_id' => $estado->id,
        'tipo' => 'municipio',
    ]);
    $localidad = DivisionAdministrativa::create([
        'pais_id' => $pais->id,
        'nombre' => 'Centro',
        'nivel' => 3,
        'division_padre_id' => $municipio->id,
        'tipo' => 'colonia',
    ]);

    CodigoPostal::create([
        'codigo' => '97000',
        'pais_id' => $pais->id,
        'division_admin_id' => $localidad->id,
        'datos_adicionales' => ['asentamiento' => 'Centro'],
    ]);
});

test('postal suggestions return only prefix matches', function () {
    $this->getJson(route('codigos-postales.sugerencias', ['codigo' => '970']))
        ->assertOk()
        ->assertJsonPath('data.0.codigo', '97000');
});

test('postal search returns the complete location for an exact code', function () {
    $this->getJson(route('codigos-postales.buscar', ['codigo' => '97000']))
        ->assertOk()
        ->assertJsonPath('data.0.codigo', '97000')
        ->assertJsonPath('data.0.pais.codigo_iso', 'MX')
        ->assertJsonPath('data.0.divisiones_administrativas.nivel_uno.nombre', 'Yucatán')
        ->assertJsonPath('data.0.divisiones_administrativas.nivel_dos.nombre', 'Mérida')
        ->assertJsonPath('data.0.divisiones_administrativas.nivel_tres.nombre', 'Centro');
});

test('postal endpoints reject input outside their contracts', function () {
    $this->getJson(route('codigos-postales.sugerencias', ['codigo' => '97']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('codigo');

    $this->getJson(route('codigos-postales.buscar', ['codigo' => '9700']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('codigo');
});

test('postal search reports a missing exact code', function () {
    $this->getJson(route('codigos-postales.buscar', ['codigo' => '99999']))
        ->assertNotFound();
});

<?php

use App\Models\CodigoPostal;
use App\Models\DivisionAdministrativa;
use App\Models\Pais;
use App\Models\RegimenFiscal;

function clienteFiscalCatalogs(): array
{
    $pais = Pais::create([
        'nombre_es' => 'México',
        'nombre_us' => 'Mexico',
        'codigo_iso' => 'MX',
        'codigo_iso3' => 'MEX',
    ]);
    $regimenFisica = RegimenFiscal::create([
        'clave' => '612',
        'descripcion' => 'Personas Físicas con Actividades Empresariales',
        'fisica' => true,
        'moral' => false,
        'fecha_inicio_vigencia' => '2022-01-01',
        'fecha_fin_vigencia' => '2099-12-31',
    ]);
    $regimenMoral = RegimenFiscal::create([
        'clave' => '601',
        'descripcion' => 'General de Ley Personas Morales',
        'fisica' => false,
        'moral' => true,
        'fecha_inicio_vigencia' => '2022-01-01',
        'fecha_fin_vigencia' => '2099-12-31',
    ]);
    $estado = DivisionAdministrativa::create([
        'pais_id' => $pais->id,
        'nombre' => 'Yucatán',
        'codigo' => '31',
        'nivel' => 1,
        'tipo' => 'estado',
    ]);
    $municipio = DivisionAdministrativa::create([
        'pais_id' => $pais->id,
        'nombre' => 'Mérida',
        'codigo' => '050',
        'nivel' => 2,
        'division_padre_id' => $estado->id,
        'tipo' => 'municipio',
    ]);
    $colonia = DivisionAdministrativa::create([
        'pais_id' => $pais->id,
        'nombre' => 'Centro',
        'codigo' => '0001',
        'nivel' => 3,
        'division_padre_id' => $municipio->id,
        'tipo' => 'colonia',
    ]);
    $codigoPostal = CodigoPostal::create([
        'codigo' => '97000',
        'pais_id' => $pais->id,
        'division_admin_id' => $colonia->id,
    ]);

    return compact(
        'pais',
        'regimenFisica',
        'regimenMoral',
        'estado',
        'municipio',
        'colonia',
        'codigoPostal',
    );
}

function clientePayload(array $catalogs, array $fiscalOverrides = []): array
{
    return [
        'primer_nombre' => 'Ana',
        'apellido_paterno' => 'García',
        'fecha_nacimiento' => '1991-04-18',
        'pais_nacimiento_id' => $catalogs['pais']->id,
        'telefono_codigo_pais' => '+52',
        'telefono' => '9991234567',
        'email' => 'ana@example.test',
        'sexo' => 'femenino',
        'datos_fiscales' => array_merge([
            'tipo_persona' => 'fisica',
            'regimen_fiscal_id' => $catalogs['regimenFisica']->id,
            'curp' => 'GALA910418MDFRPN01',
            'rfc' => 'gala910418ab8',
        ], $fiscalOverrides),
        'direcciones' => [[
            'tipo' => 'personal',
            'pais_id' => $catalogs['pais']->id,
            'codigo_postal' => '97000',
            'codigo_postal_id' => $catalogs['codigoPostal']->id,
            'linea_uno' => 'Calle 60 500',
            'division_admin_uno_id' => $catalogs['estado']->id,
            'division_admin_dos_id' => $catalogs['municipio']->id,
            'division_admin_tres_id' => $catalogs['colonia']->id,
            'coordenadas' => ['lat' => 20.9674, 'lng' => -89.5926],
        ]],
    ];
}

test('client creation normalizes and validates RFC according to person type', function () {
    $catalogs = clienteFiscalCatalogs();
    $user = actingAsSuperAdmin();

    $this->actingAs($user)
        ->post(route('clientes.store'), clientePayload($catalogs))
        ->assertRedirect();

    $this->assertDatabaseHas('clientes_datos_fiscales', [
        'tipo_persona' => 'fisica',
        'rfc' => 'GALA910418AB8',
    ]);
});

test('client creation rejects invalid RFC check digit', function () {
    $catalogs = clienteFiscalCatalogs();
    $user = actingAsSuperAdmin();

    $this->actingAs($user)
        ->from(route('clientes.create'))
        ->post(route('clientes.store'), clientePayload($catalogs, [
            'rfc' => 'GALA910418AB9',
        ]))
        ->assertRedirect(route('clientes.create'))
        ->assertSessionHasErrors('datos_fiscales.rfc');
});

test('client creation rejects a fiscal regime incompatible with person type', function () {
    $catalogs = clienteFiscalCatalogs();
    $user = actingAsSuperAdmin();

    $this->actingAs($user)
        ->from(route('clientes.create'))
        ->post(route('clientes.store'), clientePayload($catalogs, [
            'regimen_fiscal_id' => $catalogs['regimenMoral']->id,
        ]))
        ->assertRedirect(route('clientes.create'))
        ->assertSessionHasErrors('datos_fiscales.regimen_fiscal_id');
});

test('client creation explains that generic RFCs are not accepted while allowing a foreign CURP', function () {
    $catalogs = clienteFiscalCatalogs();
    $user = actingAsSuperAdmin();

    $response = $this->actingAs($user)
        ->from(route('clientes.create'))
        ->post(route('clientes.store'), clientePayload($catalogs, [
            'curp' => 'EXTRANJERO-SIN-CURP',
            'rfc' => 'XEXX010101000',
        ]))
        ->assertRedirect(route('clientes.create'))
        ->assertSessionHasErrors('datos_fiscales.rfc')
        ->assertSessionDoesntHaveErrors('datos_fiscales.curp');

    expect($response->getSession()->get('errors')->first('datos_fiscales.rfc'))
        ->toBe('Kronik requiere el RFC propio del cliente; no se admiten RFC genéricos.');
});

test('client creation rejects a postal code that does not match the locality', function () {
    $catalogs = clienteFiscalCatalogs();
    $user = actingAsSuperAdmin();
    $payload = clientePayload($catalogs);
    $payload['direcciones'][0]['codigo_postal'] = '97306';

    $this->actingAs($user)
        ->from(route('clientes.create'))
        ->post(route('clientes.store'), $payload)
        ->assertRedirect(route('clientes.create'))
        ->assertSessionHasErrors('direcciones.0.codigo_postal_id');
});

test('client validation messages are readable in Spanish', function () {
    $catalogs = clienteFiscalCatalogs();
    $user = actingAsSuperAdmin();
    $payload = clientePayload($catalogs);
    $payload['pais_nacimiento_id'] = 'desconocido';
    $payload['sexo'] = 'otro';

    $response = $this->actingAs($user)
        ->from(route('clientes.create'))
        ->post(route('clientes.store'), $payload)
        ->assertRedirect(route('clientes.create'))
        ->assertSessionHasErrors(['pais_nacimiento_id', 'sexo']);

    $messages = collect($response->getSession()->get('errors')->all())
        ->flatten()
        ->implode(' ');

    expect($messages)
        ->not->toContain('validation.')
        ->toContain('número entero')
        ->toContain('no es válido');
});

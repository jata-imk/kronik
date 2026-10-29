<?php

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

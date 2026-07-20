<?php

use App\Models\Cliente;
use App\Services\ClienteExpedienteService;
use Inertia\Testing\AssertableInertia as Assert;

test('super admin can view and update a client KYC profile', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();
    app(ClienteExpedienteService::class)->initializeChecklist($cliente);

    $this->actingAs($user)
        ->get(route('clientes.expediente.show', $cliente))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Clientes/Expediente')
            ->where('cliente.id', $cliente->id)
            ->where('resumen.documentos_requeridos', 4)
            ->where('resumen.documentos_recibidos', 0)
        );

    $this->actingAs($user)
        ->patch(route('clientes.expediente.perfil.update', $cliente), [
            'ocupacion' => 'Ingeniera civil',
            'actividad_economica' => 'Construccion especializada',
            'ingresos_mensuales' => 52000.50,
            'egresos_mensuales' => 21750,
            'origen_recursos' => 'Servicios profesionales independientes.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'ocupacion' => 'Ingeniera civil',
        'ingresos_mensuales' => 52000.50,
        'egresos_mensuales' => 21750,
    ]);
});

test('KYC profile rejects negative declared amounts', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();

    $this->actingAs($user)
        ->from(route('clientes.expediente.show', $cliente))
        ->patch(route('clientes.expediente.perfil.update', $cliente), [
            'ocupacion' => null,
            'actividad_economica' => null,
            'ingresos_mensuales' => -1,
            'egresos_mensuales' => -10,
            'origen_recursos' => null,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['ingresos_mensuales', 'egresos_mensuales']);
});

test('complete dossier relationships can be created updated and removed', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();
    $vinculado = Cliente::factory()->create();

    $this->actingAs($user)
        ->post(route('clientes.referencias.store', $cliente), [
            'tipo' => 'laboral',
            'nombre' => 'Laura Gomez',
            'empresa' => 'Constructora Demo',
            'puesto' => 'Recursos Humanos',
            'telefono_codigo_pais' => '+52',
            'telefono' => '5512345678',
            'email' => 'laura@example.test',
            'notas' => null,
        ])
        ->assertRedirect();

    $referencia = $cliente->referencias()->firstOrFail();
    $this->actingAs($user)
        ->put(route('clientes.referencias.update', [$cliente, $referencia]), [
            'tipo' => 'laboral',
            'nombre' => 'Laura Gomez Actualizada',
            'empresa' => 'Constructora Demo',
            'puesto' => 'Direccion',
            'telefono_codigo_pais' => '+52',
            'telefono' => '5512345678',
            'email' => null,
            'notas' => null,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('clientes.vinculos.store', $cliente), [
            'cliente_vinculado_id' => $vinculado->id,
            'rol' => 'aval',
            'notas' => 'Aval principal',
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('clientes.garantias.store', $cliente), [
            'propietario_cliente_id' => $vinculado->id,
            'tipo' => 'prendaria',
            'descripcion' => 'Vehiculo de carga',
            'valor_estimado' => 350000,
            'moneda' => 'MXN',
            'notas' => null,
        ])
        ->assertRedirect();

    $garantia = $cliente->garantias()->firstOrFail();
    $this->actingAs($user)
        ->put(route('clientes.garantias.update', [$cliente, $garantia]), [
            'propietario_cliente_id' => $vinculado->id,
            'tipo' => 'prendaria',
            'descripcion' => 'Vehiculo de carga actualizado',
            'valor_estimado' => 365000,
            'moneda' => 'MXN',
            'notas' => 'Valor revisado',
        ])
        ->assertRedirect();

    expect($referencia->fresh()->nombre)->toBe('Laura Gomez Actualizada')
        ->and($cliente->vinculos()->first()->cliente_vinculado_id)->toBe($vinculado->id)
        ->and($garantia->fresh()->valor_estimado)->toBe('365000.00');
});

test('client links reject self references and duplicates', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();
    $vinculado = Cliente::factory()->create();

    $route = route('clientes.vinculos.store', $cliente);

    $this->actingAs($user)->post($route, [
        'cliente_vinculado_id' => $cliente->id,
        'rol' => 'aval',
    ])->assertSessionHasErrors('cliente_vinculado_id');

    $this->actingAs($user)->post($route, [
        'cliente_vinculado_id' => $vinculado->id,
        'rol' => 'aval',
    ])->assertRedirect();

    $this->actingAs($user)->post($route, [
        'cliente_vinculado_id' => $vinculado->id,
        'rol' => 'aval',
    ])->assertSessionHasErrors('cliente_vinculado_id');
});

test('users without client permissions cannot open the dossier', function () {
    $user = App\Models\User::factory()->withPersonalTeam()->create();
    $cliente = Cliente::factory()->create();

    $this->actingAs($user)
        ->get(route('clientes.expediente.show', $cliente))
        ->assertForbidden();
});

test('authenticated client API keeps KYC fields in sync', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create([
        'ocupacion' => null,
        'ingresos_mensuales' => null,
    ]);

    $this->actingAs($user)
        ->patchJson(route('api.clientes.update', $cliente), [
            'ocupacion' => 'Comerciante',
            'actividad_economica' => 'Comercio minorista',
            'ingresos_mensuales' => 41000,
            'egresos_mensuales' => 19000,
            'origen_recursos' => 'Ventas del negocio propio.',
        ])
        ->assertOk()
        ->assertJsonPath('data.ocupacion', 'Comerciante')
        ->assertJsonPath('data.ingresos_mensuales', '41000.00');

    expect($cliente->fresh()->actividad_economica)->toBe('Comercio minorista');
});

<?php

use App\Models\Sucursal;
use Inertia\Testing\AssertableInertia as Assert;

test('super admin can see branches', function () {
    $user = actingAsSuperAdmin();

    Sucursal::create([
        'nombre' => 'Matriz',
        'clave' => 'MATRIZ',
        'activa' => true,
    ]);

    $this->actingAs($user)
        ->get(route('admin.sucursales.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Sucursales/Index')
            ->has('sucursales', 1)
        );
});

test('super admin can create and update branches', function () {
    $user = actingAsSuperAdmin();

    $payload = [
        'nombre' => 'Sucursal Norte',
        'clave' => 'NORTE',
        'domicilio' => [
            'calle' => 'Calle 1',
            'municipio' => 'Merida',
            'estado' => 'Yucatan',
            'codigo_postal' => '97000',
        ],
        'telefono' => '+529991234567',
        'email' => 'norte@example.test',
        'horario' => [
            'lunes_viernes' => '09:00-18:00',
            'sabado' => '09:00-14:00',
        ],
        'prefijo_folio' => 'NOR',
        'consecutivo_solicitud' => 1,
        'consecutivo_contrato' => 1,
        'consecutivo_credito' => 1,
        'consecutivo_recibo' => 1,
        'activa' => true,
    ];

    $this->actingAs($user)
        ->post(route('admin.sucursales.store'), $payload)
        ->assertRedirect();

    $sucursal = Sucursal::where('clave', 'NORTE')->firstOrFail();

    expect($sucursal->nombre)->toBe('Sucursal Norte')
        ->and($sucursal->domicilio['municipio'])->toBe('Merida');

    $this->actingAs($user)
        ->put(route('admin.sucursales.update', $sucursal), [
            ...$payload,
            'nombre' => 'Sucursal Norte Actualizada',
            'consecutivo_credito' => 25,
        ])
        ->assertRedirect();

    expect($sucursal->fresh()->nombre)->toBe('Sucursal Norte Actualizada')
        ->and($sucursal->fresh()->consecutivo_credito)->toBe(25);
});

test('super admin can deactivate branches', function () {
    $user = actingAsSuperAdmin();

    $sucursal = Sucursal::create([
        'nombre' => 'Sucursal Sur',
        'clave' => 'SUR',
        'activa' => true,
    ]);

    $this->actingAs($user)
        ->delete(route('admin.sucursales.destroy', $sucursal))
        ->assertRedirect();

    expect($sucursal->fresh()->activa)->toBeFalse();
});

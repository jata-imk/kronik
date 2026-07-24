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
            'pais_id' => 1,
            'pais_codigo_iso' => 'MX',
            'codigo_postal_id' => 10,
            'division_admin_uno_id' => 11,
            'division_admin_dos_id' => 12,
            'division_admin_tres_id' => 13,
            'pais' => 'México',
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
        ->and($sucursal->domicilio['municipio'])->toBe('Merida')
        ->and($sucursal->domicilio['codigo_postal_id'])->toBe(10)
        ->and($sucursal->domicilio['division_admin_tres_id'])->toBe(13);

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

    $this->assertDatabaseHas(config('activitylog.table_name'), [
        'subject_type' => Sucursal::class,
        'subject_id' => $sucursal->id,
        'event' => 'sucursal.deactivated',
        'description' => 'Sucursal desactivada',
    ]);
});

test('branch key must be unique with a clear validation message', function () {
    $user = actingAsSuperAdmin();
    Sucursal::create(['nombre' => 'Matriz', 'clave' => 'MATRIZ', 'activa' => true]);

    $this->actingAs($user)
        ->from(route('admin.sucursales.index'))
        ->post(route('admin.sucursales.store'), [
            'nombre' => 'Otra matriz',
            'clave' => 'MATRIZ',
            'consecutivo_solicitud' => 1,
            'consecutivo_contrato' => 1,
            'consecutivo_credito' => 1,
            'consecutivo_recibo' => 1,
            'activa' => true,
        ])
        ->assertSessionHasErrors(['clave' => 'La clave de sucursal ya está en uso.']);
});

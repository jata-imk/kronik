<?php

use App\Models\CodigoPostal;
use App\Models\DivisionAdministrativa;
use App\Models\Pais;
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

    $pais = Pais::create([
        'nombre_es' => 'México',
        'nombre_us' => 'Mexico',
        'codigo_iso' => 'MX',
        'codigo_iso3' => 'MEX',
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

    $payload = [
        'nombre' => 'Sucursal Norte',
        'clave' => 'NORTE',
        'domicilio' => [
            'calle' => 'Calle 1',
            'municipio' => 'Merida',
            'estado' => 'Yucatan',
            'codigo_postal' => '97000',
            'pais_id' => $pais->id,
            'pais_codigo_iso' => 'MX',
            'codigo_postal_id' => $codigoPostal->id,
            'division_admin_uno_id' => $estado->id,
            'division_admin_dos_id' => $municipio->id,
            'division_admin_tres_id' => $colonia->id,
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
        ->and($sucursal->domicilio['codigo_postal_id'])->toBe($codigoPostal->id)
        ->and($sucursal->domicilio['division_admin_tres_id'])->toBe($colonia->id);

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

test('branch postal code requires a selected locality', function () {
    $user = actingAsSuperAdmin();

    $this->actingAs($user)
        ->from(route('admin.sucursales.index'))
        ->post(route('admin.sucursales.store'), [
            'nombre' => 'Sucursal sin colonia',
            'clave' => 'SIN-COLONIA',
            'domicilio' => ['codigo_postal' => '97000'],
            'consecutivo_solicitud' => 1,
            'consecutivo_contrato' => 1,
            'consecutivo_credito' => 1,
            'consecutivo_recibo' => 1,
            'activa' => true,
        ])
        ->assertSessionHasErrors([
            'domicilio.codigo_postal_id',
            'domicilio.division_admin_tres_id',
        ]);
});

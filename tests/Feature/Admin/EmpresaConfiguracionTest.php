<?php

use App\Models\EmpresaConfiguracion;
use App\Models\RegimenFiscal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function actingAsSuperAdminWithTeam(): User
{
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->ownedTeams()->first();

    $user->forceFill(['current_team_id' => $team->id])->save();

    Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

    if (function_exists('setPermissionsTeamId')) {
        setPermissionsTeamId($team->id);
    }

    $user->assignRole('Super Admin');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

test('super admin can see current team company configuration', function () {
    $user = actingAsSuperAdminWithTeam();

    $this->actingAs($user)
        ->get(route('admin.empresa-configuracion.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/EmpresaConfiguracion/Index')
            ->where('team.id', $user->currentTeam->id)
            ->has('configuracion')
            ->has('regimenesFiscales')
        );

    expect(EmpresaConfiguracion::where('team_id', $user->currentTeam->id)->exists())->toBeTrue();
});

test('super admin can update company configuration', function () {
    $user = actingAsSuperAdminWithTeam();
    $regimen = RegimenFiscal::create([
        'clave' => '601',
        'descripcion' => 'General de Ley Personas Morales',
        'fisica' => false,
        'moral' => true,
        'fecha_inicio_vigencia' => '2022-01-01',
        'fecha_fin_vigencia' => '2099-12-31',
    ]);

    $payload = [
        'razon_social' => 'KRONIK DEMO SA DE CV',
        'nombre_comercial' => 'Kronik Demo',
        'rfc' => 'KDE260101AB1',
        'regimen_fiscal_id' => $regimen->id,
        'email' => 'operaciones@example.test',
        'telefono' => '+525512345678',
        'sitio_web' => 'https://example.test',
        'logo_path' => '/storage/logos/kronik.png',
        'domicilio_fiscal' => [
            'calle' => 'Av. Paseo de la Reforma',
            'codigo_postal' => '06000',
            'estado' => 'Ciudad de México',
            'pais' => 'México',
        ],
        'moneda' => 'MXN',
        'zona_horaria' => 'America/Mexico_City',
        'horario_operacion' => ['lunes_viernes' => '09:00-18:00'],
        'folio_credito_prefijo' => 'KRN',
        'folio_credito_siguiente' => 1001,
        'dias_inhabiles' => ['2026-01-01'],
        'reglas_cobranza' => ['dias_gracia' => 3],
        'formatos_contrato' => ['contrato_credito_simple' => 'plantillas/contratos/credito-simple.docx'],
        'cuentas_bancarias' => [['banco' => 'Banco Demo', 'clabe' => '002010077777777771', 'uso' => 'cobranza']],
        'contactos' => [['nombre' => 'Mesa de Operaciones', 'email' => 'operaciones@example.test', 'telefono' => '+525512345678']],
        'integraciones' => [
            'circulo_credito' => ['habilitado' => false, 'env_prefix' => 'CDC'],
            'geocoding' => ['habilitado' => false, 'env_key' => 'GEOCODING_API_KEY'],
        ],
        'activa' => true,
    ];

    $this->actingAs($user)
        ->put(route('admin.empresa-configuracion.update'), $payload)
        ->assertRedirect(route('admin.empresa-configuracion.index'));

    $configuracion = EmpresaConfiguracion::where('team_id', $user->currentTeam->id)->firstOrFail();

    expect($configuracion->razon_social)->toBe('KRONIK DEMO SA DE CV')
        ->and($configuracion->activa)->toBeTrue()
        ->and($configuracion->activated_at)->not->toBeNull()
        ->and($configuracion->integraciones['circulo_credito']['env_prefix'])->toBe('CDC');

    $this->assertDatabaseHas(config('activitylog.table_name'), [
        'subject_type' => EmpresaConfiguracion::class,
        'subject_id' => $configuracion->id,
        'description' => 'Configuración de empresa actualizada',
    ]);
});

test('activation requires legal minimum data', function () {
    $user = actingAsSuperAdminWithTeam();

    $this->actingAs($user)
        ->from(route('admin.empresa-configuracion.index'))
        ->put(route('admin.empresa-configuracion.update'), [
            'razon_social' => '',
            'nombre_comercial' => '',
            'rfc' => '',
            'regimen_fiscal_id' => null,
            'email' => '',
            'telefono' => '',
            'sitio_web' => '',
            'logo_path' => '',
            'domicilio_fiscal' => [],
            'moneda' => 'MXN',
            'zona_horaria' => 'America/Mexico_City',
            'horario_operacion' => [],
            'folio_credito_prefijo' => '',
            'folio_credito_siguiente' => 1,
            'dias_inhabiles' => [],
            'reglas_cobranza' => [],
            'formatos_contrato' => [],
            'cuentas_bancarias' => [],
            'contactos' => [],
            'integraciones' => [],
            'activa' => true,
        ])
        ->assertRedirect(route('admin.empresa-configuracion.index'))
        ->assertSessionHasErrors([
            'razon_social',
            'rfc',
            'regimen_fiscal_id',
            'email',
            'domicilio_fiscal.calle',
            'domicilio_fiscal.codigo_postal',
            'domicilio_fiscal.estado',
            'domicilio_fiscal.pais',
        ]);
});

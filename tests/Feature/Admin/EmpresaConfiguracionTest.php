<?php

use App\Models\CodigoPostal;
use App\Models\DivisionAdministrativa;
use App\Models\EmpresaConfiguracion;
use App\Models\Pais;
use App\Models\RegimenFiscal;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $pais = Pais::create([
        'nombre_es' => 'México',
        'nombre_us' => 'Mexico',
        'codigo_iso' => 'MX',
        'codigo_iso3' => 'MEX',
        'emoji' => '🇲🇽',
    ]);

    RegimenFiscal::create([
        'clave' => '601',
        'descripcion' => 'General de Ley Personas Morales',
        'fisica' => false,
        'moral' => true,
        'fecha_inicio_vigencia' => '2022-01-01',
        'fecha_fin_vigencia' => '2099-12-31',
    ]);

    $estado = DivisionAdministrativa::create([
        'pais_id' => $pais->id,
        'nombre' => 'Ciudad de México',
        'codigo' => '09',
        'nivel' => 1,
        'tipo' => 'estado',
    ]);
    $municipio = DivisionAdministrativa::create([
        'pais_id' => $pais->id,
        'nombre' => 'Cuauhtémoc',
        'codigo' => '015',
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

    $this->codigoPostal = CodigoPostal::create([
        'codigo' => '06000',
        'pais_id' => $pais->id,
        'division_admin_id' => $colonia->id,
    ]);
    $this->estado = $estado;
    $this->municipio = $municipio;
    $this->colonia = $colonia;
});

test('super admin can see singleton company configuration', function () {
    $user = actingAsSuperAdmin();

    $this->actingAs($user)
        ->get(route('admin.configuracion-empresa.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ConfiguracionEmpresa/Index')
            ->has('configuracion')
            ->where('configuracion.singleton_key', 'default')
            ->has('tiposPersona', 2)
            ->has('regimenesFiscales', 1)
            ->has('paises', 1)
            ->has('zonasHorarias')
        );

    expect(EmpresaConfiguracion::where('singleton_key', 'default')->exists())->toBeTrue();
});

test('super admin can update singleton company configuration without exposing api key', function () {
    $user = actingAsSuperAdmin();

    $payload = [
        'razon_social' => 'KRONIK DEMO SA DE CV',
        'nombre_comercial' => 'Kronik Demo',
        'tipo_persona' => 'moral',
        'rfc' => 'KDE260101AB1',
        'regimen_fiscal_id' => RegimenFiscal::where('clave', '601')->value('id'),
        'domicilio_fiscal' => [
            'calle' => 'Av. Paseo de la Reforma',
            'codigo_postal' => '06000',
            'codigo_postal_id' => $this->codigoPostal->id,
            'division_admin_uno_id' => $this->estado->id,
            'division_admin_dos_id' => $this->municipio->id,
            'division_admin_tres_id' => $this->colonia->id,
            'estado' => 'Ciudad de Mexico',
        ],
        'telefono' => '+525512345678',
        'email' => 'operaciones@example.test',
        'sitio_web' => 'https://example.test',
        'moneda' => 'MXN',
        'zona_horaria' => 'America/Mexico_City',
        'pais_base' => 'MX',
        'logotipo_path' => '/storage/logos/kronik.png',
        'parametros_operativos' => [
            'dias_gracia_default' => 3,
            'hora_corte_operativo' => '18:00',
            'dias_inhabiles' => ['2026-01-01'],
            'reglas_cobranza' => ['dias_mora_notificacion' => 1],
            'formatos_contrato' => ['credito_simple' => 'plantillas/credito-simple.docx'],
            'cuentas_bancarias' => [['banco' => 'Banco Demo', 'clabe' => '002010077777777771']],
            'contactos' => [['nombre' => 'Mesa de Operaciones', 'email' => 'operaciones@example.test']],
        ],
        'integraciones' => [
            'circulo_credito_host' => 'https://services.circulodecredito.com.mx',
            'circulo_credito_sandbox' => true,
            'circulo_credito_api_key' => 'secret-api-key',
        ],
        'estatus' => 'activa',
    ];

    $this->actingAs($user)
        ->from(route('admin.configuracion-empresa.index'))
        ->put(route('admin.configuracion-empresa.update'), $payload)
        ->assertRedirect(route('admin.configuracion-empresa.index'));

    $configuracion = EmpresaConfiguracion::where('singleton_key', 'default')->firstOrFail();

    expect($configuracion->razon_social)->toBe('KRONIK DEMO SA DE CV')
        ->and($configuracion->estatus)->toBe('activa')
        ->and($configuracion->tipo_persona)->toBe('moral')
        ->and($configuracion->integraciones['circulo_credito_api_key'])->toBe('secret-api-key')
        ->and($configuracion->parametros_operativos['reglas_cobranza']['dias_mora_notificacion'])->toBe(1);

    $this->assertDatabaseHas(config('activitylog.table_name'), [
        'subject_type' => EmpresaConfiguracion::class,
        'subject_id' => $configuracion->id,
        'event' => 'empresa.updated',
        'description' => 'Configuracion de empresa actualizada',
    ]);

    $this->actingAs($user)
        ->get(route('admin.configuracion-empresa.index'))
        ->assertOk()
        ->assertDontSee('secret-api-key')
        ->assertInertia(fn (Assert $page) => $page
            ->where('configuracion.integraciones.circulo_credito_api_key_configurada', true)
        );
});

test('activation requires minimum legal data', function () {
    $user = actingAsSuperAdmin();

    $this->actingAs($user)
        ->from(route('admin.configuracion-empresa.index'))
        ->put(route('admin.configuracion-empresa.update'), [
            'razon_social' => '',
            'nombre_comercial' => '',
            'tipo_persona' => 'moral',
            'rfc' => '',
            'regimen_fiscal_id' => null,
            'domicilio_fiscal' => [],
            'telefono' => '',
            'email' => '',
            'sitio_web' => '',
            'moneda' => 'MXN',
            'zona_horaria' => 'America/Mexico_City',
            'pais_base' => 'MX',
            'logotipo_path' => '',
            'parametros_operativos' => [],
            'integraciones' => [],
            'estatus' => 'activa',
        ])
        ->assertRedirect(route('admin.configuracion-empresa.index'))
        ->assertSessionHasErrors([
            'razon_social',
            'rfc',
            'regimen_fiscal_id',
            'email',
            'domicilio_fiscal.calle',
            'domicilio_fiscal.codigo_postal',
            'domicilio_fiscal.estado',
        ]);
});

test('user without admin permissions cannot access company configuration or branches', function () {
    $user = App\Models\User::factory()->withPersonalTeam()->create();
    $team = $user->ownedTeams()->first();

    $user->forceFill(['current_team_id' => $team->id])->save();

    $this->actingAs($user)
        ->get(route('admin.configuracion-empresa.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.sucursales.index'))
        ->assertForbidden();
});

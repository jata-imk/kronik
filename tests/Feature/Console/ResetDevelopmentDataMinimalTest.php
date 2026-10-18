<?php

use App\Models\Credito;
use App\Models\CreditoPago;
use App\Models\EmpresaConfiguracion;
use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use App\Services\Development\ResetDataException;
use App\Services\Development\ResetDataFiles;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('local');
    Storage::fake('public');
});

function minimalResetOptions(User $admin): array
{
    return ['--minimal' => true, '--admin-email' => $admin->email, '--confirm-qa' => true];
}

it('preserves one administrator with credentials company catalogs and usable contexts without demos', function () {
    $this->seed(Database\Seeders\SystemSeeder::class);
    $admin = actingAsSuperAdmin();
    $teamId = $admin->current_team_id;
    $admin->forceFill(['two_factor_secret' => encrypt('qa-secret'), 'two_factor_recovery_codes' => encrypt('["qa-code"]'),
        'profile_photo_path' => 'profile-photos/admin.jpg'])->save();
    $credentials = $admin->only(['password', 'two_factor_secret', 'two_factor_recovery_codes']);
    $matriz = Sucursal::create(['nombre' => 'Matriz', 'clave' => 'MATRIZ', 'activa' => false, 'consecutivo_credito' => 42]);
    $company = EmpresaConfiguracion::create(['singleton_key' => 'default', 'razon_social' => 'Empresa QA', 'logotipo_path' => 'logos/empresa.png']);
    User::factory()->withPersonalTeam()->create(['profile_photo_path' => 'profile-photos/otro.jpg']);
    $admin->createToken('QA');
    Storage::disk('public')->put('profile-photos/admin.jpg', 'admin');
    Storage::disk('public')->put('profile-photos/otro.jpg', 'otro');
    Storage::disk('public')->put('logos/empresa.png', 'logo');
    Storage::disk('local')->put('backups/conservar.sql', 'respaldo');
    $globals = DB::table('roles')->whereNull('team_id')->get()->toJson();
    $perms = DB::table('permissions')->get()->toJson();

    $this->artisan('dev:reset-data', minimalResetOptions($admin))->assertSuccessful();
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('teams', 1);
    $this->assertDatabaseCount('sucursales', 1);
    $this->assertDatabaseCount('clientes', 0);
    $this->assertDatabaseCount('sic_queries', 0);
    $this->assertDatabaseCount('personal_access_tokens', 0);
    expect($admin->fresh()->only(array_keys($credentials)))->toBe($credentials)
        ->and(Hash::check('password', $admin->fresh()->password))->toBeTrue()
        ->and($admin->fresh()->remember_token)->toBeNull()
        ->and($admin->fresh()->is_super_admin)->toBeTrue()
        ->and($admin->fresh()->current_team_id)->toBe($teamId)
        ->and($admin->fresh()->current_sucursal_id)->toBe($matriz->id)
        ->and($admin->fresh()->sucursal_principal_id)->toBe($matriz->id)
        ->and($admin->fresh()->sucursales->modelKeys())->toBe([$matriz->id])
        ->and($matriz->fresh()->activa)->toBeTrue()
        ->and($matriz->fresh()->consecutivo_credito)->toBe(42)
        ->and($company->fresh()->razon_social)->toBe('Empresa QA')
        ->and(DB::table('roles')->whereNull('team_id')->get()->toJson())->toBe($globals)
        ->and(DB::table('permissions')->get()->toJson())->toBe($perms);
    Storage::disk('public')->assertExists(['profile-photos/admin.jpg', 'logos/empresa.png']);
    Storage::disk('public')->assertMissing('profile-photos/otro.jpg');
    Storage::disk('local')->assertExists('backups/conservar.sql');
    $run = DB::table('development_data_resets')->first();
    expect($run->completed_at)->not->toBeNull()
        ->and((int) $run->file_count)->toBe(1)
        ->and(Crypt::decryptString($run->pending_files))->toBe('[]');
    // A second reset preserves the same IDs and adds a separate maintenance record.
    $this->artisan('dev:reset-data', minimalResetOptions($admin))->assertSuccessful();
    $this->assertDatabaseCount('development_data_resets', 2);
    expect(Team::first()->id)->toBe($teamId)->and(Sucursal::first()->id)->toBe($matriz->id);
});

it('creates only the missing minimum contexts without changing the administrator password', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->artisan('dev:reset-data', minimalResetOptions($admin))->assertSuccessful();
    expect(Team::first()->user_id)->toBe($admin->id)
        ->and(Team::first()->name)->toBe('Administración')
        ->and(Sucursal::first()->clave)->toBe('MATRIZ');
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('productos_crediticios', 0);
    $this->assertDatabaseCount('documento_plantillas', 0);
});

it('previews without changing database files or sessions', function () {
    $admin = actingAsSuperAdmin();
    $other = User::factory()->create(['profile_photo_path' => 'profile-photos/otro.jpg']);
    Storage::disk('public')->put('profile-photos/otro.jpg', 'otro');
    $before = $admin->fresh()->getAttributes();
    $this->artisan('dev:reset-data', ['--minimal' => true, '--admin-email' => $admin->email, '--dry-run' => true])
        ->expectsOutputToContain('No se modificó ningún registro ni archivo.')->assertSuccessful();
    expect($admin->fresh()->getAttributes())->toBe($before)->and($other->fresh())->not->toBeNull();
    $this->assertDatabaseCount('development_data_resets', 0);
    Storage::disk('public')->assertExists('profile-photos/otro.jpg');
});

it('refuses missing confirmation and invalid administrator selection', function (string $case) {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $options = minimalResetOptions($admin);
    match ($case) {
        'confirmation' => $options['--confirm-qa'] = false,
        'missing' => $options['--admin-email'] = 'missing@example.test',
        'not-global' => $admin->forceFill(['is_super_admin' => false])->save(),
        'inactive' => $admin->forceFill(['status' => 'inactive'])->save(),
        'unverified' => $admin->forceFill(['email_verified_at' => null])->save(),
        'incompatible' => $options['--no-seed'] = true,
    };
    $this->artisan('dev:reset-data', $options)->assertFailed();
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('development_data_resets', 0);
})->with(['confirmation', 'missing', 'not-global', 'inactive', 'unverified', 'incompatible']);

it('requires maintenance outside local and testing and forbids demo accounts in production', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    app()->detectEnvironment(fn () => 'production');
    $maintenance = Mockery::mock(Illuminate\Contracts\Foundation\MaintenanceMode::class);
    $maintenance->shouldReceive('active')->andReturn(false, true);
    app()->instance(Illuminate\Contracts\Foundation\MaintenanceMode::class, $maintenance);
    $this->artisan('dev:reset-data', minimalResetOptions($admin))
        ->expectsOutputToContain('Activa el modo mantenimiento')->assertFailed();
    $this->artisan('dev:reset-data', ['--confirm-qa' => true])
        ->expectsOutputToContain('solo se admite --minimal')->assertFailed();
    $this->assertDatabaseCount('users', 1);
});

it('resets without seed when explicitly requested and preserves reference configuration', function () {
    $this->seed(Database\Seeders\SystemSeeder::class);
    User::factory()->withPersonalTeam()->create();
    $permissions = DB::table('permissions')->count();
    $this->artisan('dev:reset-data', ['--no-seed' => true, '--confirm-qa' => true])->assertSuccessful();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('teams', 0);
    $this->assertDatabaseCount('sucursales', 0);
    $this->assertDatabaseCount('permissions', $permissions);
});

it('rolls back database mutations and leaves files untouched if the database reset fails', function () {
    $admin = actingAsSuperAdmin();
    $other = User::factory()->create(['profile_photo_path' => 'profile-photos/otro.jpg']);
    Storage::disk('public')->put('profile-photos/otro.jpg', 'otro');
    $fail = true;
    DB::listen(function ($query) use (&$fail) {
        if ($fail && str_starts_with(strtolower($query->sql), 'delete from') && str_contains($query->sql, 'users')) {
            $fail = false;
            throw new ResetDataException('Fallo simulado de base de datos.');
        }
    });
    $this->artisan('dev:reset-data', minimalResetOptions($admin))->assertFailed();
    expect($admin->fresh()->current_sucursal_id)->toBe($admin->current_sucursal_id)
        ->and($other->fresh())->not->toBeNull();
    $this->assertDatabaseCount('development_data_resets', 0);
    Storage::disk('public')->assertExists('profile-photos/otro.jpg');
});

it('retains a recoverable encrypted file manifest when storage deletion fails', function () {
    $admin = actingAsSuperAdmin();
    User::factory()->create(['profile_photo_path' => 'profile-photos/otro.jpg']);
    Storage::disk('public')->put('profile-photos/otro.jpg', 'otro');
    $this->partialMock(ResetDataFiles::class, function ($mock) {
        $mock->shouldReceive('delete')->once()->andReturn(false);
    });
    $this->artisan('dev:reset-data', minimalResetOptions($admin))->expectsOutputToContain('archivos pendientes')->assertFailed();
    $run = DB::table('development_data_resets')->first();
    expect($run->completed_at)->toBeNull()->and($run->pending_files)->not->toContain('otro.jpg');
    $this->assertDatabaseCount('users', 1);
    $this->artisan('dev:reset-data', minimalResetOptions($admin))->expectsOutputToContain('limpieza pendiente')->assertFailed();
    app()->forgetInstance(ResetDataFiles::class);
    $this->artisan('dev:reset-data', ['--resume-cleanup' => $run->id, '--confirm-qa' => true])->assertSuccessful();
    Storage::disk('public')->assertMissing('profile-photos/otro.jpg');
    expect(DB::table('development_data_resets')->first()->completed_at)->not->toBeNull();
    $this->assertDatabaseCount('development_data_resets', 1);
});

it('refuses unsafe file paths before deleting anything', function (string $path) {
    $admin = actingAsSuperAdmin();
    User::factory()->create(['profile_photo_path' => $path]);
    $this->artisan('dev:reset-data', minimalResetOptions($admin))->expectsOutputToContain('fuera de las rutas previstas')->assertFailed();
    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseCount('development_data_resets', 0);
})->with(['../outside.jpg', 'profile-photos/../../outside.jpg', 'logos/empresa.png', 'profile-photos/sub/archivo.jpg']);

it('refuses unclassified tables so new modules cannot silently survive a reset', function () {
    $admin = actingAsSuperAdmin();
    $schema = Illuminate\Support\Facades\Schema::getFacadeRoot();
    $tables = $schema->getTableListing();
    Illuminate\Support\Facades\Schema::partialMock()->shouldReceive('getTableListing')->once()->andReturn([...$tables, 'nuevo_modulo']);
    $this->artisan('dev:reset-data', minimalResetOptions($admin))->expectsOutputToContain('nuevo_modulo')->assertFailed();
    $this->assertDatabaseCount('users', 1);
});

it('refuses external session queue or activity stores before resetting users', function (array $configuration) {
    $admin = actingAsSuperAdmin();
    config($configuration);
    $this->artisan('dev:reset-data', minimalResetOptions($admin))->assertFailed();
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('development_data_resets', 0);
})->with([
    'file sessions' => [['session.driver' => 'file']],
    'external session database' => [['session.driver' => 'database', 'session.connection' => 'external']],
    'redis queue' => [['queue.default' => 'redis']],
    'external queue database' => [['queue.default' => 'database', 'queue.connections.database.connection' => 'external']],
    'external batches' => [['queue.batching.database' => 'external']],
    'external failures' => [['queue.failed.database' => 'external']],
    'external activity' => [['activitylog.database_connection' => 'external']],
]);

it('permits the minimal reset in a production configured instance explicitly declared QA under maintenance', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    app()->detectEnvironment(fn () => 'production');
    $maintenance = Mockery::mock(Illuminate\Contracts\Foundation\MaintenanceMode::class);
    $maintenance->shouldReceive('active')->andReturn(true);
    app()->instance(Illuminate\Contracts\Foundation\MaintenanceMode::class, $maintenance);
    config(['session.driver' => 'database']);
    $this->artisan('dev:reset-data', minimalResetOptions($admin))->assertSuccessful();
    expect($admin->fresh()->is_super_admin)->toBeTrue();
    $this->assertDatabaseCount('users', 1);
});

it('removes a full credit cycle with prepayment reversal SIC evidence templates and private files with foreign keys enabled', function () {
    [$admin, $solicitud, $paquete, , $desembolso] = prepararDesembolsoQa($this, pagos: true);
    $this->post(route('solicitudes.desembolso.store', $solicitud), $desembolso)->assertSessionHasNoErrors();
    $credito = Credito::firstOrFail();
    config(['originacion.anticipos_qa_habilitados' => true, 'originacion.pagos_qa_habilitados' => true]);
    $data = ['operacion' => 'prepago', 'fecha_pago' => $desembolso['fecha_desembolso'], 'importe' => '2500.00',
        'referencia' => 'ANTICIPO-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];
    $data['previa_hash'] = $this->postJson(route('creditos.anticipos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.anticipos.store', $credito), $data)->assertSessionHasNoErrors();
    $pago = CreditoPago::firstOrFail();
    $this->post(route('creditos.pagos.reverse', [$credito, $pago]), ['motivo' => 'Reverso QA previo a limpieza.',
        'confirmacion_qa' => true, 'idempotency_key' => (string) Str::uuid()])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('credito_pagos', 2);
    $this->assertDatabaseCount('credito_cronogramas', 3);

    // The workflow fixture uses short fake paths; move them to the actual application layouts.
    $docPath = 'clientes/'.$credito->cliente_id.'/documentos/ine.pdf';
    Storage::disk('local')->move('prueba/ine.pdf', $docPath);
    DB::table('cliente_documentos')->update(['path' => $docPath]);
    $generated = $paquete->documento;
    $pdfPath = 'documentos-generados/'.$credito->cliente_id.'/'.$generated->id.'.pdf';
    Storage::disk('local')->move('qa/original.pdf', $pdfPath);
    DB::table('documentos_generados')->where('id', $generated->id)->update(['path' => $pdfPath]);
    $consentPath = 'clientes/'.$credito->cliente_id.'/consentimientos-sic/consentimiento.pdf';
    Storage::disk('local')->put($consentPath, 'QA');
    $consent = App\Models\ClienteConsentimientoSic::create(['cliente_id' => $credito->cliente_id, 'registrado_por' => $admin->id,
        'medio' => 'firma_autografa', 'otorgado_en' => now(), 'evidencia_disk' => 'local', 'evidencia_path' => $consentPath,
        'evidencia_nombre_original' => 'consentimiento.pdf', 'evidencia_mime_type' => 'application/pdf', 'evidencia_tamano_bytes' => 2]);
    $this->seed(Database\Seeders\SicsSeeder::class);
    $api = App\Models\SicApi::firstOrFail();
    $query = App\Models\SicQuery::create(['cliente_id' => $credito->cliente_id, 'sic_id' => $api->sic_id, 'sic_api_id' => $api->id,
        'fecha_consulta' => now(), 'status' => 'success', 'ambiente' => 'qa', 'actor_id' => $admin->id,
        'team_id' => $admin->current_team_id, 'sucursal_id' => $credito->sucursal_id, 'consentimiento_id' => $consent->id,
        'solicitud_revision_id' => $solicitud->revisiones()->first()->id]);
    App\Models\SicEvidencia::create(['sic_query_id' => $query->id, 'tipo' => 'respuesta', 'contenido' => ['qa' => true], 'contenido_hash' => str_repeat('a', 64)]);
    DB::table('sic_eventos')->insert(['sic_query_id' => $query->id, 'estado' => 'completada', 'actor_id' => $admin->id]);
    DB::table('sic_query_results')->insert(['sic_query_id' => $query->id, 'tipo_registro' => 'score', 'data' => '{"score":700}']);
    $resourceId = (string) Str::uuid();
    $resourcePath = 'documento-recursos/'.$resourceId.'.png';
    Storage::disk('local')->put($resourcePath, 'QA');
    DB::table('documento_recursos')->insert(['id' => $resourceId, 'nombre' => 'QA', 'disk' => 'local', 'path' => $resourcePath,
        'mime_type' => 'image/png', 'ancho' => 1, 'alto' => 1, 'tamano_bytes' => 2, 'archivo_hash' => str_repeat('b', 64), 'creado_por' => $admin->id]);
    DB::table('documento_version_recursos')->insert(['version_id' => $paquete->documento_plantilla_version_id, 'recurso_id' => $resourceId]);
    $firmaPath = DB::table('solicitud_firmas')->value('path');
    $regimen = DB::table('regimenes_fiscales')->first();

    $this->artisan('dev:reset-data', minimalResetOptions($admin))->assertSuccessful();
    foreach (['creditos', 'credito_pagos', 'credito_cronogramas', 'credito_desembolsos', 'credito_movimientos',
        'solicitud_formalizaciones', 'solicitud_firmas', 'solicitud_paquetes', 'solicitud_dictamenes', 'solicitud_revisiones',
        'solicitud_resoluciones', 'solicitud_eventos', 'solicitudes', 'sic_queries', 'sic_evidencias', 'sic_eventos', 'sic_query_results',
        'documentos_generados', 'documento_recursos', 'documento_version_recursos', 'documento_plantillas', 'documento_plantilla_versiones',
        'originacion_politicas', 'producto_version_usos', 'producto_versiones', 'productos_crediticios', 'clientes', 'cliente_documentos',
        'cliente_consentimientos_sic', 'activity_log'] as $table) {
        $this->assertDatabaseCount($table, 0);
    }
    Storage::disk('local')->assertMissing([$docPath, $pdfPath, $consentPath, $resourcePath, $firmaPath]);
    $this->assertDatabaseHas('regimenes_fiscales', ['id' => $regimen->id, 'clave' => $regimen->clave]);
    // FK enforcement still rejects orphans after a successful reset.
    expect(fn () => DB::table('sucursal_user')->insert(['user_id' => $admin->id, 'sucursal_id' => 999999]))
        ->toThrow(Illuminate\Database\QueryException::class);
});

it('refuses non QA financial or SIC records before any deletion', function (string $surface) {
    [$admin, $solicitud, , , $desembolso] = prepararDesembolsoQa($this, pagos: true);
    $this->post(route('solicitudes.desembolso.store', $solicitud), $desembolso)->assertSessionHasNoErrors();
    // Canonical paths are not relevant: guards must run before collecting any files.
    if ($surface === 'sic') {
        $this->seed(Database\Seeders\SicsSeeder::class);
        $api = App\Models\SicApi::firstOrFail();
        DB::table('sic_queries')->insert(['cliente_id' => $solicitud->cliente_id, 'sic_id' => $api->sic_id,
            'sic_api_id' => $api->id, 'fecha_consulta' => now(), 'ambiente' => 'production']);
    } else {
        DB::table($surface)->update(['modo' => 'real']);
    }
    $this->artisan('dev:reset-data', minimalResetOptions($admin))->expectsOutputToContain('fuera de QA')->assertFailed();
    $this->assertDatabaseCount('creditos', 1);
    $this->assertDatabaseCount('development_data_resets', 0);
})->with(['creditos', 'solicitud_formalizaciones', 'sic']);

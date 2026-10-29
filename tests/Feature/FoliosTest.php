<?php

use App\Enums\TipoFolio;
use App\Models\Activity;
use App\Models\Cliente;
use App\Models\Solicitud;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\FolioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->withoutVite());

test('los drivers de producción registran el manejo de conflictos snapshot', function () {
    expect(DB::connection('mysql'))->toBeInstanceOf(\App\Database\MySqlConnection::class)
        ->and(DB::connection('mariadb'))->toBeInstanceOf(\App\Database\MariaDbConnection::class);
});

function datosSolicitudFolio(User $actor, ?Cliente $cliente = null): array
{
    $cliente ??= Cliente::factory()->create(['sucursal_id' => $actor->current_sucursal_id]);

    return ['cliente_id' => $cliente->id, 'sucursal_id' => $cliente->sucursal_id, 'creada_por' => $actor->id,
        'responsable_id' => $actor->id, 'clave_creacion' => (string) Str::uuid(), 'creacion_hash' => str_repeat('a', 64)];
}

test('asigna folios transaccionales y nunca consume números por vista previa o rollback', function () {
    $actor = actingAsSuperAdmin();
    $sucursal = $actor->currentSucursal;
    $service = app(FolioService::class);
    $config = $service->configuracion($sucursal);
    $this->assertDatabaseCount('folio_asignaciones', 0);
    expect(fn () => DB::transaction(function () use ($actor) {
        Solicitud::create(datosSolicitudFolio($actor));
        throw new RuntimeException('Simulación de fallo transaccional.');
    }))->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('folio_asignaciones', 0);
    $solicitud = Solicitud::create(datosSolicitudFolio($actor));
    expect($solicitud->folio)->toBe($config['prefijo_folio'].'-SOL-000001');
    expect(fn () => $solicitud->update(['folio' => 'CAMBIO-SOL-000999']))->toThrow(ValidationException::class);
    expect(fn () => $solicitud->fresh()->delete())->toThrow(ValidationException::class);
    $this->assertDatabaseCount('folio_asignaciones', 1);
});

test('los cuatro folios llegan a UI documentos y recibos sin duplicarse en reintentos', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $solicitud = $credito->solicitud;
    $paquete = \App\Models\SolicitudPaquete::sole();
    expect($solicitud->folio)->toContain('-SOL-000001')
        ->and($paquete->folio)->toContain('-CTO-000001')
        ->and($credito->folio)->toContain('-CRE-000001')
        ->and($paquete->snapshot['solicitud_folio'])->toBe($solicitud->folio)
        ->and($paquete->snapshot['contrato_folio'])->toBe($paquete->folio);
    $this->assertDatabaseCount('folio_asignaciones', 3);
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $this->assertDatabaseCount('folio_asignaciones', 3);
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    $pago = $credito->pagos()->sole();
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    expect($pago->folio)->toContain('-REC-000001');
    $this->assertDatabaseCount('folio_asignaciones', 4);
    $this->get(route('creditos.pagos.show', [$credito, $pago]))->assertInertia(fn (Assert $page) => $page
        ->where('pago.folio', $pago->folio)->where('credito.folio', $credito->folio));
    $this->get(route('solicitudes.paquete.show', $solicitud))->assertInertia(fn (Assert $page) => $page->where('actual.folio', $paquete->folio));
    $this->get(route('solicitudes.index', ['buscar' => $solicitud->folio]))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 1));
    $this->get(route('creditos.index', ['buscar' => $credito->folio]))->assertInertia(fn (Assert $page) => $page->where('creditos.total', 1));
    $this->post(route('creditos.pagos.reverse', [$credito, $pago]), ['motivo' => 'Corrección sintética para verificar números de recibos.',
        'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true])->assertSessionHasNoErrors();
    expect($pago->fresh()->folio)->toBe($pago->folio)->and($pago->reverso->folio)->toContain('-REC-000002');
});

test('cambiar prefijo afecta solo futuros y conserva todos los prefijos históricos reservados', function () {
    $actor = actingAsSuperAdmin();
    $service = app(FolioService::class);
    $primera = Solicitud::create(datosSolicitudFolio($actor));
    $sucursal = $actor->currentSucursal->fresh();
    $config = $service->configuracion($sucursal);
    $service->configurar($sucursal, [...$config, 'prefijo_folio' => 'NUEVO'], $actor);
    $segunda = Solicitud::create(datosSolicitudFolio($actor));
    expect($primera->fresh()->folio)->toBe($primera->folio)->and($segunda->folio)->toBe('NUEVO-SOL-000002');
    $otra = Sucursal::create(['nombre' => 'Otra', 'clave' => 'OTRA', 'prefijo_folio' => $config['prefijo_folio'], 'activa' => true]);
    expect(fn () => $service->inicializar($otra))->toThrow(ValidationException::class);
    $evento = Activity::where('event', 'sucursal.folios.updated')->sole();
    expect($evento->causer_id)->toBe($actor->id)->and($evento->sucursal_id)->toBe($sucursal->id);
    expect(json_encode($evento->properties))->not->toContain('NUEVO', $actor->email);
});

test('la unicidad del prefijo ignora guiones y rechaza colisiones antes de emitir', function () {
    $actor = actingAsSuperAdmin();
    $service = app(FolioService::class);
    $sucursal = $actor->currentSucursal;
    $service->inicializar($sucursal);
    $config = $service->configuracion($sucursal->fresh());
    $service->configurar($sucursal->fresh(), [...$config, 'prefijo_folio' => 'AB-C'], $actor);
    $otra = Sucursal::create(['nombre' => 'Colisión', 'clave' => 'COL', 'prefijo_folio' => 'ABC', 'activa' => true]);
    expect(fn () => $service->inicializar($otra))->toThrow(ValidationException::class);
    expect(DB::table('folio_asignaciones')->count())->toBe(0);
});

test('guardar numeración exige versión vigente y nunca permite retroceder', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $service = app(FolioService::class);
    $sucursal = $actor->currentSucursal;
    $config = $service->configuracion($sucursal);
    Solicitud::create(datosSolicitudFolio($actor));
    $this->putJson(route('admin.sucursales.folios.update', $sucursal), $config)->assertUnprocessable()->assertJsonValidationErrors('version');
    $actual = $service->configuracion($sucursal->fresh());
    $this->putJson(route('admin.sucursales.folios.update', $sucursal), [...$actual, 'siguientes' => [...$actual['siguientes'], 'solicitud' => 1]])
        ->assertUnprocessable()->assertJsonValidationErrors('siguientes.solicitud');
    $response = $this->putJson(route('admin.sucursales.folios.update', $sucursal), [...$actual, 'siguientes' => [...$actual['siguientes'], 'credito' => 'invalido']]);
    $response->assertUnprocessable()->assertJsonValidationErrors('siguientes.credito');
    expect($response->json('errors')['siguientes.credito'][0])->toContain('entero')->not->toContain('validation.');
});

test('folios históricos se completan por fecha e ID más allá de un lote sin modificar evidencia', function () {
    $actor = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $actor->current_sucursal_id]);
    $ids = [];
    for ($i = 0; $i < 205; $i++) {
        $ids[] = DB::table('solicitudes')->insertGetId([...datosSolicitudFolio($actor, $cliente),
            'created_at' => now()->subDays($i === 204 ? 2 : 1), 'updated_at' => '2026-01-01 00:00:00']);
    }
    $this->artisan('folios:completar', ['--dry-run' => true])->assertSuccessful();
    $this->assertDatabaseCount('folio_asignaciones', 0);
    $this->artisan('folios:completar')->assertSuccessful();
    expect(Solicitud::find($ids[204])->folio)->toEndWith('-000001')
        ->and(Solicitud::find($ids[0])->folio)->toEndWith('-000002');
    expect(DB::table('solicitudes')->whereNull('folio')->count())->toBe(0)
        ->and(DB::table('solicitudes')->where('updated_at', '2026-01-01 00:00:00')->count())->toBe(205);
    $antes = DB::table('folio_asignaciones')->orderBy('id')->pluck('folio')->all();
    $this->artisan('folios:completar')->assertSuccessful();
    expect(DB::table('folio_asignaciones')->orderBy('id')->pluck('folio')->all())->toBe($antes);
    $evento = Activity::where('event', 'folio.historical_assigned')->firstOrFail();
    expect($evento->causer_id)->toBeNull()->and($evento->team_id)->toBeNull()->and($evento->sucursal_id)->toBe($actor->current_sucursal_id);
    expect(Activity::where('event', 'folio.historical_assigned')->count())->toBe(205);
});

test('un ámbito sobrevive a la recreación de su sucursal sin reutilizar números', function () {
    $actor = actingAsSuperAdmin();
    $service = app(FolioService::class);
    $sucursal = $actor->currentSucursal;
    DB::transaction(fn () => $service->siguiente(TipoFolio::Solicitud, $sucursal));
    $ambito = $sucursal->fresh()->folio_ambito_id;
    // Recreate the branch in this isolated fixture, just as the QA reset does.
    DB::table('sucursales')->where('id', $sucursal->id)->update(['folio_ambito_id' => null, 'clave' => 'TEMPORAL']);
    $nueva = Sucursal::create(['nombre' => 'Recreada', 'clave' => $sucursal->clave, 'prefijo_folio' => 'OTROPREF', 'activa' => true]);
    expect($service->configuracion($nueva)['siguientes']['solicitud'])->toBe(2);
    $service->inicializar($nueva);
    expect($nueva->fresh()->folio_ambito_id)->toBe($ambito)
        ->and($service->configuracion($nueva->fresh())['siguientes']['solicitud'])->toBe(2);
});

test('se puede corregir un prefijo heredado duplicado desde la configuración antes de emitir', function () {
    $actor = actingAsSuperAdmin();
    $primera = Sucursal::create(['nombre' => 'Uno', 'clave' => 'UNO', 'prefijo_folio' => 'DUP', 'activa' => true]);
    $segunda = Sucursal::create(['nombre' => 'Dos', 'clave' => 'DOS', 'prefijo_folio' => 'DUP', 'activa' => true]);
    $service = app(FolioService::class);
    $service->inicializar($primera);
    $this->actingAs($actor)->putJson(route('admin.sucursales.folios.update', $segunda), [
        ...$service->configuracion($segunda), 'prefijo_folio' => 'DOS',
    ])->assertSessionHasNoErrors();
    expect($service->configuracion($segunda->fresh())['prefijo_folio'])->toBe('DOS');
    $this->assertDatabaseCount('folio_asignaciones', 0);
});

test('editar datos generales no altera una numeración que avanzó desde la carga del formulario', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    Solicitud::create(datosSolicitudFolio($actor));
    $sucursal = $actor->currentSucursal->fresh();
    $antes = app(FolioService::class)->configuracion($sucursal);
    $this->putJson(route('admin.sucursales.update', $sucursal), ['nombre' => 'Nombre actualizado', 'clave' => $sucursal->clave,
        'activa' => true, 'prefijo_folio' => 'CAMBIO', 'consecutivo_solicitud' => 1])->assertSessionHasNoErrors();
    expect(app(FolioService::class)->configuracion($sucursal->fresh()))->toBe($antes);
});

test('actualizar folios exige permiso y audita la sucursal seleccionada', function () {
    $actor = actingAsSuperAdmin();
    $otra = Sucursal::create(['nombre' => 'Norte', 'clave' => 'NORTE', 'prefijo_folio' => 'NOR', 'activa' => true]);
    $service = app(FolioService::class);
    $config = $service->configuracion($otra);
    $normal = User::factory()->withPersonalTeam()->create();
    $this->actingAs($normal)->putJson(route('admin.sucursales.folios.update', $otra), $config)->assertForbidden();
    $this->actingAs($actor)->putJson(route('admin.sucursales.folios.update', $otra), [...$config,
        'siguientes' => [...$config['siguientes'], 'credito' => 42]])->assertSessionHasNoErrors();
    $evento = Activity::where('event', 'sucursal.folios.updated')->sole();
    expect($evento->causer_id)->toBe($actor->id)->and($evento->sucursal_id)->toBe($otra->id)
        ->and($evento->team_id)->toBe($actor->current_team_id);
});

test('completar historia conserva bytes de snapshots y documentos de los cuatro registros', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    $tablas = ['solicitudes', 'solicitud_paquetes', 'creditos', 'credito_pagos'];
    $antes = [];
    foreach ($tablas as $tabla) {
        // Recreate the pre-migration shape only in this isolated fixture.
        DB::table($tabla)->update(['folio' => null, 'folio_asignado_en' => null, 'folio_historico' => false]);
        $antes[$tabla] = (array) DB::table($tabla)->first();
    }
    DB::table('folio_asignaciones')->delete();
    $documentoAntes = (array) DB::table('documentos_generados')->first();
    $this->artisan('folios:completar')->assertSuccessful();
    foreach ($tablas as $tabla) {
        $despues = (array) DB::table($tabla)->first();
        expect((bool) $despues['folio_historico'])->toBeTrue()->and($despues['folio'])->not->toBeNull();
        foreach (['folio', 'folio_asignado_en', 'folio_historico'] as $campo) {
            unset($antes[$tabla][$campo], $despues[$campo]);
        }
        expect($despues)->toBe($antes[$tabla]);
    }
    expect((array) DB::table('documentos_generados')->first())->toBe($documentoAntes);
});

test('la base impide duplicar un crédito normalizado incluso si se omite el servicio', function () {
    $actor = actingAsSuperAdmin();
    $service = app(FolioService::class);
    $sucursal = $actor->currentSucursal;
    $service->inicializar($sucursal);
    $service->configurar($sucursal->fresh(), [...$service->configuracion($sucursal->fresh()), 'prefijo_folio' => 'AB-C'], $actor);
    $folio = DB::transaction(fn () => $service->siguiente(TipoFolio::Credito, $sucursal->fresh()));
    $asignacion = (array) DB::table('folio_asignaciones')->where('folio', $folio)->first();
    unset($asignacion['id'], $asignacion['normalizado']);
    expect(fn () => DB::transaction(fn () => DB::table('folio_asignaciones')->insert([
        ...$asignacion, 'folio' => 'ABC-CRE-000001', 'numero' => 2,
    ])))->toThrow(\Illuminate\Database\QueryException::class);
});

test('variables contractuales resuelven folios reales y reintentar conserva la misma asignación', function () {
    [$actor, $solicitud, , , , , $data] = prepararSolicitudConContrato($this);
    $plantillas = app(\App\Services\Documentos\DocumentoPlantillaVersionService::class);
    $plantilla = $plantillas->create(['clave' => 'contrato-folios', 'nombre' => 'Contrato con folios', 'tipo' => 'contrato',
        'contenido_html' => '<p>{{solicitud.folio}} / {{contrato.folio}}</p>', 'presentacion' => []], $actor->id);
    $version = $plantillas->activate($plantilla->versiones()->first());
    $data['version_id'] = $version->id;
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $paquete = \App\Models\SolicitudPaquete::sole();
    expect($paquete->documento->datos_utilizados['solicitud.folio'])->toBe($solicitud->folio)
        ->and($paquete->documento->datos_utilizados['contrato.folio'])->toBe($paquete->folio);
    $paquete->documento->update(['estado' => 'fallido']);
    $this->post(route('solicitudes.paquete.retry', [$solicitud, $paquete]))->assertSessionHasNoErrors();
    $this->assertDatabaseCount('folio_asignaciones', 2);
    expect($paquete->fresh()->folio)->toBe($paquete->folio);
});

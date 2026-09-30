<?php

use App\Enums\SolicitudEstado;
use App\Models\Cliente;
use App\Models\ProductoVersion;
use App\Models\Solicitud;
use App\Models\SolicitudRevision;
use App\Models\Sucursal;
use App\Services\FechaEmpresa;
use App\Services\ProductoVersionService;
use Database\Seeders\ModulesAndPermissionsSeeder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->withoutVite());

function prepararDesembolsoQa($test): array
{
    [$user, $solicitud, $paquete] = prepararContratoParaFirma($test);
    $test->post(route('solicitudes.firmas.store', [$solicitud, $paquete]), datosFirma())->assertSessionHasNoErrors();
    $firma = \App\Models\SolicitudFirma::firstOrFail();
    $test->post(route('solicitudes.firmas.review', [$solicitud, $firma]), datosRevisionFirma())->assertSessionHasNoErrors();
    $test->travel(7)->days();
    config(['originacion.desembolsos_qa_habilitados' => true]);
    $data = ['fecha_desembolso' => app(FechaEmpresa::class)->hoy()->toDateString(), 'importe' => $paquete->snapshot['tabla']['escenario']['efectivo_entregado'],
        'referencia' => 'QA-'.Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'lock_version' => 7, 'confirmacion_qa' => true];

    return [$user, $solicitud->fresh(), $paquete, $firma, $data];
}

test('desembolso QA crea un crédito calendario y movimiento inmutables sin duplicados', function () {
    [$user, $solicitud, $paquete, , $data] = prepararDesembolsoQa($this);
    $route = route('solicitudes.desembolso.store', $solicitud);
    $this->get(route('solicitudes.desembolso.create', $solicitud))->assertInertia(fn (Assert $page) => $page->where('preparacion.permitido', true));
    $this->post($route, $data)->assertSessionHasNoErrors();
    $this->post($route, $data)->assertSessionHasNoErrors();
    $credito = \App\Models\Credito::firstOrFail();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Desembolsada)
        ->and($credito->cronogramas()->first()->snapshot)->toBe($paquete->snapshot['tabla'])
        ->and($credito->capital_inicial)->toBe($paquete->snapshot['tabla']['escenario']['saldo_financiado']);
    foreach (['creditos', 'credito_desembolsos', 'credito_cronogramas', 'credito_movimientos'] as $table) {
        $this->assertDatabaseCount($table, 1);
    }
    $this->post($route, [...$data, 'importe' => '9999'])->assertSessionHasErrors('desembolso');
    $this->post($route, [...$data, 'idempotency_key' => (string) Str::uuid()])->assertSessionHasErrors('desembolso');
    foreach ([$credito, $credito->desembolso, $credito->cronogramas()->first(), $credito->movimientos()->first()] as $registro) {
        expect(fn () => $registro->delete())->toThrow(ValidationException::class);
        expect(fn () => $registro->update(['created_at' => now()->subDay()]))->toThrow(ValidationException::class);
    }
    expect(\Illuminate\Support\Facades\DB::table('credito_desembolsos')->value('referencia'))->not->toContain($data['referencia']);
    $this->get(route('creditos.show', $credito))->assertInertia(fn (Assert $page) => $page
        ->component('Creditos/Show')->missing('credito.condiciones')->missing('desembolso.payload_hash')->where('desembolso.referencia', $data['referencia']));
    $this->get(route('creditos.index'))->assertInertia(fn (Assert $page) => $page->where('creditos.total', 1));
    $this->get(route('solicitudes.desembolso.create', $solicitud))->assertRedirect(route('creditos.show', $credito));
    $this->post(route('solicitudes.resolver', $solicitud), ['accion' => 'cancelar', 'lock_version' => 8, 'motivo' => 'Intento inválido posterior al desembolso.'])->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.resolver', $solicitud), ['accion' => 'devolver', 'lock_version' => 8, 'motivo' => 'Intento inválido posterior al desembolso.', 'responsable_id' => $user->id])->assertSessionHasErrors('solicitud');
});

test('desembolso bloquea condiciones inválidas sin efectos parciales', function (string $caso) {
    [$user, $solicitud, , $firma, $data] = prepararDesembolsoQa($this);
    match ($caso) {
        'gate' => config(['originacion.desembolsos_qa_habilitados' => false]),
        'importe' => $data['importe'] = '1.00',
        'fecha' => $data['fecha_desembolso'] = app(FechaEmpresa::class)->hoy()->subDay()->toDateString(),
        'lock' => $data['lock_version'] = 0,
        'futuro' => $this->travelBack(),
        'pasado' => $this->travel(1)->days(),
        'expediente' => $solicitud->cliente->update(['ocupacion' => 'Cambio material']),
        'firma' => \Illuminate\Support\Facades\Storage::disk($firma->disk)->put($firma->path, 'archivo alterado'),
        'sucursal' => $user->forceFill(['current_sucursal_id' => null])->save(),
    };
    $this->post(route('solicitudes.desembolso.store', $solicitud), $data)->assertSessionHasErrors('desembolso');
    foreach (['creditos', 'credito_desembolsos', 'credito_cronogramas', 'credito_movimientos'] as $table) {
        $this->assertDatabaseCount($table, 0);
    }
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Formalizada);
})->with(['gate', 'importe', 'fecha', 'lock', 'futuro', 'pasado', 'expediente', 'firma', 'sucursal']);

test('desembolso exige permisos independientes y valida en español', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    [$user, $solicitud, , , $data] = prepararDesembolsoQa($this);
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo(['read clientes', 'read solicitudes', 'read documentos']);
    $this->get(route('creditos.index'))->assertForbidden();
    $this->get(route('solicitudes.desembolso.create', $solicitud))->assertForbidden();
    $user->givePermissionTo('read creditos');
    $this->get(route('solicitudes.desembolso.create', $solicitud))->assertOk();
    $this->post(route('solicitudes.desembolso.store', $solicitud), $data)->assertForbidden();
    $user->givePermissionTo('disburse creditos');
    $this->post(route('solicitudes.desembolso.store', $solicitud), [])->assertSessionHasErrors(['importe', 'fecha_desembolso', 'referencia', 'confirmacion_qa']);
    foreach (session('errors')->all() as $error) {
        expect($error)->not->toContain('validation.');
    }
    $this->post(route('solicitudes.desembolso.store', $solicitud), [...$data, 'importe' => '10000.001'])->assertSessionHasErrors('importe');
    $this->post(route('solicitudes.desembolso.store', $solicitud), $data)->assertSessionHasNoErrors();
    $credito = \App\Models\Credito::firstOrFail();
    $user->revokePermissionTo('read creditos');
    $this->get(route('creditos.show', $credito))->assertForbidden();
    expect(collect(app(\App\Services\NavigationService::class)->forUser($user))->pluck('items')->flatten(1)->pluck('label'))->not->toContain('Créditos');
});

function prepararContratoParaFirma($test): array
{
    [$user, $solicitud, , , , , $data] = prepararSolicitudConContrato($test);
    config(['originacion.firmas_qa_habilitadas' => true, 'documentos.disk' => 'local']);
    $test->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $paquete = \App\Models\SolicitudPaquete::firstOrFail();
    $original = "%PDF-1.4\nOriginal QA\n%%EOF";
    \Illuminate\Support\Facades\Storage::disk('local')->put('qa/original.pdf', $original);
    $paquete->documento->update(['estado' => 'generado', 'disk' => 'local', 'path' => 'qa/original.pdf',
        'archivo_hash' => hash('sha256', $original), 'generado_en' => now(), 'nombre_archivo' => 'original.pdf']);

    return [$user, $solicitud->fresh(), $paquete->fresh()];
}

function datosFirma(): array
{
    return ['archivo' => \Illuminate\Http\UploadedFile::fake()->createWithContent('firmado.pdf', "%PDF-1.4\nCopia firmada QA\n%%EOF"),
        'fecha_firma' => app(FechaEmpresa::class)->hoy()->toDateString(), 'confirmacion_qa' => true,
        'idempotency_key' => (string) Str::uuid(), 'lock_version' => 5];
}

function datosRevisionFirma(string $estado = 'aceptada', int $version = 6): array
{
    return ['estado_firma' => $estado, 'lock_version' => $version, 'confirmacion_revision' => true, 'confirmacion_qa' => true,
        'motivo' => $estado === 'rechazada' ? 'Falta la firma del cliente en el anexo.' : null];
}

test('recibe firma privada y la aceptación formaliza QA una sola vez conservando condiciones', function () {
    [, $solicitud, $paquete] = prepararContratoParaFirma($this);
    $data = datosFirma();
    $route = route('solicitudes.firmas.store', [$solicitud, $paquete]);
    $this->post($route, $data)->assertSessionHasNoErrors();
    $this->post($route, $data)->assertSessionHasNoErrors();
    $firma = \App\Models\SolicitudFirma::firstOrFail();
    $this->assertDatabaseCount('solicitud_firmas', 1);
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Aprobada);
    $this->get(route('solicitudes.firmas.view', [$solicitud, $firma]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->get(route('solicitudes.paquete.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('firmas.data.0.estado', 'recibida')->missing('firmas.data.0.path')->missing('firmas.data.0.disk'));
    $this->post(route('solicitudes.firmas.review', [$solicitud, $firma]), datosRevisionFirma())->assertSessionHasNoErrors();
    $this->post(route('solicitudes.firmas.review', [$solicitud, $firma]), datosRevisionFirma())->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Formalizada)
        ->and($paquete->fresh()->snapshot_hash)->toBe($paquete->snapshot_hash)
        ->and($solicitud->eventos()->where('tipo', 'formalizada_qa')->count())->toBe(1);
    $this->assertDatabaseCount('solicitud_formalizaciones', 1);
    $formalizacion = \App\Models\SolicitudFormalizacion::firstOrFail();
    expect($formalizacion->snapshot['firma_hash'])->toBe($firma->archivo_hash);
    expect(fn () => $formalizacion->update(['modo' => 'real']))->toThrow(ValidationException::class);
    expect(fn () => $firma->fresh()->update(['fecha_firma' => '2000-01-01']))->toThrow(ValidationException::class);
});

test('firma rechazada conserva motivo cifrado y permite una copia corregida', function () {
    [, $solicitud, $paquete] = prepararContratoParaFirma($this);
    $this->post(route('solicitudes.firmas.store', [$solicitud, $paquete]), datosFirma())->assertSessionHasNoErrors();
    $firma = \App\Models\SolicitudFirma::firstOrFail();
    $this->post(route('solicitudes.firmas.store', [$solicitud, $paquete]), [...datosFirma(), 'lock_version' => 6])->assertSessionHasErrors('firma');
    $this->post(route('solicitudes.firmas.review', [$solicitud, $firma]), datosRevisionFirma('rechazada'))->assertSessionHasNoErrors();
    expect(\Illuminate\Support\Facades\DB::table('solicitud_firmas')->value('motivo'))->not->toContain('Falta la firma');
    $this->post(route('solicitudes.firmas.store', [$solicitud, $paquete]), [...datosFirma(), 'lock_version' => 7])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('solicitud_firmas', 2);
    $this->assertDatabaseCount('solicitud_formalizaciones', 0);
    expect($firma->fresh()->estado)->toBe('rechazada');
});

test('firma bloquea cambios de evidencia aprobación obsoleta archivos alterados y gate cerrado', function (string $caso) {
    [, $solicitud, $paquete] = prepararContratoParaFirma($this);
    $this->post(route('solicitudes.firmas.store', [$solicitud, $paquete]), datosFirma())->assertSessionHasNoErrors();
    $firma = \App\Models\SolicitudFirma::firstOrFail();
    match ($caso) {
        'expediente' => $solicitud->cliente->update(['ocupacion' => 'Otra actividad']),
        'vencida' => $this->travel(16)->days(),
        'original' => \Illuminate\Support\Facades\Storage::disk('local')->put('qa/original.pdf', 'alterado'),
        'copia' => \Illuminate\Support\Facades\Storage::disk($firma->disk)->put($firma->path, 'alterado'),
        'gate' => config(['originacion.firmas_qa_habilitadas' => false]),
    };
    $this->post(route('solicitudes.firmas.review', [$solicitud, $firma]), datosRevisionFirma())->assertSessionHasErrors('firma');
    $this->assertDatabaseCount('solicitud_formalizaciones', 0);
    expect($firma->fresh()->estado)->toBe('recibida')->and($solicitud->fresh()->estado)->toBe(SolicitudEstado::Aprobada);
})->with(['expediente', 'vencida', 'original', 'copia', 'gate']);

test('firma valida PDF fecha confirmaciones y motivo en español', function () {
    [, $solicitud, $paquete] = prepararContratoParaFirma($this);
    $route = route('solicitudes.firmas.store', [$solicitud, $paquete]);
    $this->post($route, [])->assertSessionHasErrors(['archivo', 'fecha_firma', 'confirmacion_qa', 'idempotency_key', 'lock_version']);
    foreach (session('errors')->all() as $error) {
        expect($error)->not->toContain('validation.');
    }
    $this->post($route, [...datosFirma(), 'archivo' => \Illuminate\Http\UploadedFile::fake()->createWithContent('falso.pdf', '<script>alert(1)</script>')])->assertSessionHasErrors('archivo');
    $this->post($route, [...datosFirma(), 'fecha_firma' => '2000-01-01'])->assertSessionHasErrors('firma');
    $this->post($route, [...datosFirma(), 'fecha_firma' => '2099-01-01'])->assertSessionHasErrors('fecha_firma');
    $this->post($route, datosFirma())->assertSessionHasNoErrors();
    $firma = \App\Models\SolicitudFirma::firstOrFail();
    $this->post(route('solicitudes.firmas.review', [$solicitud, $firma]), [...datosRevisionFirma(), 'confirmacion_revision' => false])->assertSessionHasErrors('confirmacion_revision');
    expect(session('errors')->first('confirmacion_revision'))->toContain('comparaste')->not->toContain('validation.');
    $this->post(route('solicitudes.firmas.review', [$solicitud, $firma]), [...datosRevisionFirma('rechazada'), 'motivo' => '   '])->assertSessionHasErrors('motivo');
});

test('firma separa permisos de recepción revisión consulta y descarga', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    [$user, $solicitud, $paquete] = prepararContratoParaFirma($this);
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo(['read clientes', 'read solicitudes', 'read documentos']);
    $route = route('solicitudes.firmas.store', [$solicitud, $paquete]);
    $this->post($route, datosFirma())->assertForbidden();
    $user->givePermissionTo('receive signature solicitudes');
    $this->post($route, datosFirma())->assertSessionHasNoErrors();
    $firma = \App\Models\SolicitudFirma::firstOrFail();
    $this->post(route('solicitudes.firmas.review', [$solicitud, $firma]), datosRevisionFirma())->assertForbidden();
    $this->get(route('solicitudes.firmas.view', [$solicitud, $firma]))->assertOk();
    $this->get(route('solicitudes.firmas.download', [$solicitud, $firma]))->assertForbidden();
    $user->givePermissionTo('review signature solicitudes');
    $this->post(route('solicitudes.firmas.review', [$solicitud, $firma]), datosRevisionFirma())->assertSessionHasNoErrors();
    $user->revokePermissionTo('read solicitudes');
    $this->get(route('solicitudes.firmas.view', [$solicitud, $firma]))->assertForbidden();
});

test('firma no admite el original como firmado ni idempotencia con otros datos', function () {
    [$user, $solicitud, $paquete] = prepararContratoParaFirma($this);
    $route = route('solicitudes.firmas.store', [$solicitud, $paquete]);
    $original = \Illuminate\Support\Facades\Storage::disk('local')->get('qa/original.pdf');
    $this->post($route, [...datosFirma(), 'archivo' => \Illuminate\Http\UploadedFile::fake()->createWithContent('original.pdf', $original)])->assertSessionHasErrors('firma');
    $data = datosFirma();
    $this->post($route, $data)->assertSessionHasNoErrors();
    $this->post($route, [...$data, 'archivo' => \Illuminate\Http\UploadedFile::fake()->createWithContent('otra.pdf', "%PDF-1.4\nOtra copia\n%%EOF")])->assertSessionHasErrors('firma');
    $this->assertDatabaseCount('solicitud_firmas', 1);
    $user->update(['current_sucursal_id' => Sucursal::factory()->create()->id]);
    $this->post($route, $data)->assertSessionHasErrors('firma');
});

test('devolver una formalización QA conserva firma y registro histórico', function () {
    [$user, $solicitud, $paquete] = prepararContratoParaFirma($this);
    $this->post(route('solicitudes.firmas.store', [$solicitud, $paquete]), datosFirma())->assertSessionHasNoErrors();
    $firma = \App\Models\SolicitudFirma::firstOrFail();
    $this->post(route('solicitudes.firmas.review', [$solicitud, $firma]), datosRevisionFirma())->assertSessionHasNoErrors();
    $this->post(route('solicitudes.resolver', $solicitud), ['accion' => 'devolver', 'lock_version' => 7,
        'motivo' => 'Cambiar fecha y renovar las condiciones firmadas.', 'responsable_id' => $user->id])->assertSessionHasNoErrors();
    $this->get(route('solicitudes.paquete.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('actual', null)->where('formalizacion', null)->where('firmas.data.0.actual', false)->where('firmas.data.0.estado', 'aceptada'));
    $this->assertDatabaseCount('solicitud_formalizaciones', 1);
    expect($firma->fresh()->estado)->toBe('aceptada');
    $this->post(route('solicitudes.firmas.store', [$solicitud, $paquete]), [...datosFirma(), 'lock_version' => 8])->assertSessionHasErrors('firma');
});

test('firma rechaza evidencia de otra solicitud y una versión de captura obsoleta', function () {
    [, $solicitud, $paquete] = prepararContratoParaFirma($this);
    $route = route('solicitudes.firmas.store', [$solicitud, $paquete]);
    $this->post($route, [...datosFirma(), 'lock_version' => 0])->assertSessionHasErrors('firma');
    expect(\Illuminate\Support\Facades\Storage::disk('local')->allFiles('solicitudes'))->toBeEmpty();
    $this->post($route, datosFirma())->assertSessionHasNoErrors();
    $firma = \App\Models\SolicitudFirma::firstOrFail();
    $this->post(route('solicitudes.store'), ['cliente_id' => $solicitud->cliente_id, 'clave_creacion' => (string) Str::uuid()])->assertSessionHasNoErrors();
    $otra = Solicitud::latest('id')->firstOrFail();
    $this->get(route('solicitudes.firmas.view', [$otra, $firma]))->assertNotFound();
    $this->get(route('solicitudes.firmas.download', [$otra, $firma]))->assertNotFound();
    $this->post(route('solicitudes.firmas.review', [$otra, $firma]), datosRevisionFirma())->assertNotFound();
    $this->post(route('solicitudes.firmas.store', [$otra, $paquete]), datosFirma())->assertNotFound();
    $this->assertDatabaseCount('solicitud_formalizaciones', 0);
});

test('firma permite rechazar evidencia aunque haya vencido la aprobación', function () {
    [, $solicitud, $paquete] = prepararContratoParaFirma($this);
    $this->post(route('solicitudes.firmas.store', [$solicitud, $paquete]), datosFirma())->assertSessionHasNoErrors();
    $firma = \App\Models\SolicitudFirma::firstOrFail();
    $this->travel(16)->days();
    $this->post(route('solicitudes.firmas.review', [$solicitud, $firma]), datosRevisionFirma('rechazada'))->assertSessionHasNoErrors();
    expect($firma->fresh()->estado)->toBe('rechazada');
    $this->assertDatabaseCount('solicitud_formalizaciones', 0);
});

test('firma no convierte contratos históricos sin proyección fiscal', function () {
    [, $solicitud, $paquete] = prepararContratoParaFirma($this);
    // Fixture histórica únicamente: no existe una operación pública de edición del snapshot.
    $snapshot = $paquete->snapshot;
    unset($snapshot['formato']);
    $snapshot['fiscalidad'] = 'no_definida';
    $paquete->snapshot = $snapshot;
    \Illuminate\Support\Facades\DB::table('solicitud_paquetes')->where('id', $paquete->id)->update([
        'snapshot' => $paquete->getAttributes()['snapshot'],
        'snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)),
    ]);
    $this->post(route('solicitudes.firmas.store', [$solicitud, $paquete]), datosFirma())->assertSessionHasErrors('firma');
    expect(session('errors')->first('firma'))->toContain('proyección fiscal');
    $this->assertDatabaseCount('solicitud_firmas', 0);
    expect($paquete->fresh()->snapshot)->toBe($snapshot);
});

function prepararSolicitudConContrato($test, bool $fiscalidad = true): array
{
    \Illuminate\Support\Facades\Queue::fake();
    [$user, $solicitud, $cliente, $producto, $doc] = prepararSolicitudAprobable($test, fiscalidad: $fiscalidad);
    config(['originacion.paquetes_qa_habilitados' => true]);
    $test->post(route('solicitudes.aprobar', $solicitud), ['lock_version' => 3, 'motivo' => 'Aprobación para probar paquete contractual.'])->assertSessionHasNoErrors();
    $service = app(\App\Services\Documentos\DocumentoPlantillaVersionService::class);
    $plantilla = $service->create(['clave' => 'contrato-qa', 'nombre' => 'Contrato de prueba', 'tipo' => 'contrato',
        'contenido_html' => '<p>Contrato para {{cliente.nombre_completo}}.</p>', 'presentacion' => []], $user->id);
    $version = $service->activate($plantilla->versiones->first());
    $data = ['version_id' => $version->id, 'lock_version' => 4, 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true];

    return [$user, $solicitud->fresh(), $cliente, $producto, $doc, $version, $data];
}

test('paquete QA congela aprobación contrato y tabla con idempotencia y sin formalizar', function () {
    [$user, $solicitud, $cliente, , , $version, $data] = prepararSolicitudConContrato($this);
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $this->post(route('solicitudes.paquete.store', $solicitud), [...$data, 'idempotency_key' => (string) Str::uuid()])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('solicitud_paquetes', 1);
    $this->assertDatabaseCount('documentos_generados', 1);
    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\GenerarDocumentoPdf::class, 1);
    $paquete = \App\Models\SolicitudPaquete::firstOrFail();
    expect($paquete->snapshot['fiscalidad'])->toBe('proyeccion')
        ->and($paquete->snapshot['tabla']['total_impuestos'])->not->toBe('0.00')
        ->and($solicitud->revisiones()->first()->snapshot['simulacion_informativa']['fiscalidad']['estado'])->toBe('no_calculada')
        ->and($paquete->snapshot['tabla']['tabla'])->toHaveCount(13)
        ->and($solicitud->fresh()->estado)->toBe(SolicitudEstado::Aprobada)
        ->and($solicitud->fresh()->lock_version)->toBe(5);
    expect(fn () => $paquete->update(['snapshot_hash' => 'alterada']))->toThrow(ValidationException::class);
    $stored = \Illuminate\Support\Facades\DB::table('solicitud_paquetes')->value('snapshot');
    expect($stored)->not->toContain('Contrato para');
    $this->get(route('solicitudes.paquete.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->component('Solicitudes/Paquete')->where('actual.id', $paquete->id)->missing('actual.snapshot')->has('paquetes.data', 1));
    $version->plantilla->update(['nombre' => 'Otro nombre posterior']);
    expect($paquete->fresh()->snapshot['plantilla']['nombre'])->toBe('Contrato de prueba');
    $this->get(route('clientes.expediente.show', $cliente))->assertInertia(fn (Assert $page) => $page->where('documentosGenerados.total', 0));
});

test('tabla fiscal del paquete conserva fechas importes y configuración de la versión aprobada', function () {
    $this->travelTo(\Carbon\CarbonImmutable::parse('2026-01-01 12:00:00', app(FechaEmpresa::class)->zonaHoraria()));
    [$user, $solicitud, , $producto, , , $data] = prepararSolicitudConContrato($this);
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $paquete = \App\Models\SolicitudPaquete::firstOrFail();
    $snapshot = $paquete->snapshot;
    expect($snapshot['tabla']['tabla'][1]['fecha'])->toBe('2026-02-08')
        ->and($snapshot['tabla']['tabla'][1]['interes'])->toBe('310.00')
        ->and($snapshot['tabla']['tabla'][1]['impuestos'])->toBe('49.60')
        ->and($snapshot['tabla']['fiscalidad']['uso'])->toBe('prueba');
    $productos = app(ProductoVersionService::class);
    $nueva = $productos->nuevaVersion($producto->producto, $producto, $user->id)->refresh();
    $nueva->update(['fiscalidad' => ['uso' => 'prueba', 'ordinario' => ['tratamiento' => 'exento']]]);
    $this->travel(1)->days();
    $productos->activar($nueva, app(FechaEmpresa::class)->hoy()->toDateString());
    expect($paquete->fresh()->snapshot)->toBe($snapshot);
    $this->get(route('solicitudes.paquete.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('actual.tabla.tabla.1.impuestos', '49.60'));
});

test('nuevo paquete bloquea fiscalidad ausente sin crear documento y orienta al operador', function () {
    [, $solicitud, , , , , $data] = prepararSolicitudConContrato($this, false);
    $this->get(route('solicitudes.paquete.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('preparacion.puede_preparar', false));
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasErrors('paquete');
    expect(session('errors')->first('paquete'))->toContain('nueva versión con fiscalidad definida')->not->toContain('validation.');
    $this->assertDatabaseCount('solicitud_paquetes', 0);
    $this->assertDatabaseCount('documentos_generados', 0);
    expect($solicitud->fresh()->lock_version)->toBe(4);
});

test('paquete anterior a impuestos conserva snapshot y reintenta sin promover su fiscalidad', function () {
    [, $solicitud, , , , , $data] = prepararSolicitudConContrato($this);
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $paquete = \App\Models\SolicitudPaquete::firstOrFail();
    // Reproduce a persisted P4a record, bypassing immutable model only in this fixture.
    $snapshot = $paquete->snapshot;
    unset($snapshot['formato']);
    $snapshot['fiscalidad'] = 'no_definida';
    $snapshot['tabla'] = $solicitud->revisiones()->first()->snapshot['simulacion_informativa'];
    $paquete->snapshot = $snapshot;
    $hash = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
    \Illuminate\Support\Facades\DB::table('solicitud_paquetes')->where('id', $paquete->id)->update([
        'snapshot' => $paquete->getAttributes()['snapshot'], 'snapshot_hash' => $hash]);
    $paquete->documento->update(['estado' => 'fallido']);
    $this->post(route('solicitudes.paquete.retry', [$solicitud, $paquete]))->assertSessionHasNoErrors();
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    expect($paquete->fresh()->snapshot_hash)->toBe($hash)
        ->and($paquete->fresh()->snapshot)->toBe($snapshot);
    $html = view('documentos.paquete-anexo', ['snapshot' => $snapshot])->render();
    expect($html)->toContain('Fiscalidad no definida', 'Total sin impuestos')->not->toContain('Total con impuestos');
    $this->assertDatabaseCount('solicitud_paquetes', 1);
});

test('paquete revalida aprobación evidencia sucursal y habilitación en servidor', function (string $caso) {
    [$user, $solicitud, $cliente, $producto, $doc, , $data] = prepararSolicitudConContrato($this);
    match ($caso) {
        'qa' => config(['originacion.paquetes_qa_habilitados' => false]),
        'sucursal' => $user->update(['current_sucursal_id' => null]),
        'vencida' => $this->travel(16)->days(),
        'identidad' => $cliente->update(['primer_nombre' => 'Nombre cambiado']),
        'documento' => $doc->update(['estado' => 'rechazado']),
        'producto' => app(ProductoVersionService::class)->retirar($producto),
    };
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasErrors('paquete');
    $this->assertDatabaseCount('solicitud_paquetes', 0);
})->with(['qa', 'sucursal', 'vencida', 'identidad', 'documento', 'producto']);

test('paquete valida campos en español y rechaza versión retirada y lock obsoleto', function () {
    [, $solicitud, , , , $version, $data] = prepararSolicitudConContrato($this);
    $this->post(route('solicitudes.paquete.store', $solicitud), [])->assertSessionHasErrors(['version_id', 'idempotency_key', 'lock_version', 'confirmacion_qa']);
    foreach (session('errors')->all() as $error) {
        expect($error)->not->toContain('validation.');
    }
    $this->post(route('solicitudes.paquete.store', $solicitud), [...$data, 'confirmacion_qa' => false])
        ->assertSessionHasErrors(['confirmacion_qa' => 'Confirma que el paquete es de prueba y no tiene validez contractual.']);
    $this->post(route('solicitudes.paquete.store', $solicitud), [...$data, 'lock_version' => 0])->assertSessionHasErrors('paquete');
    app(\App\Services\Documentos\DocumentoPlantillaVersionService::class)->retire($version);
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasErrors('version_id');
});

test('paquete protege pantalla estado y archivo por permisos de solicitud y documentos', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    [$user, $solicitud, , , , , $data] = prepararSolicitudConContrato($this);
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $documento = \App\Models\SolicitudPaquete::firstOrFail()->documento;
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo(['read clientes', 'read documentos', 'download documentos']);
    $this->get(route('solicitudes.paquete.show', $solicitud))->assertForbidden();
    $this->get(route('documentos-generados.status', $documento))->assertForbidden();
    $this->get(route('documentos-generados.download', $documento))->assertForbidden();
    $user->givePermissionTo('read solicitudes');
    $this->get(route('solicitudes.paquete.show', $solicitud))->assertOk();
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertForbidden();
    $user->givePermissionTo(['prepare package solicitudes', 'generate documentos']);
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
});

test('paquete genera anexo marcado usando snapshot y no regenera el original', function () {
    [, $solicitud, $cliente, , , $version, $data] = prepararSolicitudConContrato($this);
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $paquete = \App\Models\SolicitudPaquete::firstOrFail();
    $renderer = new class implements \App\Contracts\DocumentoPdfRenderer
    {
        public array $captured = [];

        public function render(string $bodyHtml, ?string $headerHtml = null, ?string $footerHtml = null, array $options = []): string
        {
            $this->captured = compact('bodyHtml', 'headerHtml', 'options');

            return '%PDF-original-qa';
        }
    };
    $this->instance(\App\Contracts\DocumentoPdfRenderer::class, $renderer);
    $cliente->update(['primer_nombre' => 'Cambió después']);
    $job = new \App\Jobs\GenerarDocumentoPdf($paquete->documento->id);
    $job->handle(app(\App\Services\Documentos\DocumentoRenderService::class), app(\App\Services\ActivityLogService::class));
    expect($renderer->captured['bodyHtml'])->toContain('Proyección fiscal QA congelada', 'Total con impuestos', 'Desglose fiscal por periodo y concepto')->not->toContain('Cambió después')
        ->and($renderer->captured['headerHtml'])->toContain('sin validez contractual')
        ->and($renderer->captured['options']['marca_agua'])->toBe('QA — SIN VALIDEZ CONTRACTUAL');
    $documento = $paquete->documento->fresh();
    $this->get(route('documentos-generados.download', $documento))->assertOk();
    \Illuminate\Support\Facades\Storage::disk($documento->disk)->delete($documento->path);
    $job->handle(app(\App\Services\Documentos\DocumentoRenderService::class), app(\App\Services\ActivityLogService::class));
    \Illuminate\Support\Facades\Storage::disk($documento->disk)->assertMissing($documento->path);
    expect($documento->fresh()->archivo_hash)->toBe(hash('sha256', '%PDF-original-qa'));
});

test('paquete reintenta el mismo snapshot fallido y rechaza paquetes ajenos', function () {
    [$user, $solicitud, $cliente, , , , $data] = prepararSolicitudConContrato($this);
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $paquete = \App\Models\SolicitudPaquete::firstOrFail();
    $paquete->documento->update(['estado' => 'fallido', 'error_mensaje' => 'Prueba de recuperación']);
    $this->post(route('solicitudes.paquete.retry', [$solicitud, $paquete]))->assertSessionHasNoErrors();
    expect($paquete->documento->fresh()->estado->value)->toBe('pendiente');
    $this->post(route('solicitudes.paquete.retry', [$solicitud, $paquete]))->assertSessionHasErrors('paquete');
    $this->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()])->assertSessionHasNoErrors();
    $otra = Solicitud::latest('id')->firstOrFail();
    $this->post(route('solicitudes.paquete.retry', [$otra, $paquete]))->assertNotFound();
    $this->assertDatabaseCount('solicitud_paquetes', 1);
});

test('paquete histórico se conserva y una nueva aprobación permite otra versión', function () {
    [$user, $solicitud, , , , $version, $data] = prepararSolicitudConContrato($this);
    $this->post(route('solicitudes.paquete.store', $solicitud), $data)->assertSessionHasNoErrors();
    $anterior = \App\Models\SolicitudPaquete::firstOrFail();
    $this->post(route('solicitudes.resolver', $solicitud), ['accion' => 'devolver', 'lock_version' => 5,
        'motivo' => 'Revisar el paquete con otra versión contractual.', 'responsable_id' => $user->id])->assertSessionHasNoErrors();
    $this->get(route('solicitudes.paquete.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('actual', null)->where('paquetes.data.0.actual', false));
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 6])->assertSessionHasNoErrors();
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(7))->assertSessionHasNoErrors();
    $this->post(route('solicitudes.dictaminar', $solicitud), [...solicitudDictamenDatos(8, 'pld'), 'resultado' => 'sin_observaciones', 'nivel_riesgo' => 'bajo'])->assertSessionHasNoErrors();
    $this->post(route('solicitudes.aprobar', $solicitud), ['lock_version' => 9, 'motivo' => 'Aprobación renovada con evidencia actual.'])->assertSessionHasNoErrors();
    $service = app(\App\Services\Documentos\DocumentoPlantillaVersionService::class);
    $nueva = $service->activate($service->duplicate($version->plantilla, $version, $user->id));
    $this->post(route('solicitudes.paquete.store', $solicitud), [...$data, 'version_id' => $nueva->id, 'lock_version' => 10, 'idempotency_key' => (string) Str::uuid()])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('solicitud_paquetes', 2);
    expect($anterior->fresh()->snapshot_hash)->toBe($anterior->snapshot_hash)
        ->and($anterior->fresh()->snapshot['plantilla']['numero'])->toBe(1);
});

function solicitudProducto(int $userId, bool $fiscalidad = true): ProductoVersion
{
    $producto = app(ProductoVersionService::class)->crear([
        'clave' => 'SOL-'.Str::random(8), 'nombre' => 'Crédito de solicitud',
        'version' => [
            'monto_minimo' => '5000.00', 'monto_maximo' => '100000.00',
            'tasa_ordinaria_anual' => '36.00', 'tasa_moratoria_anual' => '72.00',
            'dias_gracia_mora' => 3, 'cat_aplica' => true,
            'fiscalidad' => $fiscalidad ? ['uso' => 'prueba', 'referencia' => 'Configuración sintética QA',
                'ordinario' => ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto'],
                'moratorio' => ['tratamiento' => 'no_definido']] : null,
            'periodicidades' => [['periodicidad' => 'mensual', 'plazo_minimo' => 3, 'plazo_maximo' => 24, 'plazo_predeterminado' => 12]],
            'reglas' => ['metodos_amortizacion' => ['cuota_nivelada', 'capital_fijo'], 'permite_prepago_parcial' => true,
                'permite_liquidacion_anticipada' => true, 'monto_minimo_prepago' => '500.00', 'aplicacion_prepago' => 'reducir_plazo'],
            'comisiones' => [],
        ],
    ], $userId);

    return app(ProductoVersionService::class)->activar($producto->versiones()->first(), app(FechaEmpresa::class)->hoy()->toDateString());
}

function solicitudDatos(Cliente $cliente, ProductoVersion $producto): array
{
    return [
        'cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid(),
        'producto_version_id' => $producto->id, 'monto' => '10000.00',
        'periodicidad' => 'mensual', 'plazo' => 12, 'metodo' => 'cuota_nivelada',
        'destino' => 'Compra de herramienta.', 'fecha_estimada' => app(FechaEmpresa::class)->hoy()->addWeek()->toDateString(),
    ];
}

function solicitudDictamenDatos(int $lockVersion, string $tipo = 'evaluacion'): array
{
    return [
        'lock_version' => $lockVersion, 'tipo_dictamen' => $tipo,
        'resultado' => $tipo === 'pld' ? 'bloqueada' : 'favorable',
        'fundamento' => 'Dictamen reservado sustentado en las evidencias revisadas.',
        'fuentes' => 'Expediente y evidencia documental identificada por el revisor.',
        'metodologia' => 'Manual interno de pruebas versión 1',
        ...($tipo === 'pld' ? ['nivel_riesgo' => 'alto'] : []),
    ];
}

function politicaSolicitudDatos(string $modalidad = 'individual', string $sic = 'manual_permitido'): array
{
    return ['version_anterior' => 0, 'modalidad' => $modalidad, 'sic' => $sic, 'monto_maximo' => '50000.00',
        'vigencia_dias' => 15, 'documentos' => ['ine'], 'referencia_validacion' => 'Validación interna de pruebas, no para producción.',
        'criterio_capacidad' => 'Revisar ingresos y egresos y fundamentar capacidad manualmente.', 'confirmacion' => true];
}

function prepararSolicitudAprobable($test, string $modalidad = 'individual', string $sic = 'manual_permitido', bool $fiscalidad = true): array
{
    \Illuminate\Support\Facades\Storage::fake('local');
    config(['originacion.aprobaciones_habilitadas' => true]);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $regimen = \App\Models\RegimenFiscal::create(['clave' => '612', 'descripcion' => 'Régimen de prueba', 'fisica' => true,
        'moral' => false, 'fecha_inicio_vigencia' => '2020-01-01', 'fecha_fin_vigencia' => '2099-12-31']);
    $cliente->datosFiscales()->create(['tipo_persona' => 'fisica', 'regimen_fiscal_id' => $regimen->id,
        'curp' => 'GODE561231HDFRRN09', 'rfc' => 'GODE561231GR8', 'razon_social' => 'Persona de prueba']);
    $version = solicitudProducto($user->id, $fiscalidad);
    app(\App\Services\OriginacionPoliticaService::class)->crear($version, politicaSolicitudDatos($modalidad, $sic), $user);
    $doc = $cliente->documentos()->create(['tipo' => 'ine', 'version' => 1, 'estado' => 'validado', 'es_actual' => true,
        'disk' => 'local', 'path' => 'prueba/ine.pdf', 'mime_type' => 'application/pdf', 'nombre_original' => 'ine.pdf',
        'tamano_bytes' => 10, 'revisado_en' => now(), 'revisado_por' => $user->id]);
    \Illuminate\Support\Facades\Storage::disk('local')->put('prueba/ine.pdf', 'fixture');
    $test->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, $version))->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $test->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasNoErrors();
    $test->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(1))->assertSessionHasNoErrors();
    $test->post(route('solicitudes.dictaminar', $solicitud), [...solicitudDictamenDatos(2, 'pld'), 'resultado' => 'sin_observaciones', 'nivel_riesgo' => 'bajo'])->assertSessionHasNoErrors();

    return [$user, $solicitud->fresh(), $cliente, $version, $doc];
}

test('accesos del cliente precargan solicitud y respetan permiso y sucursal', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->get(route('clientes.show', $cliente))->assertOk();
    $this->get(route('clientes.edit', $cliente))->assertOk();
    $this->get(route('solicitudes.create', ['cliente_id' => $cliente->id]))->assertInertia(fn (Assert $page) => $page->where('clienteInicial.id', $cliente->id));
    $user->forceFill(['current_sucursal_id' => null])->save();
    $this->get(route('solicitudes.create', ['cliente_id' => $cliente->id]))->assertForbidden();
    $user->forceFill(['is_super_admin' => false, 'current_sucursal_id' => $cliente->sucursal_id])->save();
    $user->givePermissionTo('read clientes');
    $this->get(route('clientes.show', $cliente))->assertOk();
    $this->get(route('solicitudes.create', ['cliente_id' => $cliente->id]))->assertForbidden();
});

test('aprobación individual conserva evidencia política y vigencia sin duplicarse', function () {
    [$user, $solicitud] = prepararSolicitudAprobable($this);
    $requisitos = app(\App\Services\SolicitudRequisitosService::class)->evaluar($solicitud, $user);
    expect(collect($requisitos['requisitos'])->where('cumplido', false)->all())->toBe([]);
    $data = ['lock_version' => 3, 'motivo' => 'Aprobación fundamentada de prueba.'];
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Aprobada);
    $resolucion = $solicitud->resoluciones()->firstOrFail();
    expect($resolucion->evidencia['politica']['condiciones']['modalidad'])->toBe('individual')
        ->and($resolucion->evidencia['documentos'])->toHaveCount(1)
        ->and($resolucion->vigente_hasta->toDateString())->toBe(app(FechaEmpresa::class)->hoy()->addDays(14)->toDateString());
    $this->travel(16)->days();
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page->where('aprobacionVencida', true));
    $this->travelBack();
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.aprobar', $solicitud), [...$data, 'lock_version' => 4])->assertSessionHasErrors('aprobacion');
    $this->assertDatabaseCount('solicitud_resoluciones', 1);
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 4, 'monto' => '11000.00'])->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.resolver', $solicitud), ['lock_version' => 4, 'accion' => 'devolver', 'motivo' => 'Actualizar condiciones para nueva revisión.', 'responsable_id' => $user->id])->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Devuelta);
    $this->assertDatabaseCount('solicitud_resoluciones', 2);
});

test('dual impide autoaprobación incluso superadmin y admite otro aprobador', function () {
    [$capturista, $solicitud] = prepararSolicitudAprobable($this, 'dual');
    $data = ['lock_version' => 3, 'motivo' => 'Resolución dual de prueba documentada.'];
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    $aprobador = actingAsSuperAdmin();
    $aprobador->sucursales()->attach($capturista->current_sucursal_id);
    $aprobador->update(['current_sucursal_id' => $capturista->current_sucursal_id]);
    $this->actingAs($aprobador)->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Aprobada);
});

test('bloqueos de habilitación SIC documentos y dictámenes no pueden saltarse por URL', function () {
    [$user, $solicitud, $cliente, $version, $doc] = prepararSolicitudAprobable($this, 'individual', 'requerido');
    $data = ['lock_version' => 3, 'motivo' => 'Intento de aprobación bloqueada.'];
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('SIC integrado');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
    $this->assertDatabaseCount('sic_queries', 0);
});

test('expediente cambiado exige renovar dictamen y política nueva exige reenviar', function () {
    [$user, $solicitud, $cliente, $version, $doc] = prepararSolicitudAprobable($this);
    $data = ['lock_version' => 3, 'motivo' => 'Intento de aprobación con expediente cambiado.'];
    config(['originacion.aprobaciones_habilitadas' => false]);
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    config(['originacion.aprobaciones_habilitadas' => true]);
    $doc->update(['vence_en' => app(FechaEmpresa::class)->hoy()->subDay()]);
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('El expediente cambió después del dictamen')->toContain('incluida su validación')->toContain('Evaluación debe revisar')->toContain('Cumplimiento debe revisar')->toContain('Documento requerido');
    app(\App\Services\OriginacionPoliticaService::class)->crear($version, [...politicaSolicitudDatos(), 'version_anterior' => 1], $user);
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('La política cambió');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
});

test('política requiere permisos confirmación datos explícitos y preserva historia', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $version = solicitudProducto($user->id);
    $this->actingAs($user)->post(route('originacion-politicas.store', $version), [])->assertSessionHasErrors(['modalidad', 'sic', 'vigencia_dias', 'referencia_validacion']);
    expect(session('errors')->first('modalidad'))->toBe('El campo modalidad de aprobación es obligatorio.');
    $this->post(route('originacion-politicas.store', $version), politicaSolicitudDatos())->assertSessionHasNoErrors();
    $politica = \App\Models\OriginacionPolitica::firstOrFail();
    expect(fn () => $politica->delete())->toThrow(ValidationException::class);
    $this->post(route('originacion-politicas.store', $version), politicaSolicitudDatos())->assertSessionHasErrors('politica');
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo('read productos-crediticios');
    $this->get(route('originacion-politicas.show', $version))->assertForbidden();
    $this->post(route('originacion-politicas.store', $version), [...politicaSolicitudDatos(), 'version_anterior' => 1])->assertForbidden();
});

test('aprobar exige permiso explícito y sucursal incluso cuando los requisitos están completos', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    [$user, $solicitud] = prepararSolicitudAprobable($this);
    $data = ['lock_version' => 3, 'motivo' => 'Comprobación de autorización de aprobación.'];
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo(['read clientes', 'read solicitudes', 'review solicitudes']);
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertForbidden();
    $user->forceFill(['is_super_admin' => true, 'current_sucursal_id' => null])->save();
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('sucursal');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
});

test('último dictamen prevalece y cambios de cliente o pérdida del archivo bloquean', function () {
    [$user, $solicitud, $cliente] = prepararSolicitudAprobable($this);
    $this->post(route('solicitudes.dictaminar', $solicitud), [...solicitudDictamenDatos(3), 'resultado' => 'desfavorable'])->assertSessionHasNoErrors();
    $data = ['lock_version' => 4, 'motivo' => 'Intento con último dictamen desfavorable.'];
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('Falta evaluación favorable');
    $cliente->update(['origen_recursos' => 'Cambió el origen declarado']);
    \Illuminate\Support\Facades\Storage::disk('local')->delete('prueba/ine.pdf');
    $this->post(route('solicitudes.aprobar', $solicitud), $data)->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('Los datos evaluados del cliente cambiaron')->toContain('Documento requerido');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
});

test('cambiar identidad fiscal invalida revisión y no permite aprobar persona moral', function () {
    [$user, $solicitud, $cliente] = prepararSolicitudAprobable($this);
    $cliente->datosFiscales()->update(['tipo_persona' => 'moral']);
    $this->post(route('solicitudes.aprobar', $solicitud), ['lock_version' => 3, 'motivo' => 'Intento con cambio de identidad fiscal.'])->assertSessionHasErrors('aprobacion');
    expect(session('errors')->first('aprobacion'))->toContain('solo permite aprobar personas físicas')->toContain('Los datos evaluados del cliente cambiaron');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
});

test('dictámenes son inmutables cifrados y ligados a revisión sin aprobación implícita', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)));
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(0))->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0]);
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(1))->assertSessionHasNoErrors();
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(1))->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(2, 'pld'))->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::EnRevision);
    $dictamen = $solicitud->dictamenes()->firstOrFail();
    expect($dictamen->getRawOriginal('contenido'))->not->toContain('Dictamen reservado')
        ->and($dictamen->toArray())->not->toHaveKey('contenido');
    expect(fn () => $dictamen->delete())->toThrow(ValidationException::class);
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('dictamenes.evaluacion.0.revision_actual', true)->where('dictamenes.pld.0.resultado', 'bloqueada'));
    $this->post(route('solicitudes.resolver', $solicitud), ['accion' => 'devolver', 'lock_version' => 3, 'responsable_id' => $user->id, 'motivo' => 'Corregir datos antes de nueva revisión.'])->assertSessionHasNoErrors();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 4])->assertSessionHasNoErrors();
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('dictamenes.evaluacion.0.revision_actual', false)->where('dictamenes.pld.0.revision_actual', false));
    $this->assertDatabaseCount('solicitud_dictamenes', 2);
    $this->assertDatabaseCount('sic_queries', 0);
    expect(json_encode($solicitud->eventos()->get()->toArray()))->not->toContain('Dictamen reservado');
});

test('notas reservadas y bandejas requieren permisos específicos independientes', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)));
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0]);
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(1, 'pld'))->assertSessionHasNoErrors();
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo(['read clientes', 'read solicitudes', 'update solicitudes']);
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('dictamenes.pld', null)->where('dictamenes.evaluacion', null));
    $this->get(route('cumplimiento.index'))->assertForbidden();
    $this->get(route('evaluacion-solicitudes.index'))->assertForbidden();
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(2, 'pld'))->assertForbidden();
    $user->givePermissionTo('read cumplimiento');
    $this->get(route('cumplimiento.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('bandeja', 'Cumplimiento')->where('filters.estado', 'en_revision')->where('solicitudes.total', 1));
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('dictamenes.pld.0.contenido.fundamento', solicitudDictamenDatos(1)['fundamento'])->where('dictamenes.evaluacion', null));
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(2, 'pld'))->assertForbidden();
    $user->givePermissionTo('create cumplimiento');
    $this->post(route('solicitudes.dictaminar', $solicitud), solicitudDictamenDatos(2, 'pld'))->assertSessionHasNoErrors();
});

test('dictamen exige fundamento y riesgo determinado para concluir sin observaciones', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)));
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0]);
    $this->post(route('solicitudes.dictaminar', $solicitud), ['lock_version' => 1, 'tipo_dictamen' => 'evaluacion'])
        ->assertSessionHasErrors(['fundamento' => 'El campo fundamento del dictamen es obligatorio.']);
    $this->post(route('solicitudes.dictaminar', $solicitud), [...solicitudDictamenDatos(1, 'pld'), 'resultado' => 'sin_observaciones', 'nivel_riesgo' => 'sin_determinar'])
        ->assertSessionHasErrors(['nivel_riesgo' => 'Determina el nivel de riesgo antes de concluir sin observaciones.']);
    $this->post(route('solicitudes.dictaminar', $solicitud), [...solicitudDictamenDatos(1), 'nivel_riesgo' => 'alto'])->assertSessionHasErrors('nivel_riesgo');
    $this->assertDatabaseCount('solicitud_dictamenes', 0);
});

test('devolver corregir y reenviar conserva ambas revisiones y cifra el motivo', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)))->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasNoErrors();
    $original = SolicitudRevision::firstOrFail();
    $payload = ['accion' => 'devolver', 'lock_version' => 1, 'responsable_id' => $user->id, 'motivo' => 'Corregir el destino de los recursos.'];
    $this->post(route('solicitudes.resolver', $solicitud), $payload)->assertSessionHasNoErrors();
    $this->post(route('solicitudes.resolver', $solicitud), $payload)->assertSessionHasErrors('solicitud');
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Devuelta);
    $decision = $solicitud->resoluciones()->firstOrFail();
    expect($decision->motivo)->toBe($payload['motivo'])
        ->and($decision->getRawOriginal('motivo'))->not->toContain($payload['motivo'])
        ->and($decision->toArray())->not->toHaveKey('motivo');
    expect(fn () => $decision->update(['motivo' => 'Alterado']))->toThrow(ValidationException::class);
    $this->get(route('solicitudes.show', $solicitud))->assertInertia(fn (Assert $page) => $page
        ->where('resoluciones.0.motivo', $payload['motivo']));
    $this->get(route('solicitudes.edit', $solicitud))->assertOk();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 2, 'destino' => 'Nuevo destino documentado.'])->assertSessionHasNoErrors();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 3])->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::EnRevision)
        ->and($original->fresh()->snapshot['condiciones']['destino'])->toBe('Compra de herramienta.')
        ->and($solicitud->revisiones()->where('numero', 2)->firstOrFail()->snapshot['condiciones']['destino'])->toBe('Nuevo destino documentado.');
    $this->assertDatabaseCount('solicitud_resoluciones', 1);
    $this->assertDatabaseCount('producto_version_usos', 2);
    expect(json_encode($solicitud->eventos()->get()->toArray()))->not->toContain($payload['motivo']);
});

test('cancelación es terminal y sale de pendientes sin borrar evidencia', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.resolver', $solicitud), ['accion' => 'cancelar', 'lock_version' => 0, 'motivo' => 'El cliente desistió de la solicitud.'])->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Cancelada);
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 1, 'destino' => 'Otro'])->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 1])->assertSessionHasErrors('solicitud');
    $this->patch(route('solicitudes.asignar', $solicitud), ['lock_version' => 1, 'responsable_id' => $user->id])->assertSessionHasErrors('solicitud');
    $this->get(route('solicitudes.trabajo'))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 0));
    $this->get(route('solicitudes.trabajo', ['estado' => 'cancelada']))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 1));
    $this->assertDatabaseCount('solicitudes', 1);
});

test('rechazo solo procede en revisión y exige permiso separado de captura', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)));
    $solicitud = Solicitud::firstOrFail();
    $data = ['accion' => 'rechazar', 'lock_version' => 0, 'motivo' => 'No cumple condiciones de originación.'];
    $this->post(route('solicitudes.resolver', $solicitud), $data)->assertSessionHasErrors('solicitud');
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasNoErrors();
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo(['read clientes', 'read solicitudes', 'update solicitudes']);
    $data['lock_version'] = 1;
    $this->post(route('solicitudes.resolver', $solicitud), $data)->assertForbidden();
    $user->givePermissionTo('review solicitudes');
    $this->post(route('solicitudes.resolver', $solicitud), $data)->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::Rechazada);
    $this->post(route('solicitudes.resolver', $solicitud), [...$data, 'accion' => 'cancelar', 'lock_version' => 2])->assertForbidden();
});

test('resolución valida motivo responsable y sucursal en español', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, solicitudProducto($user->id)));
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0]);
    $this->post(route('solicitudes.resolver', $solicitud), ['accion' => 'devolver', 'lock_version' => 1])
        ->assertSessionHasErrors(['motivo' => 'El campo motivo operativo es obligatorio.', 'responsable_id']);
    $data = ['accion' => 'devolver', 'lock_version' => 1, 'motivo' => 'Completar información de destino.', 'responsable_id' => $user->id];
    $recipient = \App\Models\User::factory()->create(['status' => 'inactive']);
    $this->post(route('solicitudes.resolver', $solicitud), [...$data, 'responsable_id' => $recipient->id])->assertSessionHasErrors('responsable_id');
    $user->update(['current_sucursal_id' => null]);
    $this->post(route('solicitudes.resolver', $solicitud), $data)->assertSessionHasErrors('sucursal');
    $this->assertDatabaseCount('solicitud_resoluciones', 0);
});

test('crea un borrador mínimo reanudable sin aprobar ni consultar SIC', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $data = ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()];
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
    $solicitud = Solicitud::firstOrFail();
    expect($solicitud->estado)->toBe(SolicitudEstado::Borrador)
        ->and($solicitud->responsable_id)->toBe($user->id);
    $this->get(route('solicitudes.edit', $solicitud))->assertOk();
    $this->get(route('solicitudes.show', $solicitud))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Solicitudes/Show')->where('revision', null)->has('solicitud.eventos', 1));
    $this->get(route('solicitudes.trabajo'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 1));
    $this->assertDatabaseCount('sic_queries', 0);
    $this->assertDatabaseCount('solicitud_revisiones', 0);
});

test('crear es idempotente y no reutiliza clave con otro payload', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $data = ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()];
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $this->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $this->post(route('solicitudes.store'), [...$data, 'destino' => 'Otro'])->assertSessionHasErrors('clave_creacion');
    $this->assertDatabaseCount('solicitudes', 1);
    $this->assertDatabaseCount('solicitud_eventos', 1);
});

test('guardar rechaza edición concurrente y conserva cliente y sucursal originales', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $solicitud = Solicitud::firstOrFail();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'destino' => 'Equipo'])
        ->assertSessionHasNoErrors();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'destino' => 'Sobrescribir'])
        ->assertSessionHasErrors(['solicitud' => 'La solicitud cambió. Actualiza la página antes de continuar.']);
    expect($solicitud->fresh()->destino)->toBe('Equipo');
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 1, 'cliente_id' => $cliente->id])
        ->assertSessionHasErrors('cliente_id');
    $otra = Sucursal::create(['nombre' => 'Otra', 'clave' => 'OTRA', 'activa' => true]);
    $cliente->update(['sucursal_id' => $otra->id]);
    expect($solicitud->fresh()->sucursal_id)->toBe($user->current_sucursal_id);
});

test('enviar valida producto y congela revisión y uso sin duplicación', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $version = solicitudProducto($user->id);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, $version))->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::EnRevision);
    $revision = SolicitudRevision::firstOrFail();
    expect($revision->snapshot['condiciones']['monto'])->toBe('10000.00')
        ->and($revision->snapshot['simulacion_informativa']['tabla'])->toHaveCount(13);
    $this->assertDatabaseHas('producto_version_usos', ['usable_type' => 'solicitud_revisiones', 'usable_id' => $revision->id]);
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasErrors('solicitud');
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 1, 'monto' => '20000'])
        ->assertSessionHasErrors('solicitud');
    $this->assertDatabaseCount('solicitud_revisiones', 1);
    expect(fn () => $revision->update(['snapshot_hash' => 'alterada']))->toThrow(ValidationException::class);
    $this->get(route('solicitudes.show', $solicitud))->assertOk();
});

test('producto retirado datos incompletos y condiciones fuera de rango impiden envío', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $version = solicitudProducto($user->id);
    $data = solicitudDatos($cliente, $version);
    $data['monto'] = '1.00';
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasErrors();
    app(ProductoVersionService::class)->retirar($version);
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasErrors('producto_version_id');
    $this->assertDatabaseCount('solicitud_revisiones', 0);
    $this->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $this->post(route('solicitudes.enviar', Solicitud::latest('id')->first()), ['lock_version' => 0])
        ->assertSessionHasErrors('producto_version_id');
});

test('permisos de solicitudes y sucursal se verifican también en servidor', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $data = ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()];
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo('read clientes');
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertForbidden();
    $this->get(route('solicitudes.index'))->assertForbidden();
    $this->get(route('solicitudes.clientes', ['buscar' => 'Ma']))->assertForbidden();
    $user->givePermissionTo(['read solicitudes', 'create solicitudes']);
    $this->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertForbidden();
    $this->patch(route('solicitudes.asignar', $solicitud), ['lock_version' => 0, 'responsable_id' => $user->id])->assertForbidden();
    $user->forceFill(['is_super_admin' => true, 'current_sucursal_id' => null])->save();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'destino' => 'Cambio'])->assertSessionHasErrors('sucursal');
});

test('asignación genera tarea visible al responsable y rechaza usuarios inactivos', function () {
    $actor = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $actor->current_sucursal_id]);
    $this->actingAs($actor)->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $solicitud = Solicitud::firstOrFail();
    $recipient = actingAsSuperAdmin();
    $recipient->sucursales()->attach($actor->current_sucursal_id);
    $this->actingAs($actor)->patch(route('solicitudes.asignar', $solicitud), ['lock_version' => 0, 'responsable_id' => $recipient->id])->assertSessionHasNoErrors();
    $this->actingAs($recipient)->get(route('solicitudes.trabajo'))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 1));
    $this->actingAs($actor)->get(route('solicitudes.trabajo'))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 0));
    $recipient->update(['status' => 'inactive']);
    $this->patch(route('solicitudes.asignar', $solicitud), ['lock_version' => 1, 'responsable_id' => $recipient->id])->assertSessionHasErrors('responsable_id');
});

test('solicitud impide eliminar el expediente del cliente', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $this->delete(route('clientes.destroy', $cliente))->assertSessionHasErrors('cliente');
    $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
});

test('validaciones de captura se presentan en español', function () {
    $this->actingAs(actingAsSuperAdmin())->post(route('solicitudes.store'), ['monto' => '1.123'])
        ->assertSessionHasErrors(['monto' => 'Escribe un monto positivo con máximo dos decimales.'])
        ->assertSessionHasErrors('cliente_id');
    expect(session('errors')->first('cliente_id'))->not->toContain('validation.');
});

test('un borrador permite quitar un producto retirado para recuperarse', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $version = solicitudProducto($user->id);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, $version))->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    app(ProductoVersionService::class)->retirar($version);
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'producto_version_id' => null])
        ->assertSessionHasNoErrors();
    expect($solicitud->fresh()->producto_version_id)->toBeNull();
});

test('la fecha estimada de hoy se interpreta como fecha empresarial no como instante UTC', function () {
    config(['app.timezone' => 'America/Mexico_City']);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $version = solicitudProducto($user->id);
    $data = solicitudDatos($cliente, $version);
    $data['fecha_estimada'] = app(FechaEmpresa::class)->hoy()->toDateString();
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $this->post(route('solicitudes.enviar', Solicitud::firstOrFail()), ['lock_version' => 0])->assertSessionHasNoErrors();
    expect(SolicitudRevision::firstOrFail()->snapshot['simulacion_informativa']['escenario']['fecha_disposicion'])
        ->toBe($data['fecha_estimada']);
});

<?php

use App\Models\Cliente;
use App\Models\SicQuery;
use App\Services\SICs\SicOperacionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function sicQaFixture(?Cliente $cliente = null, ?\App\Models\User $actor = null): array
{
    $actor ??= actingAsSuperAdmin();
    $nuevoCliente = $cliente === null;
    $cliente ??= Cliente::factory()->create(['sucursal_id' => $actor->current_sucursal_id, 'primer_nombre' => 'JUAN', 'apellido_paterno' => 'PRUEBA', 'fecha_nacimiento' => '1990-01-01']);
    $pais = \App\Models\Pais::create(['nombre_es' => 'México', 'nombre_us' => 'Mexico', 'codigo_iso' => 'MX', 'codigo_iso3' => 'MEX']);
    $estado = \App\Models\DivisionAdministrativa::create(['pais_id' => $pais->id, 'nombre' => 'Ciudad de México', 'nivel' => 1]);
    $cp = \App\Models\CodigoPostal::create(['codigo' => '01000', 'pais_id' => $pais->id, 'division_admin_id' => $estado->id,
        'datos_adicionales' => ['nombre_ciudad' => 'Ciudad de México']]);
    $municipio = \App\Models\DivisionAdministrativa::create(['pais_id' => $pais->id, 'nombre' => 'Álvaro Obregón', 'nivel' => 2, 'division_padre_id' => $estado->id]);
    $colonia = \App\Models\DivisionAdministrativa::create(['pais_id' => $pais->id, 'nombre' => 'Centro (Área 1)', 'nivel' => 3, 'division_padre_id' => $municipio->id]);
    $cliente->direcciones()->create(['tipo' => 'personal', 'pais_id' => $pais->id, 'codigo_postal_id' => $cp->id, 'division_admin_uno_id' => $estado->id,
        'division_admin_dos_id' => $municipio->id, 'division_admin_tres_id' => $colonia->id, 'linea_uno' => 'AV PRUEBA 1', 'coordenadas' => ['lat' => 19.35, 'lng' => -99.19]]);
    if ($nuevoCliente) {
        Storage::fake('local');
    }
    Storage::disk('local')->put('consentimiento.pdf', 'Autorización sintética');
    $c = $cliente->consentimientosSic()->create(['registrado_por' => $actor->id, 'medio' => 'firma_autografa', 'otorgado_en' => now()->subDay(),
        'evidencia_disk' => 'local', 'evidencia_path' => 'consentimiento.pdf', 'evidencia_nombre_original' => 'consentimiento.pdf', 'evidencia_mime_type' => 'application/pdf', 'evidencia_tamano_bytes' => 10]);
    config(['sic.qa_habilitado' => true, 'sic.sandbox' => true, 'sic.api_key' => 'QA-FAKE', 'sic.host' => 'https://services.circulodecredito.com.mx']);
    Queue::fake();
    test()->seed(\Database\Seeders\SicsSeeder::class);

    return [$actor, $cliente, ['api' => 'fico_score_v2', 'consentimiento_id' => $c->id, 'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true], $c];
}

test('SIC QA preserves encrypted evidence and never duplicates a provider POST', function (string $api) {
    [$actor, $cliente, $data] = sicQaFixture();
    $data['api'] = $api;
    Http::fake(['*' => Http::response(['folioConsulta' => '123', 'score' => ['nombreScore' => 'FICO', 'valor' => 680, 'razones' => ['A1']]])]);
    $this->actingAs($actor)->post(route('circulo-credito.store', $cliente), $data)->assertSessionHasNoErrors();
    $q = SicQuery::firstOrFail();
    $service = app(SicOperacionService::class);
    $service->ejecutar($q->id);
    $service->ejecutar($q->id);
    $this->post(route('circulo-credito.store', $cliente), $data)->assertSessionHasNoErrors();
    Http::assertSentCount(1);
    Http::assertSent(fn ($r) => str_contains($r->url(), '/sandbox/') && $r->header('x-api-key')[0] === 'QA-FAKE');
    Http::assertSent(function ($request) use ($api) {
        $payload = $request->data();
        $persona = $api === 'rc_fico_score' ? $payload : $payload['persona'];

        return ($persona['domicilio']['ciudad'] ?? null) === 'CIUDAD DE MEXICO'
            && $persona['domicilio']['coloniaPoblacion'] === 'CENTRO AREA 1'
            && $persona['domicilio']['delegacionMunicipio'] === 'ALVARO OBREGON';
    });
    expect($cliente->direcciones()->first()->divisionAdministrativaTres->nombre)->toBe('Centro (Área 1)')
        ->and($cliente->direcciones()->first()->codigoPostal->datos_adicionales['nombre_ciudad'])->toBe('Ciudad de México');
    expect($q->fresh()->estado_operacion)->toBe('completada');
    expect(\Illuminate\Support\Facades\DB::table('sic_evidencias')->where('tipo', 'respuesta')->value('contenido'))->not->toContain('680');
    $this->get(route('consultas-sic.show', $q))->assertOk();
    $this->assertDatabaseCount('sic_queries', 1);
})->with(['fico_score_v2', 'fintech', 'rc_fico_score']);

test('SIC rejects a missing or invalid required postal city in Spanish before recording or sending', function (string $api, ?string $city) {
    [$actor, $cliente, $data] = sicQaFixture();
    $data['api'] = $api;
    $cliente->direcciones()->first()->codigoPostal->update(['datos_adicionales' => ['nombre_ciudad' => $city]]);
    Http::fake();

    $this->actingAs($actor)->postJson(route('circulo-credito.store', $cliente), $data)->assertUnprocessable()
        ->assertJsonPath('errors.sic.0', 'El código postal seleccionado no tiene una ciudad válida para este servicio SIC. Revisa el catálogo postal antes de consultar.');

    $this->assertDatabaseCount('sic_queries', 0);
    Http::assertNothingSent();
    Queue::assertNothingPushed();
})->with(['fico_score_v2', 'rc_fico_score'])->with([null, '  ', str_repeat('A', 66)]);

test('Fintech can omit the optional city without inventing one from the municipality', function () {
    [$actor, $cliente, $data] = sicQaFixture();
    $data['api'] = 'fintech';
    $cliente->direcciones()->first()->codigoPostal->update(['datos_adicionales' => []]);
    Http::fake(['*' => Http::response(['score' => ['valor' => 680]])]);

    $this->actingAs($actor)->post(route('circulo-credito.store', $cliente), $data)->assertSessionHasNoErrors();
    $query = SicQuery::firstOrFail();
    app(SicOperacionService::class)->ejecutar($query->id);

    Http::assertSent(fn ($request) => ! array_key_exists('ciudad', $request->data()['persona']['domicilio']));
    Http::assertSentCount(1);
    expect($query->fresh()->estado_operacion)->toBe('completada');
});

test('SIC QA validates consent again before sending and cancels without network', function () {
    [$actor, $cliente, $data, $c] = sicQaFixture();
    Http::fake();
    $q = app(SicOperacionService::class)->preparar($cliente, $data, $actor);
    $c->update(['revocado_en' => now()]);
    app(SicOperacionService::class)->ejecutar($q->id);
    Http::assertNothingSent();
    expect($q->fresh()->estado_operacion)->toBe('cancelada');
});

test('SIC QA timeout is uncertain and never retried', function () {
    [$actor, $cliente, $data] = sicQaFixture();
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('No guardar mensaje sensible'));
    $service = app(SicOperacionService::class);
    $q = $service->preparar($cliente, $data, $actor);
    $service->ejecutar($q->id);
    $service->ejecutar($q->id);
    expect($q->fresh()->estado_operacion)->toBe('incierta')->and($q->fresh()->mensaje_error)->toBeNull();
});

test('SIC QA interprets provider reasons without turning every zero into nohit', function () {
    $service = new \App\Services\SICs\SicResultado;
    expect($service->normalizar(['score' => ['valor' => 0, 'razon' => ['E2']]])['estado'])->toBe('bloqueado');
    expect($service->normalizar(['score' => ['valor' => 0, 'razon' => ['E1']]])['razones'])->toBe(['E1']);
    expect($service->normalizar(['unexpected' => true])['estado'])->toBe('formato_desconocido');
});

test('uncertain SIC blocks new requests until an authorized documented reconciliation', function () {
    [$actor, $cliente, $data] = sicQaFixture();
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('sensible'));
    $service = app(SicOperacionService::class);
    $q = $service->preparar($cliente, $data, $actor);
    $service->ejecutar($q->id);
    $new = [...$data, 'idempotency_key' => (string) Str::uuid()];
    $this->actingAs($actor);
    $this->postJson(route('circulo-credito.store', $cliente), $new)->assertUnprocessable()->assertJsonValidationErrors('sic');
    $this->post(route('consultas-sic.conciliar', $q), ['motivo' => 'El proveedor confirmó que no se puede acreditar un resultado.',
        'referencia' => 'TICKET-QA-123', 'confirmacion_revision' => true])->assertSessionHasNoErrors();
    expect($q->fresh()->estado_operacion)->toBe('conciliada_sin_resultado');
    $this->post(route('circulo-credito.store', $cliente), $new)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('sic_queries', 2);
    Queue::assertPushed(\App\Jobs\ConsultarSicQa::class, 2);
});

test('interrupted SIC sends become uncertain without resending and encrypted evidence is immutable', function () {
    [$actor, $cliente, $data] = sicQaFixture();
    $service = app(SicOperacionService::class);
    $q = $service->preparar($cliente, $data, $actor);
    $q->forceFill(['estado_operacion' => 'enviando', 'updated_at' => now()->subMinutes(3)])->save();
    Http::fake();
    expect($service->marcarInciertas())->toBe(1)->and($service->marcarInciertas())->toBe(0);
    $service->ejecutar($q->id);
    Http::assertNothingSent();
    expect(fn () => $q->evidencias()->first()->delete())->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('historical results without response remain readable after encryption and plaintext removal', function () {
    [$actor, $cliente] = sicQaFixture();
    $q = historicalSicQuery($cliente, ['response_data' => null, 'mensaje_error' => null]);
    $q->results()->create(['tipo_registro' => 'score', 'data' => ['valor' => 710, 'nombreScore' => 'Modelo histórico']]);
    $this->artisan('sic:cifrar-historico --retirar-texto-plano')->assertSuccessful();
    expect(app(\App\Services\SICs\SicResultado::class)->historico($q)['scores'][0]['valor'])->toBe('710');
    $this->actingAs($actor)->get(route('consultas-sic.show', $q))->assertOk();
});

test('SIC historical encryption is resumable and preserves IDs before clearing plaintext', function () {
    [$actor, $cliente] = sicQaFixture();
    $q = historicalSicQuery($cliente, ['response_data' => ['score' => ['valor' => 700]]]);
    $q->results()->create(['tipo_registro' => 'score', 'data' => ['valor' => 700]]);
    $this->artisan('sic:cifrar-historico')->assertSuccessful();
    $this->artisan('sic:cifrar-historico')->assertSuccessful();
    $this->artisan('sic:cifrar-historico --retirar-texto-plano')->assertSuccessful();
    expect($q->fresh()->response_data)->toBeNull()->and($q->fresh()->results->first()->data)->toBeNull();
    expect(app(SicOperacionService::class)->leer($q, 'historico')['score']['valor'])->toBe(700);
});

test('required SIC approves only current revision evidence and invalidates revoked consent', function () {
    [$actor, $solicitud, $cliente] = prepararSolicitudAprobable($this, sic: 'requerido', prepararCliente: function ($cliente, $actor) use (&$data, &$consent) {
        [, , $data, $consent] = sicQaFixture($cliente, $actor);
    });
    $revision = $solicitud->revisiones()->latest('numero')->firstOrFail();
    $data['solicitud_revision_id'] = $revision->id;
    Http::fake(['*' => Http::response(['score' => ['nombreScore' => 'FICO', 'valor' => 680]])]);
    $service = app(SicOperacionService::class);
    $q = $service->preparar($cliente, $data, $actor);
    $service->ejecutar($q->id);
    $requisito = app(\App\Services\SICs\SicRequisito::class);
    $reglas = ['sic_apis' => ['fico_score_v2'], 'sic_sin_score' => 'bloquear'];
    $evidencia = $requisito->evaluar($revision->id, $cliente, $reglas);
    expect($evidencia['consulta_id'])->toBe($q->id)->and($evidencia['ambiente'])->toBe('qa');
    expect($requisito->evaluar($revision->id + 1, $cliente, $reglas))->toBeNull();
    expect($requisito->evaluar($revision->id, $cliente, [...$reglas, 'sic_apis' => ['fintech']]))->toBeNull();
    $this->post(route('solicitudes.aprobar', $solicitud), ['lock_version' => 3, 'motivo' => 'Aprobación QA con SIC vigente y revisión manual.'])->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(\App\Enums\SolicitudEstado::Aprobada);
    $consent->update(['revocado_en' => now()]);
    expect($requisito->evaluar($revision->id, $cliente, $reglas, $evidencia))->toBeNull();
});

test('SIC provider failures preserve evidence without approval and reject unsafe configuration', function (int $status, mixed $body) {
    [$actor, $cliente, $data] = sicQaFixture();
    Http::fake(['*' => Http::response($body, $status)]);
    $service = app(SicOperacionService::class);
    $q = $service->preparar($cliente, $data, $actor);
    $service->ejecutar($q->id);
    expect($q->fresh()->estado_operacion)->toBe('error')->and($q->fresh()->http_status)->toBe($status);
    expect($service->leer($q, 'respuesta'))->not->toBeNull();
    config(['sic.sandbox' => false]);
    expect(fn () => $service->preparar($cliente, [...$data, 'idempotency_key' => (string) Str::uuid()], $actor))->toThrow(\Illuminate\Validation\ValidationException::class);
    Http::assertSentCount(1);
})->with([[401, ['message' => 'Unauthorized']], [500, 'Provider unavailable'], [200, ['unexpected' => 'schema']]]);

test('late SIC response is preserved without converting an uncertain attempt into approval evidence', function () {
    [$actor, $cliente, $data] = sicQaFixture();
    $service = app(SicOperacionService::class);
    $q = $service->preparar($cliente, $data, $actor);
    Http::fake(function () use ($q) {
        $q->fresh()->forceFill(['estado_operacion' => 'incierta'])->save();

        return Http::response(['score' => ['nombreScore' => 'FICO', 'valor' => 680]]);
    });
    $service->ejecutar($q->id);
    expect($q->fresh()->estado_operacion)->toBe('incierta')->and($service->leer($q, 'resultado'))->toBeNull();
    expect($service->leer($q, 'respuesta_tardia')['score']['valor'])->toBe(680);
    Http::assertSentCount(1);
});

test('incomplete SIC address is rejected in Spanish before creating or sending an attempt', function () {
    [$actor, $cliente, $data] = sicQaFixture();
    $cliente->direcciones()->first()->divisionAdministrativaDos->update(['nombre' => '']);
    Http::fake();
    $this->actingAs($actor)->postJson(route('circulo-credito.store', $cliente), $data)->assertUnprocessable()
        ->assertJsonPath('errors.sic.0', 'Completa nombre, apellido, nacimiento y domicilio mexicano con municipio, colonia, estado y código postal válido antes de consultar.');
    $this->assertDatabaseCount('sic_queries', 0);
    Http::assertNothingSent();
    Queue::assertNothingPushed();
});

<?php

use App\Models\Activity;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Services\ActivityLogService;
use App\Services\ClienteExpedienteService;
use Illuminate\Support\Facades\Storage;

test('expediente audita relaciones altas cambios y bajas con sucursal del cliente y sin valores reservados', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $branch = Sucursal::factory()->create();
    $client = Cliente::factory()->create(['sucursal_id' => $branch->id]);
    $linked = Cliente::factory()->create();
    $service = app(ClienteExpedienteService::class);
    $service->updateProfile($client, ['ocupacion' => 'OCUPACION-RESERVADA']);
    $reference = $service->storeReferencia($client, ['tipo' => 'personal', 'nombre' => 'NOMBRE-RESERVADO', 'telefono' => '9991234567']);
    $service->updateReferencia($reference, ['nombre' => 'NOMBRE-ACTUALIZADO']);
    $link = $service->storeVinculo($client, ['cliente_vinculado_id' => $linked->id, 'rol' => 'aval']);
    $guarantee = $service->storeGarantia($client, ['tipo' => 'prendaria', 'descripcion' => 'BIEN-RESERVADO', 'valor_estimado' => '25000', 'moneda' => 'MXN']);
    $service->updateGarantia($guarantee, ['descripcion' => 'BIEN-ACTUALIZADO']);
    $before = Activity::count();
    $this->travel(1)->minutes();
    $service->updateProfile($client, ['ocupacion' => 'OCUPACION-RESERVADA']);
    $service->updateReferencia($reference, ['nombre' => 'NOMBRE-ACTUALIZADO']);
    $service->updateGarantia($guarantee, ['descripcion' => 'BIEN-ACTUALIZADO']);
    expect(Activity::count())->toBe($before);
    $service->deleteGarantia($guarantee);
    $service->deleteVinculo($link);
    $service->deleteReferencia($reference);
    assertDomainAudit(['cliente.kyc.updated', 'cliente.referencia.created', 'cliente.referencia.updated', 'cliente.referencia.deleted',
        'cliente.vinculo.created', 'cliente.vinculo.deleted', 'cliente.garantia.created', 'cliente.garantia.updated', 'cliente.garantia.deleted'],
        $actor, $branch->id, ['OCUPACION-RESERVADA', 'NOMBRE-RESERVADO', 'NOMBRE-ACTUALIZADO', 'BIEN-RESERVADO', 'BIEN-ACTUALIZADO', '9991234567']);
});

test('expediente revierte cada mutación cuando no puede guardar actividad', function (string $action) {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $client = Cliente::factory()->create();
    $service = app(ClienteExpedienteService::class);
    $reference = $service->storeReferencia($client, ['tipo' => 'personal', 'nombre' => 'Anterior', 'telefono' => '9991234567']);
    $guarantee = $service->storeGarantia($client, ['tipo' => 'prendaria', 'descripcion' => 'Anterior', 'valor_estimado' => '25000', 'moneda' => 'MXN']);
    $linked = Cliente::factory()->create();
    $link = $service->storeVinculo($client, ['cliente_vinculado_id' => $linked->id, 'rol' => 'aval']);
    $before = Activity::count();
    $this->partialMock(ActivityLogService::class)->shouldReceive('log')->andThrow(new RuntimeException('Auditoría no disponible'));
    $service = app(ClienteExpedienteService::class);
    expect(fn () => match ($action) {
        'kyc' => $service->updateProfile($client, ['ocupacion' => 'Debe revertirse']),
        'referencia.create' => $service->storeReferencia($client, ['tipo' => 'personal', 'nombre' => 'Debe revertirse', 'telefono' => '9991234567']),
        'referencia.update' => $service->updateReferencia($reference, ['nombre' => 'Debe revertirse']),
        'referencia.delete' => $service->deleteReferencia($reference),
        'garantia.create' => $service->storeGarantia($client, ['tipo' => 'prendaria', 'descripcion' => 'Debe revertirse', 'valor_estimado' => '25000', 'moneda' => 'MXN']),
        'garantia.update' => $service->updateGarantia($guarantee, ['descripcion' => 'Debe revertirse']),
        'garantia.delete' => $service->deleteGarantia($guarantee),
        'vinculo.delete' => $service->deleteVinculo($link),
    })->toThrow(RuntimeException::class);
    expect($reference->newQuery()->findOrFail($reference->id)->nombre)->toBe('Anterior')->and($guarantee->newQuery()->findOrFail($guarantee->id)->descripcion)->toBe('Anterior')
        ->and($link->newQuery()->find($link->id))->not->toBeNull()->and($client->fresh()->ocupacion)->not->toBe('Debe revertirse')
        ->and($client->referencias()->count())->toBe(1)->and($client->garantias()->count())->toBe(1)
        ->and(Activity::count())->toBe($before);
})->with(['kyc', 'referencia.create', 'referencia.update', 'referencia.delete', 'garantia.create', 'garantia.update', 'garantia.delete', 'vinculo.delete']);

test('ciclo de cliente registra cambios efectivos sin copiar datos personales', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    activarEmpresaPrueba();
    Storage::fake('local');
    $data = clientePayload(clienteFiscalCatalogs());
    $service = app(\App\Services\ClienteService::class);
    $client = $service->store($data);
    $service->update($client, ['primer_nombre' => 'MODIFICADO']);
    $this->travel(1)->minutes();
    $before = Activity::count();
    $service->update($client, ['primer_nombre' => 'MODIFICADO']);
    expect(Activity::count())->toBe($before);
    $client->documentos()->firstOrFail()->update(['disk' => 'local', 'path' => 'prueba/cliente-eliminado.pdf']);
    Storage::disk('local')->put('prueba/cliente-eliminado.pdf', 'Evidencia sintética');
    $service->destroy($client);
    Storage::disk('local')->assertMissing('prueba/cliente-eliminado.pdf');
    assertDomainAudit(['cliente.created', 'cliente.updated', 'cliente.deleted'], $actor, $actor->current_sucursal_id,
        ['MODIFICADO', $data['email'], $data['datos_fiscales']['curp']]);
});

test('ciclo contractual y cobranza persisten eventos con contexto e identificadores sin contenido reservado', function () {
    [$actor, $credit, $data] = creditoPagableQa($this);
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credit), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credit), $data)->assertSessionHasNoErrors();
    $this->post(route('creditos.pagos.store', $credit), $data)->assertSessionHasNoErrors();
    $payment = \App\Models\CreditoPago::sole();
    $reversal = ['motivo' => 'MOTIVO-RESERVADO de corrección sintética.', 'idempotency_key' => (string) str()->uuid(), 'confirmacion_qa' => true];
    $this->post(route('creditos.pagos.reverse', [$credit, $payment]), $reversal)->assertSessionHasNoErrors();
    $this->post(route('creditos.pagos.reverse', [$credit, $payment]), $reversal)->assertSessionHasNoErrors();
    assertDomainAudit(['solicitud.created', 'solicitud.submitted', 'solicitud.assessment_recorded', 'originacion.politica_created',
        'solicitud.approved', 'solicitud.signature_received', 'solicitud.signature_reviewed', 'credito.disbursed',
        'credito.payment_recorded', 'credito.payment_reversed'], $actor, $credit->sucursal_id,
        ['MOTIVO-RESERVADO', $data['referencia'], 'Dictamen reservado', 'Copia firmada QA', 'snapshot']);
    expect(Activity::where('event', 'credito.payment_recorded')->count())->toBe(1)
        ->and(Activity::where('event', 'credito.payment_reversed')->count())->toBe(1);
});

test('documentos y consentimientos auditan recepción revisión acceso y revocación en su sucursal', function () {
    Storage::fake('local');
    $actor = actingAsSuperAdmin();
    $branch = Sucursal::factory()->create();
    $client = Cliente::factory()->create(['sucursal_id' => $branch->id]);
    $file = fn () => \Illuminate\Http\UploadedFile::fake()->createWithContent('RESERVADO.pdf', "%PDF-1.4\nCONTENIDO-RESERVADO\n%%EOF");
    $this->actingAs($actor)->post(route('clientes.documentos.store', $client), ['tipo' => 'ine', 'archivo' => $file()])->assertSessionHasNoErrors();
    $document = $client->documentos()->sole();
    $this->patch(route('clientes.documentos.estado.update', [$client, $document]), ['estado' => 'validado'])->assertSessionHasNoErrors();
    $this->get(route('clientes.documentos.view', [$client, $document]))->assertOk();
    $this->get(route('clientes.documentos.download', [$client, $document]))->assertOk();
    $this->post(route('clientes.consentimientos-sic.store', $client), ['medio' => 'firma_autografa', 'otorgado_en' => now()->subMinute()->toIso8601String(), 'evidencia' => $file()])->assertSessionHasNoErrors();
    $consent = $client->consentimientosSic()->sole();
    $this->get(route('clientes.consentimientos-sic.view', [$client, $consent]))->assertOk();
    $this->get(route('clientes.consentimientos-sic.download', [$client, $consent]))->assertOk();
    $this->patch(route('clientes.consentimientos-sic.revoke', [$client, $consent]))->assertSessionHasNoErrors();
    $this->patch(route('clientes.consentimientos-sic.revoke', [$client, $consent]))->assertSessionHasNoErrors();
    assertDomainAudit(['cliente.documento.received', 'cliente.documento.status_updated', 'cliente.documento.viewed', 'cliente.documento.downloaded',
        'cliente.consentimiento_sic.created', 'cliente.consentimiento_sic.revoked', 'cliente.consentimiento_sic.evidence_viewed', 'cliente.consentimiento_sic.evidence_downloaded'],
        $actor, $branch->id, ['RESERVADO.pdf', 'CONTENIDO-RESERVADO']);
    expect(Activity::where('event', 'cliente.consentimiento_sic.revoked')->count())->toBe(1);
});

test('rollback de eliminación de cliente conserva evidencias privadas y sus registros', function () {
    Storage::fake('local');
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    $client = Cliente::factory()->create();
    $document = $client->documentos()->create(['tipo' => 'ine', 'version' => 1, 'estado' => 'recibido', 'es_actual' => true,
        'disk' => 'local', 'path' => 'prueba/privado.pdf']);
    Storage::disk('local')->put($document->path, 'EVIDENCIA-PRIVADA');
    $before = Activity::count();
    $this->partialMock(ActivityLogService::class)->shouldReceive('log')->andThrow(new RuntimeException('Auditoría no disponible'));
    expect(fn () => app(\App\Services\ClienteService::class)->destroy($client))->toThrow(RuntimeException::class)
        ->and(Cliente::whereKey($client->id)->exists())->toBeTrue()
        ->and($document->fresh())->not->toBeNull()->and(Activity::count())->toBe($before);
    Storage::disk('local')->assertExists($document->path);
});

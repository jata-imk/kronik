<?php

use App\Models\Cliente;
use App\Services\ClienteExpedienteService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a private document can be received reviewed and versioned', function () {
    Storage::fake('local');
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();
    app(ClienteExpedienteService::class)->initializeChecklist($cliente);
    $pending = $cliente->documentos()->where('tipo', 'ine')->firstOrFail();

    $this->actingAs($user)
        ->post(route('clientes.documentos.store', $cliente), [
            'tipo' => 'ine',
            'reemplaza_documento_id' => $pending->id,
            'archivo' => UploadedFile::fake()->create('ine.pdf', 120, 'application/pdf'),
            'vence_en' => now()->addYear()->format('Y-m-d'),
        ])
        ->assertRedirect();

    $received = $pending->fresh();
    expect($received->estado->value)->toBe('recibido')
        ->and($received->version)->toBe(1)
        ->and($received->path)->not->toBeNull();
    Storage::disk('local')->assertExists($received->path);

    $this->actingAs($user)
        ->patch(route('clientes.documentos.estado.update', [$cliente, $received]), [
            'estado' => 'validado',
        ])
        ->assertRedirect();

    expect($received->fresh()->estado->value)->toBe('validado')
        ->and($received->fresh()->revisado_por)->toBe($user->id);

    $this->actingAs($user)
        ->post(route('clientes.documentos.store', $cliente), [
            'tipo' => 'ine',
            'reemplaza_documento_id' => $received->id,
            'archivo' => UploadedFile::fake()->create('ine-nueva.pdf', 90, 'application/pdf'),
        ])
        ->assertRedirect();

    $replacement = $cliente->documentos()->where('tipo', 'ine')->where('es_actual', true)->firstOrFail();
    expect($received->fresh()->es_actual)->toBeFalse()
        ->and($replacement->version)->toBe(2)
        ->and($replacement->reemplaza_documento_id)->toBe($received->id)
        ->and($replacement->estado->value)->toBe('recibido');

    $this->actingAs($user)
        ->patch(route('clientes.documentos.estado.update', [$cliente, $received]), [
            'estado' => 'vencido',
        ])
        ->assertSessionHasErrors('estado');

    $otroTipo = $cliente->documentos()->where('tipo', 'constancia_fiscal')->firstOrFail();
    $this->actingAs($user)
        ->post(route('clientes.documentos.store', $cliente), [
            'tipo' => 'ine',
            'reemplaza_documento_id' => $otroTipo->id,
            'archivo' => UploadedFile::fake()->create('tipo-incorrecto.pdf', 20, 'application/pdf'),
        ])
        ->assertNotFound();
});

test('document upload validates type and rejection reason', function () {
    Storage::fake('local');
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();
    app(ClienteExpedienteService::class)->initializeChecklist($cliente);

    $this->actingAs($user)
        ->post(route('clientes.documentos.store', $cliente), [
            'tipo' => 'ine',
            'archivo' => UploadedFile::fake()->create('malware.exe', 20, 'application/octet-stream'),
        ])
        ->assertSessionHasErrors('archivo');

    $documento = $cliente->documentos()->where('tipo', 'ine')->firstOrFail();
    $documento->update(['estado' => 'recibido']);

    $this->actingAs($user)
        ->patch(route('clientes.documentos.estado.update', [$cliente, $documento]), [
            'estado' => 'rechazado',
        ])
        ->assertSessionHasErrors('motivo_rechazo');
});

test('document download is private and scoped to its client', function () {
    Storage::fake('local');
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();
    $otro = Cliente::factory()->create();
    app(ClienteExpedienteService::class)->initializeChecklist($cliente);
    $documento = $cliente->documentos()->where('tipo', 'ine')->firstOrFail();
    Storage::disk('local')->put('clientes/test/ine.pdf', 'pdf-content');
    $documento->update([
        'estado' => 'recibido',
        'disk' => 'local',
        'path' => 'clientes/test/ine.pdf',
        'nombre_original' => 'ine.pdf',
        'mime_type' => 'application/pdf',
        'tamano_bytes' => 11,
    ]);

    $this->get(route('clientes.documentos.download', [$cliente, $documento]))
        ->assertRedirect(route('login'));

    $this->actingAs($user)
        ->get(route('clientes.documentos.download', [$otro, $documento]))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('clientes.documentos.download', [$cliente, $documento]))
        ->assertOk();
});

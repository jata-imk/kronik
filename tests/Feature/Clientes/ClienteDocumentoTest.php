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
            'archivo' => UploadedFile::fake()->createWithContent('ine.pdf', "%PDF-1.4\n% test\n%%EOF"),
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
            'archivo' => UploadedFile::fake()->createWithContent('ine-nueva.pdf', "%PDF-1.4\n% nueva\n%%EOF"),
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
            'archivo' => UploadedFile::fake()->createWithContent('tipo-incorrecto.pdf', "%PDF-1.4\n% tipo\n%%EOF"),
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

    $falsePdf = $this->actingAs($user)
        ->post(route('clientes.documentos.store', $cliente), [
            'tipo' => 'ine',
            'archivo' => UploadedFile::fake()->createWithContent('documento.pdf', '<html><script>alert(1)</script></html>'),
        ]);
    $falsePdf->assertSessionHasErrors('archivo');
    expect($falsePdf->getSession()->get('errors')->first('archivo'))
        ->toContain('contenido no coincide con la extensión');

    $this->actingAs($user)
        ->post(route('clientes.documentos.store', $cliente), [
            'tipo' => 'ine',
            'archivo' => UploadedFile::fake()->createWithContent('activo.svg', '<svg onload="alert(1)"/>'),
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
    Storage::disk('local')->put('clientes/test/ine.pdf', "%PDF-1.4\n% test\n%%EOF");
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

test('rechazo exige un motivo útil en español y validar elimina el motivo previo', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();
    app(ClienteExpedienteService::class)->initializeChecklist($cliente);
    $documento = $cliente->documentos()->where('tipo', 'ine')->firstOrFail();
    $documento->update(['estado' => 'recibido']);
    $url = route('clientes.documentos.estado.update', [$cliente, $documento]);

    foreach (['', '   ', 'Corto', '  123456789  ', str_repeat('x', 2001)] as $reason) {
        $response = $this->actingAs($user)->patchJson($url, ['estado' => 'rechazado', 'motivo_rechazo' => $reason]);
        $response->assertUnprocessable()->assertJsonValidationErrors('motivo_rechazo');
        expect($response->json('errors.motivo_rechazo.0'))->not->toContain('validation.');
    }
    $this->patch($url, ['estado' => 'rechazado', 'motivo_rechazo' => '  La imagen es ilegible.  '])->assertRedirect()->assertSessionHasNoErrors();
    expect($documento->fresh()->motivo_rechazo)->toBe('La imagen es ilegible.');
    // A rejected file must be received again before validation is allowed.
    $documento->refresh()->update(['estado' => 'recibido']);
    $this->patch($url, ['estado' => 'validado', 'motivo_rechazo' => 'x'])->assertRedirect()->assertSessionHasNoErrors();
    expect($documento->fresh()->motivo_rechazo)->toBeNull();
});

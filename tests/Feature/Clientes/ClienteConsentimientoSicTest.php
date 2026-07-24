<?php

use App\Models\Cliente;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('SIC consent stores user date medium and private evidence then can be revoked', function () {
    Storage::fake('local');
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();

    $this->actingAs($user)
        ->post(route('clientes.consentimientos-sic.store', $cliente), [
            'medio' => 'firma_autografa',
            'otorgado_en' => now()->subMinute()->toIso8601String(),
            'vence_en' => now()->addYear()->format('Y-m-d'),
            'evidencia' => UploadedFile::fake()->create('consentimiento.pdf', 80, 'application/pdf'),
            'notas' => 'Documento firmado en sucursal.',
        ])
        ->assertRedirect();

    $consentimiento = $cliente->consentimientosSic()->firstOrFail();
    expect($consentimiento->registrado_por)->toBe($user->id)
        ->and($consentimiento->medio->value)->toBe('firma_autografa')
        ->and($consentimiento->revocado_en)->toBeNull();
    Storage::disk('local')->assertExists($consentimiento->evidencia_path);

    $this->actingAs($user)
        ->patch(route('clientes.consentimientos-sic.revoke', [$cliente, $consentimiento]))
        ->assertRedirect();

    expect($consentimiento->fresh()->revocado_en)->not->toBeNull();
});

test('SIC consent requires valid evidence', function () {
    Storage::fake('local');
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();

    $this->actingAs($user)
        ->post(route('clientes.consentimientos-sic.store', $cliente), [
            'medio' => 'firma_electronica',
            'otorgado_en' => now()->toIso8601String(),
        ])
        ->assertSessionHasErrors('evidencia');
});

<?php

use App\Models\Cliente;
use App\Services\SICs\SicOperacionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

test('browser SIC consent preserves its instant after persistence and is immediately usable', function (string $timezone, string $input) {
    $originalTimezone = date_default_timezone_get();
    config(['app.timezone' => $timezone]);
    date_default_timezone_set($timezone);
    $this->travelTo(Carbon::parse('2026-10-20T06:09:48Z')->setTimezone($timezone));

    try {
        Storage::fake('local');
        $user = actingAsSuperAdmin();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)->post(route('clientes.consentimientos-sic.store', $cliente), [
            'medio' => 'otro',
            'otorgado_en' => $input,
            'vence_en' => '2026-12-31',
            'evidencia' => UploadedFile::fake()->createWithContent('consentimiento.pdf', "%PDF-1.4\n% prueba\n%%EOF"),
        ])->assertSessionHasNoErrors()->assertRedirect();

        // Read a new instance: checking the in-memory assignment misses the timezone regression.
        $consentimiento = $cliente->consentimientosSic()->firstOrFail();
        expect($consentimiento->otorgado_en->toISOString())->toBe('2026-10-20T06:09:48.000000Z')
            ->and($consentimiento->vence_en->toDateString())->toBe('2026-12-31')
            ->and(app(SicOperacionService::class)->consentimientoHash($consentimiento))->toHaveLength(64);

        $this->patch(route('clientes.consentimientos-sic.revoke', [$cliente, $consentimiento]))
            ->assertSessionHasNoErrors();
        expect(fn () => app(SicOperacionService::class)->consentimientoHash($consentimiento->fresh()))
            ->toThrow(ValidationException::class, 'Se necesita un consentimiento SIC vigente con evidencia disponible.');

        $this->postJson(route('clientes.consentimientos-sic.store', $cliente), [
            'medio' => 'otro',
            'otorgado_en' => '2026-10-20T06:10:48.000Z',
            'evidencia' => UploadedFile::fake()->createWithContent('futuro.pdf', "%PDF-1.4\n% prueba\n%%EOF"),
        ])->assertUnprocessable()->assertJsonPath('errors.otorgado_en.0',
            'La fecha de consentimiento no puede ser posterior al momento actual.');
        expect($cliente->consentimientosSic()->count())->toBe(1);
    } finally {
        $this->travelBack();
        date_default_timezone_set($originalTimezone);
    }
})->with(['UTC', 'America/Mexico_City'])->with([
    '2026-10-20T06:09:48.000Z',
    '2026-10-20T00:09:48-06:00',
]);

test('SIC consent stores user date medium and private evidence then can be revoked', function () {
    Storage::fake('local');
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create();

    $this->actingAs($user)
        ->post(route('clientes.consentimientos-sic.store', $cliente), [
            'medio' => 'firma_autografa',
            'otorgado_en' => now()->subMinute()->toIso8601String(),
            'vence_en' => now()->addYear()->format('Y-m-d'),
            'evidencia' => UploadedFile::fake()->createWithContent('consentimiento.pdf', "%PDF-1.4\n% consentimiento\n%%EOF"),
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

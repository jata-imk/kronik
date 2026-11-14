<?php

use App\Enums\ActivityEvent;
use App\Models\Activity;
use App\Models\CommunicationEvent;
use App\Services\Credito\ProyeccionCreditoService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => $this->withoutVite());

test('el comando prepara solo a tres días y nunca entrega correo aunque exista contacto', function () {
    [, $credito, $data] = creditoPagableQa($this);
    $recipient = getenv('COMMUNICATION_QA_EMAIL') ?: 'destinatario@example.test';
    $credito->cliente->update(['email' => $recipient]);
    Mail::fake();
    $due = CarbonImmutable::parse($data['fecha_pago'].' 12:00:00', 'America/Mexico_City');

    $this->travelTo($due->subDays(4));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $this->artisan('comunicaciones:preparar-vencimientos')->assertSuccessful();
    $this->assertDatabaseCount('communication_events', 0);

    $this->travelTo($due->subDays(3));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $this->artisan('comunicaciones:preparar-vencimientos')->assertSuccessful();
    $this->artisan('comunicaciones:preparar-vencimientos')->assertSuccessful();
    $this->assertDatabaseCount('communication_events', 1);
    $event = CommunicationEvent::sole();
    expect($event->event_type)->toBe('upcoming_due')
        ->and($event->due_date->toDateString())->toBe($due->toDateString());

    $this->travelTo($due->subDays(2));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $this->artisan('comunicaciones:preparar-vencimientos')->assertSuccessful();
    $this->assertDatabaseCount('communication_events', 1);
    expect($event->fresh()->cancelled_at)->toBeNull();
    Mail::assertNothingSent();
    Mail::assertNothingQueued();

    expect(json_encode(DB::table('communication_events')->first(), JSON_THROW_ON_ERROR))->not->toContain($recipient);
    $audit = Activity::where('event', ActivityEvent::CommunicationEventPrepared->value)->sole();
    expect($audit->causer_id)->toBeNull()
        ->and($audit->team_id)->toBeNull()
        ->and($audit->sucursal_id)->toBe($credito->sucursal_id)
        ->and(json_encode($audit->properties, JSON_THROW_ON_ERROR))->not->toContain($recipient);
});

test('la preparación no interpreta una lectura invalidada como vencimiento confirmado', function () {
    [, $credito, $data] = creditoPagableQa($this);
    $due = CarbonImmutable::parse($data['fecha_pago'].' 12:00:00', 'America/Mexico_City');
    $this->travelTo($due->subDays(3));
    $proyecciones = app(ProyeccionCreditoService::class);
    $proyecciones->sincronizar($credito);
    $proyecciones->invalidar($credito);
    Mail::fake();

    $this->artisan('comunicaciones:preparar-vencimientos')->assertSuccessful();
    $this->assertDatabaseCount('communication_events', 0);
    $proyecciones->sincronizar($credito);
    $this->artisan('comunicaciones:preparar-vencimientos')->assertSuccessful();
    $this->assertDatabaseCount('communication_events', 1);
    Mail::assertNothingSent();
});

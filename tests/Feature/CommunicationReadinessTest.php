<?php

use App\Enums\ActivityEvent;
use App\Jobs\SendCommunicationDelivery;
use App\Models\Activity;
use App\Models\CommunicationChannel;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationEvent;
use App\Models\CommunicationRule;
use App\Services\Communications\CommunicationOrchestrator;
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
        ->and($audit->team_id)->toBe($credito->team_id)
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

test('la anticipación de cada regla cambia el día de preparación y el despacho se revalida', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $credito->cliente->update(['email' => 'cliente@example.test']);
    $due = CarbonImmutable::parse($data['fecha_pago'].' 12:00:00', 'America/Mexico_City');
    $rule = CommunicationRule::create([
        'team_id' => $credito->team_id, 'event_type' => 'upcoming_due', 'channel' => 'email',
        'enabled' => true, 'lead_days' => 5, 'timezone' => 'America/Mexico_City',
        'window_start' => '09:00:00', 'window_end' => '20:00:00', 'daily_limit' => 1,
        'subject' => 'Próximo vencimiento', 'body' => 'Hola {nombre}, vence el {fecha_vencimiento}.',
        'template_version' => 1,
    ]);
    CommunicationChannel::create(['team_id' => $credito->team_id, 'channel' => 'email', 'provider' => 'smtp',
        'sender' => 'avisos@example.test', 'eligibility' => 'approved', 'enabled' => true]);
    $service = app(CommunicationOrchestrator::class);
    $service->grantConsent($credito->cliente, $credito->team_id,
        ['channel' => 'email', 'purpose' => 'preventive', 'source' => 'QA', 'evidence_reference' => 'QA-1'], $actor);

    $this->travelTo($due->subDays(6));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $this->artisan('comunicaciones:preparar-vencimientos')->assertSuccessful();
    $this->assertDatabaseCount('communication_events', 0);

    $this->travelTo($due->subDays(5));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $this->artisan('comunicaciones:preparar-vencimientos')->assertSuccessful();
    expect(CommunicationEvent::sole()->due_date->toDateString())->toBe($due->toDateString());
    expect($service->prepare())->toBe(1);
    $rule->update(['lead_days' => 4]);
    expect($service->prepare())->toBe(0);
    config(['comunicaciones.send_enabled' => true]);
    expect($service->process())->toBe(0);

    $this->travelTo($due->subDays(4));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    expect($service->prepare())->toBe(0)->and($service->process())->toBe(1);
    $this->assertDatabaseCount('communication_deliveries', 1);
    $delivery = CommunicationDelivery::sole();
    $rule->update(['lead_days' => 3]);
    Mail::fake();
    (new SendCommunicationDelivery($delivery->id))->handle($service);
    expect($delivery->fresh()->status)->toBe('prepared');
    Mail::assertNothingSent();

    $this->travelTo($due->subDays(3));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    expect($service->process())->toBe(1);
});

test('el valor inicial de anticipación cambia desde configuración sin crear una regla', function () {
    [, $credito, $data] = creditoPagableQa($this);
    config(['comunicaciones.default_lead_days' => 5]);
    $due = CarbonImmutable::parse($data['fecha_pago'].' 12:00:00', 'America/Mexico_City');
    $this->travelTo($due->subDays(5));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $this->artisan('comunicaciones:preparar-vencimientos')->assertSuccessful();
    expect(CommunicationEvent::sole()->due_date->toDateString())->toBe($due->toDateString());
});

test('dos canales pueden preparar el mismo vencimiento en días distintos sin duplicar el evento', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $credito->cliente->update(['email' => 'cliente@example.test']);
    $due = CarbonImmutable::parse($data['fecha_pago'].' 12:00:00', 'America/Mexico_City');
    foreach (['email' => 5, 'sms' => 3] as $channel => $days) {
        CommunicationRule::create(['team_id' => $credito->team_id, 'event_type' => 'upcoming_due',
            'channel' => $channel, 'enabled' => true, 'lead_days' => $days,
            'timezone' => 'America/Mexico_City', 'window_start' => '09:00:00', 'window_end' => '20:00:00',
            'daily_limit' => 2, 'subject' => 'Vencimiento', 'body' => 'Hola {nombre}, vence el {fecha_vencimiento}.',
            'template_version' => 1]);
        CommunicationChannel::create(['team_id' => $credito->team_id, 'channel' => $channel,
            'provider' => $channel === 'sms' ? 'labsmobile' : 'smtp', 'sender' => 'avisos-qa',
            'eligibility' => 'approved', 'enabled' => true]);
        app(CommunicationOrchestrator::class)->grantConsent($credito->cliente, $credito->team_id,
            ['channel' => $channel, 'purpose' => 'preventive', 'source' => 'QA', 'evidence_reference' => 'QA-1'], $actor);
    }
    $service = app(CommunicationOrchestrator::class);
    $this->travelTo($due->subDays(5));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $this->artisan('comunicaciones:preparar-vencimientos')->assertSuccessful();
    expect($service->prepare())->toBe(1);
    $this->assertDatabaseHas('communication_deliveries', ['channel' => 'email']);

    $this->travelTo($due->subDays(3));
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $this->artisan('comunicaciones:preparar-vencimientos')->assertSuccessful();
    expect($service->prepare())->toBe(1);
    $this->assertDatabaseCount('communication_events', 1);
    $this->assertDatabaseCount('communication_deliveries', 2);
    $this->assertDatabaseHas('communication_deliveries', ['channel' => 'sms']);
});

<?php

use App\Enums\ActivityEvent;
use App\Models\CommunicationEvent;
use App\Models\MenubarItem;
use App\Models\MenubarItemModule;
use App\Services\ActivityLogService;
use App\Services\Credito\PagoService;
use App\Services\Credito\ProyeccionCreditoService;
use App\Services\MenubarCatalogoService;
use Database\Seeders\MenubarItemsSeeder;
use Database\Seeders\ModulesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(fn () => $this->withoutVite());

test('el catálogo conjunto conserva calendario comunicaciones y notificaciones sin duplicar menús', function () {
    $this->seed([ModulesAndPermissionsSeeder::class, MenubarItemsSeeder::class]);
    $routes = collect(app(MenubarCatalogoService::class)->acciones())->pluck('key');
    expect($routes)->toContain('productos-crediticios.calendario.index', 'comunicaciones.index', 'notificaciones.index')
        ->not->toContain('solicitudes.clientes', 'consultas-sic.clientes', 'comunicaciones.channels.update');
    $items = MenubarItem::count();
    $links = MenubarItemModule::count();
    $this->seed([ModulesAndPermissionsSeeder::class, MenubarItemsSeeder::class]);
    expect(MenubarItem::count())->toBe($items)->and(MenubarItemModule::count())->toBe($links);
});

test('el reverso conserva la fecha civil y cancela el aviso en su contexto original después de cambiar equipo', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $service = app(PagoService::class);
    app(ProyeccionCreditoService::class)->sincronizar($credito);
    $data['previa_hash'] = $service->previa($credito, $data, $actor)['previa_hash'];
    $payment = $service->registrar($credito, $data, $actor);
    $event = CommunicationEvent::where('event_key', 'payment:'.$payment->id)->sole();
    $originalTeam = $event->team_id;
    $otherTeam = $actor->ownedTeams()->create(['name' => 'Otro contexto sintético', 'personal_team' => false]);
    $actor->forceFill(['current_team_id' => $otherTeam->id])->save();
    setPermissionsTeamId($otherTeam->id);
    $reverse = $service->reversar($credito, $payment, ['motivo' => 'Corrección sintética civil y contextual.',
        'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true], $actor);
    expect(substr($reverse->getRawOriginal('fecha_efectiva'), 0, 10))->toBe($data['fecha_pago'])
        ->and($event->fresh()->cancellation_reason)->toBe('payment_reversed')
        ->and($event->fresh()->team_id)->toBe($originalTeam);
    $this->assertDatabaseHas('activity_log', ['event' => ActivityEvent::CommunicationEventCancelled->value,
        'subject_id' => $event->id, 'subject_type' => CommunicationEvent::class,
        'team_id' => $originalTeam, 'sucursal_id' => $credito->sucursal_id]);
    $projections = app(ProyeccionCreditoService::class);
    $projections->diagnosticar($credito);
    $projections->sincronizar($credito, reconstruir: true);
    $projections->diagnosticar($credito);
});

test('un fallo al auditar la cancelación revierte el reverso sus proyecciones y el aviso', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    $service = app(PagoService::class);
    $data['previa_hash'] = $service->previa($credito, $data, $actor)['previa_hash'];
    $payment = $service->registrar($credito, $data, $actor);
    $before = DB::table('credito_lecturas')->where('credito_id', $credito->id)->orderBy('id')->get()->toJson();
    $movements = $credito->movimientos()->count();
    $activity = Mockery::mock(ActivityLogService::class)->makePartial();
    $activity->shouldReceive('log')->andReturnUsing(function ($event) {
        if ($event === ActivityEvent::CommunicationEventCancelled) {
            throw new RuntimeException('Fallo sintético de cancelación');
        }
    });
    app()->instance(ActivityLogService::class, $activity);
    expect(fn () => $service->reversar($credito, $payment, ['motivo' => 'Corrección sintética con rollback.',
        'idempotency_key' => (string) Str::uuid(), 'confirmacion_qa' => true], $actor))
        ->toThrow(RuntimeException::class, 'Fallo sintético de cancelación');
    $this->assertDatabaseCount('credito_pagos', 1);
    expect($credito->movimientos()->count())->toBe($movements)
        ->and(CommunicationEvent::where('event_key', 'payment:'.$payment->id)->sole()->cancelled_at)->toBeNull()
        ->and(DB::table('credito_lecturas')->where('credito_id', $credito->id)->orderBy('id')->get()->toJson())->toBe($before);
});

test('el recordatorio conserva el vencimiento ajustado del contrato aunque cambie el calendario publicado', function () {
    $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-14 12:00:00', 'America/Mexico_City'));
    activarEmpresaPrueba();
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor);
    \Illuminate\Support\Facades\Storage::fake('calendario_comunicaciones');
    \Illuminate\Support\Facades\Queue::fake();
    config(['documentos.disk' => 'calendario_comunicaciones']);
    \Tests\Support\BulletScenario::habilitar();
    \Tests\Support\BulletScenario::pdfSintetico();
    $calendar = app(\App\Services\Credito\CalendarioCrediticioService::class);
    $configuration = config('calendario-crediticio.cnbv_2026');
    $first = $calendar->guardar(null, ['configuracion' => $configuration], $actor);
    $calendar->publicar($first, 1, $actor);
    $product = \Tests\Support\BulletScenario::producto($actor, 'periodicos', ajuste: 'siguiente_habil');
    $application = \Tests\Support\BulletScenario::aprobar(\Tests\Support\BulletScenario::solicitud($actor, $product), $actor);
    $package = \Tests\Support\BulletScenario::formalizar($application, $actor);
    $credit = app(\App\Services\Credito\DesembolsoService::class)->registrar($application->fresh(),
        \Tests\Support\BulletScenario::desembolso($application, $package), $actor);
    expect($credit->cronogramas()->sole()->snapshot['tabla'][1])->toMatchArray(['fecha_original' => '2026-11-14', 'fecha' => '2026-11-17']);
    $second = $calendar->guardar(null, ['configuracion' => [...$configuration,
        'excepciones' => [['fecha' => '2026-11-14', 'nombre' => 'Apertura posterior sintética', 'habil' => true]]]], $actor);
    $calendar->publicar($second, 1, $actor);
    $events = app(\App\Services\CommunicationEventService::class);
    foreach (['2026-11-11' => 0, '2026-11-14' => 1] as $date => $expected) {
        $this->travelTo(\Carbon\CarbonImmutable::parse($date.' 12:00:00', 'America/Mexico_City'));
        app(ProyeccionCreditoService::class)->sincronizar($credit);
        expect($events->reconcileDueReminders())->toBe($expected);
    }
    $event = CommunicationEvent::where('event_type', 'upcoming_due')->sole();
    expect($event->due_date->toDateString())->toBe('2026-11-17')->and($event->cancelled_at)->toBeNull();
    expect($events->reconcileDueReminders())->toBe(0);
});

<?php

use App\Enums\ActivityEvent;
use App\Enums\OperatorNotificationEvent;
use App\Models\OperatorNotification;
use App\Models\Sucursal;
use App\Models\Team;
use App\Services\OperatorNotificationService;

test('la bandeja aísla equipo sucursal y destinatario y audita una sola lectura', function () {
    $recipient = actingAsSuperAdmin();
    $teamId = $recipient->current_team_id;
    $branchId = $recipient->current_sucursal_id;
    $service = app(OperatorNotificationService::class);
    $notice = $service->publish($recipient, $teamId, $branchId,
        OperatorNotificationEvent::ApplicationAssigned, 'solicitud:1:asignada:1',
        'Solicitud asignada', 'Se te asignó una solicitud.');

    $same = $service->publish($recipient, $teamId, $branchId,
        OperatorNotificationEvent::ApplicationAssigned, 'solicitud:1:asignada:1',
        'Otro título', 'Otro cuerpo');
    expect($same->id)->toBe($notice->id)
        ->and(OperatorNotification::count())->toBe(1);

    $this->actingAs($recipient)->get(route('notifications.summary'))
        ->assertOk()->assertJsonPath('unread_count', 1)
        ->assertJsonPath('items.0.title', 'Solicitud asignada');

    $other = actingAsSuperAdmin();
    $this->actingAs($other)->get(route('notifications.summary'))->assertOk()->assertJsonPath('unread_count', 0);
    $this->patch(route('notifications.read', $notice->id))->assertNotFound();

    $this->actingAs($recipient)->patch(route('notifications.read', $notice->id))
        ->assertOk()->assertJsonPath('unread_count', 0);
    $this->patch(route('notifications.read', $notice->id))->assertOk();
    $this->assertDatabaseCount('operator_notifications', 1);
    expect(\App\Models\Activity::where('event', ActivityEvent::OperatorNotificationRead->value)->count())->toBe(1);

    $otherTeam = Team::factory()->create(['name' => 'Otro equipo de prueba']);
    $otherTeam->users()->attach($recipient);
    $recipient->forceFill(['current_team_id' => $otherTeam->id])->save();
    $this->actingAs($recipient)->get(route('notifications.summary'))->assertOk()->assertJsonCount(0, 'items');
    $this->patch(route('notifications.read', $notice->id))->assertNotFound();
    $recipient->forceFill(['current_team_id' => $teamId])->save();

    $branch = Sucursal::create(['clave' => 'NOTIF-OTRA', 'nombre' => 'Otra sucursal', 'activa' => true]);
    $recipient->sucursales()->attach($branch);
    $recipient->forceFill(['current_sucursal_id' => $branch->id])->save();
    $this->actingAs($recipient)->get(route('notifications.summary'))->assertOk()->assertJsonPath('unread_count', 0)->assertJsonCount(0, 'items');
    $this->patch(route('notifications.read', $notice->id))->assertNotFound();
});

test('no se crea aviso para un destinatario fuera del equipo o sucursal', function () {
    $recipient = actingAsSuperAdmin();
    $other = actingAsSuperAdmin();

    expect(app(OperatorNotificationService::class)->publish(
        $recipient, $other->current_team_id, $other->current_sucursal_id,
        OperatorNotificationEvent::ApplicationAssigned, 'invalida', 'Aviso', 'Mensaje',
    ))->toBeNull();
    $this->assertDatabaseCount('operator_notifications', 0);
});

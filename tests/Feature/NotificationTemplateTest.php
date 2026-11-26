<?php

use App\Models\Activity;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Communications\NotificationContent;
use App\Services\Communications\NotificationTemplateService;
use App\Services\Documentos\DocumentoRenderService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    Http::preventStrayRequests();
    Mail::fake();
});

function notificationPayload(array $changes = []): array
{
    return array_replace(['key' => 'pago-sms', 'name' => 'Pago recibido', 'description' => 'Institucional',
        'event_type' => 'payment_received', 'channel' => 'sms', 'format' => 'text',
        'body' => 'Hola {nombre}, registramos tu pago.', 'change_summary' => 'Redacción inicial'], $changes);
}

test('el catálogo global permite lectura sin gestión de comunicaciones ni documentos', function () {
    $admin = actingAsSuperAdmin();
    $template = notificationTemplateQa();
    $reader = User::factory()->withPersonalTeam()->create(['current_sucursal_id' => $admin->current_sucursal_id]);
    grantTeamPermissions($reader, $reader->currentTeam, ['read plantillas-notificaciones']);
    $this->actingAs($reader)->get(route('notificaciones.index', ['plantilla_id' => $template->id]))
        ->assertOk()->assertInertia(fn (Assert $p) => $p->where('selected.id', $template->id)->where('permissions.create', false)->where('version.can_edit', false));
    $this->post(route('notificaciones.store'), notificationPayload())->assertForbidden();
    $this->get(route('comunicaciones.index'))->assertForbidden();
    expect(Activity::where('event', 'like', 'notification_template.%')->count())->toBe(0);
});

test('cada acción exige su permiso y la consulta exacta no se sustituye por filtros o páginas', function () {
    $admin = actingAsSuperAdmin();
    $template = notificationTemplateQa();
    $version = $template->versions()->sole();
    $reader = User::factory()->withPersonalTeam()->create(['current_sucursal_id' => $admin->current_sucursal_id]);
    grantTeamPermissions($reader, $reader->currentTeam, ['read plantillas-notificaciones']);
    $this->actingAs($reader);
    $this->get(route('notificaciones.index', ['plantilla_id' => $template->id, 'version_id' => $version->id, 'buscar' => 'no coincide', 'page' => 12]))
        ->assertOk()->assertInertia(fn (Assert $p) => $p->where('selected.id', $template->id)->where('version.id', $version->id)->has('templates.data', 0));
    $this->put(route('notificaciones.update', [$template, $version]), notificationPayload())->assertForbidden();
    $this->post(route('notificaciones.duplicate', [$template, $version]))->assertForbidden();
    $this->post(route('notificaciones.activate', $version))->assertForbidden();
    $this->post(route('notificaciones.retire', $version))->assertForbidden();
    $other = notificationTemplateQa();
    $this->get(route('notificaciones.index', ['plantilla_id' => $other->id, 'version_id' => $version->id]))->assertNotFound();
});

test('crear editar versionar activar y retirar conserva redacciones exactas e inmutables', function () {
    $actor = actingAsSuperAdmin();
    $this->actingAs($actor)->post(route('notificaciones.store'), notificationPayload())->assertSessionHasNoErrors();
    $template = NotificationTemplate::sole();
    $v1 = $template->versions()->sole();
    $this->put(route('notificaciones.update', [$template, $v1]), notificationPayload(['body' => 'Gracias, {nombre}.']))->assertSessionHasNoErrors();
    $this->post(route('notificaciones.activate', $v1))->assertSessionHasNoErrors();
    $hash = $v1->fresh()->content_hash;
    $this->put(route('notificaciones.update', [$template, $v1]), notificationPayload(['body' => 'Otro']))->assertSessionHasErrors('version');
    $this->post(route('notificaciones.duplicate', [$template, $v1]))->assertSessionHasNoErrors();
    $v2 = $template->versions()->where('number', 2)->sole();
    $this->put(route('notificaciones.update', [$template, $v2]), notificationPayload(['body' => 'Nueva redacción, {nombre}.']))->assertSessionHasNoErrors();
    $this->post(route('notificaciones.activate', $v2))->assertSessionHasNoErrors();
    $this->post(route('notificaciones.activate', $v2))->assertSessionHasNoErrors();
    expect($v1->fresh()->state)->toBe('retired')->and($v1->fresh()->body)->toBe('Gracias, {nombre}.')->and($v1->fresh()->content_hash)->toBe($hash);
    expect(fn () => $v1->update(['body' => 'Alterada']))->toThrow(ValidationException::class);
    $this->post(route('notificaciones.retire', $v2))->assertSessionHasNoErrors();
    $this->post(route('notificaciones.retire', $v2))->assertSessionHasNoErrors();
    expect(Activity::where('event', 'notification_template.activated')->count())->toBe(2)
        ->and(Activity::where('event', 'notification_template.retired')->count())->toBe(2);
    $activity = Activity::where('event', 'notification_template.created')->sole();
    expect($activity->causer_id)->toBe($actor->id)->and($activity->subject_id)->toBe($template->id)
        ->and($activity->team_id)->toBe($actor->current_team_id)->and($activity->sucursal_id)->toBe($actor->current_sucursal_id)
        ->and($activity->properties->toJson())->not->toContain('Hola', 'nombre', 'Institucional');
});

test('una instancia antigua no modifica una versión activada ni mezcla pertenencias', function () {
    $this->actingAs(actingAsSuperAdmin());
    $template = notificationTemplateQa([], 'draft');
    $stale = $template->versions()->sole();
    app(NotificationTemplateService::class)->activate($stale->fresh());
    expect(fn () => app(NotificationTemplateService::class)->update($template, $stale, notificationPayload()))
        ->toThrow(ValidationException::class);
    $other = notificationTemplateQa([], 'draft');
    $this->put(route('notificaciones.update', [$other, $stale]), notificationPayload(['key' => $other->key]))->assertNotFound();
});

test('fallar la auditoría revierte contenido y cambio de estado', function () {
    $this->actingAs(actingAsSuperAdmin());
    $template = notificationTemplateQa([], 'draft');
    $version = $template->versions()->sole();
    $this->mock(ActivityLogService::class)->shouldReceive('log')->andThrow(new RuntimeException('fallo sintético'));
    expect(fn () => app(NotificationTemplateService::class)->activate($version))->toThrow(RuntimeException::class);
    expect($version->fresh()->state)->toBe('draft')->and($version->fresh()->activated_at)->toBeNull();
    expect(fn () => app(NotificationTemplateService::class)->create(notificationPayload()))->toThrow(RuntimeException::class);
    expect(NotificationTemplate::count())->toBe(1);
});

test('variables desconocidas sintaxis documental y formatos incompatibles muestran errores legibles', function (array $changes, string $field) {
    $this->actingAs(actingAsSuperAdmin())->postJson(route('notificaciones.store'), notificationPayload($changes))
        ->assertUnprocessable()->assertJsonValidationErrors($field);
    expect(json_encode(session('errors')))->not->toContain('validation.');
    expect(NotificationTemplate::count())->toBe(0);
})->with([
    [['body' => 'Hola {importe}'], 'body'], [['body' => '{{cliente.nombre_completo}}'], 'body'],
    [['body' => 'Hola {{nombre}}'], 'body'], [['format' => 'html'], 'format'],
    [['body' => '  '], 'body'], [['channel' => 'whatsapp'], 'channel'],
    [['channel' => 'email', 'format' => 'html', 'subject' => 'Aviso', 'body' => '<img data-recurso-id="123">Texto'], 'body'],
]);

test('previas guardadas y sin guardar usan datos sintéticos sin PDF ni mutaciones', function () {
    $this->actingAs(actingAsSuperAdmin());
    $this->app->bind(DocumentoRenderService::class, fn () => throw new RuntimeException('No debe resolver PDF'));
    $template = notificationTemplateQa(['body' => 'Hola {nombre}: á &amp; <b>literal</b>']);
    $version = $template->versions()->sole();
    $before = Activity::count();
    $this->getJson(route('notificaciones.preview', $version))->assertOk()->assertJsonPath('body', 'Hola María: á &amp; <b>literal</b>')
        ->assertJsonPath('format', 'text')->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    $this->postJson(route('notificaciones.preview-draft'), ['channel' => 'sms', 'format' => 'text', 'body' => 'Sin guardar, {nombre}'])
        ->assertOk()->assertJsonPath('body', 'Sin guardar, María');
    expect(NotificationTemplateVersion::count())->toBe(1)->and(Activity::count())->toBe($before)
        ->and(App\Models\CommunicationDelivery::count())->toBe(0);
    Http::assertNothingSent();
    Mail::assertNothingSent();
});

test('HTML de correo se sanea sustituye como texto y coincide con el HTML del mailable', function () {
    $this->actingAs(actingAsSuperAdmin());
    $data = ['channel' => 'email', 'format' => 'html', 'subject' => 'Aviso para {nombre}',
        'body' => '<h2 onclick="alert(1)">Hola {nombre}</h2><script>secreto()</script><p style="color:#123456;background:url(https://evil.test)">Próximo aviso</p>'];
    $response = $this->postJson(route('notificaciones.preview-draft'), $data)->assertOk();
    $html = $response->json('html');
    expect($html)->toContain('<h2>Hola María</h2>', 'default-src')->not->toContain('onclick', 'secreto()', 'evil.test');
    $render = app(NotificationContent::class)->render(app(NotificationContent::class)->content('email', $data), ['nombre' => '<img src=x onerror=alert(1)>', 'fecha_vencimiento' => '30/11/2026']);
    expect($render['body'])->toContain('&lt;img')->not->toContain('<img');
    $mail = new App\Mail\CommunicationMail('Aviso para María', $response->json('body'), 'qa@example.test', 'html');
    expect($mail->render())->toBe($html);
    $text = new App\Mail\CommunicationMail('Texto', 'Hola María', 'qa@example.test');
    expect($text->render())->toContain('Hola María')->not->toContain('<h2>');
});

test('un lector consulta históricas pero no puede publicar una previa arbitraria', function () {
    $admin = actingAsSuperAdmin();
    $template = notificationTemplateQa();
    $version = $template->versions()->sole();
    $reader = User::factory()->withPersonalTeam()->create(['current_sucursal_id' => $admin->current_sucursal_id]);
    grantTeamPermissions($reader, $reader->currentTeam, ['read plantillas-notificaciones']);
    $this->actingAs($reader)->getJson(route('notificaciones.preview', $version))->assertOk();
    $this->postJson(route('notificaciones.preview-draft'), ['channel' => 'sms', 'format' => 'text', 'body' => 'Otro'])->assertForbidden();
    $this->actingAs($admin)->postJson(route('notificaciones.preview-draft'), ['version_id' => $version->id, 'channel' => 'sms', 'format' => 'text', 'body' => 'Otro'])->assertForbidden();
});

function notificationPreparedEmail($test, string $format = 'text'): array
{
    [$actor, $credit] = communicationReadyPayment($test);
    $test->actingAs($actor);
    $rule = App\Models\CommunicationRule::sole();
    if ($format === 'html') {
        $template = notificationTemplateQa(['channel' => 'email', 'format' => 'html', 'subject' => 'Aviso {nombre}', 'body' => '<h2>Hola {nombre}</h2><p>Pago recibido.</p>']);
        $rule->update(['notification_template_id' => $template->id]);
    }
    $service = app(App\Services\Communications\CommunicationOrchestrator::class);
    $service->grantConsent($credit->cliente, $credit->team_id, ['channel' => 'email', 'purpose' => 'operational', 'source' => 'QA', 'evidence_reference' => 'EVID-1'], $actor);
    $service->prepare();

    return [$actor, $credit, App\Models\CommunicationDelivery::sole(), $rule];
}

test('sustituir o retirar cancela pendientes y no recrea el aviso con otra redacción', function (string $state, bool $replace) {
    [, , $delivery, $rule] = notificationPreparedEmail($this);
    $delivery->update(['status' => $state]);
    $version = $delivery->notificationVersion;
    $service = app(NotificationTemplateService::class);
    if ($replace) {
        $next = $service->duplicate($version->template, $version);
        $service->update($version->template, $next, ['name' => 'Nueva', 'format' => 'text', 'subject' => 'Pago', 'body' => 'Gracias, {nombre}.']);
        $service->activate($next);
    } else {
        $service->retire($version);
    }
    expect($delivery->fresh()->status)->toBe('cancelled')->and($delivery->fresh()->reason)->toBe('notification_template_changed')
        ->and($delivery->fresh()->notification_template_version_id)->toBe($version->id);
    expect(app(App\Services\Communications\CommunicationOrchestrator::class)->prepare())->toBe(0);
    expect(App\Models\CommunicationDelivery::count())->toBe(1);
    expect(Activity::where('event', 'communication.delivery_cancelled')->count())->toBe(1);
    Http::assertNothingSent();
    Mail::assertNothingSent();
})->with([['prepared', true], ['queued', true], ['prepared', false], ['queued', false]]);

test('un fallo de auditoría revierte retiro y cancelación conjuntamente', function () {
    [, , $delivery] = notificationPreparedEmail($this);
    $version = $delivery->notificationVersion;
    $this->mock(ActivityLogService::class)->shouldReceive('log')->andThrow(new RuntimeException('fallo sintético'));
    expect(fn () => app(NotificationTemplateService::class)->retire($version))->toThrow(RuntimeException::class);
    expect($version->fresh()->state)->toBe('active')->and($delivery->fresh()->status)->toBe('prepared');
});

test('cambiar selección cancela pendientes y rechaza plantillas de otro evento o canal', function () {
    [$actor, , $delivery, $rule] = notificationPreparedEmail($this);
    $data = [...$rule->toArray(), 'window_start' => '09:00', 'window_end' => '20:00'];
    $wrong = notificationTemplateQa(['channel' => 'sms']);
    $this->put(route('comunicaciones.rules.update'), [...$data, 'notification_template_id' => $wrong->id])->assertSessionHasErrors('notification_template_id');
    $new = notificationTemplateQa(['channel' => 'email']);
    $this->put(route('comunicaciones.rules.update'), [...$data, 'notification_template_id' => $new->id])->assertSessionHasNoErrors();
    expect($delivery->fresh()->status)->toBe('cancelled')->and($rule->fresh()->notification_template_id)->toBe($new->id);
    expect(app(App\Services\Communications\CommunicationOrchestrator::class)->prepare())->toBe(0);
});

test('correo usa la versión exacta y conserva snapshot cifrado fuera de props y auditoría', function () {
    [, $credit, $delivery] = notificationPreparedEmail($this, 'html');
    $credit->cliente->update(['primer_nombre' => 'NombrePrivadoQA']);
    config(['comunicaciones.send_enabled' => true]);
    $delivery->update(['status' => 'queued']);
    $service = app(App\Services\Communications\CommunicationOrchestrator::class);
    expect($service->send($delivery->id))->toBeTrue();
    expect($service->send($delivery->id))->toBeFalse();
    Mail::assertSent(App\Mail\CommunicationMail::class, fn ($mail) => $mail->messageFormat === 'html' && $mail->messageBody === '<h2>Hola NombrePrivadoQA</h2><p>Pago recibido.</p>');
    Mail::assertSentCount(1);
    $delivery->refresh();
    expect($delivery->message_snapshot['body'])->toContain('NombrePrivadoQA')
        ->and($delivery->getRawOriginal('message_snapshot'))->not->toContain('NombrePrivadoQA')
        ->and($delivery->toJson())->not->toContain('message_snapshot', 'NombrePrivadoQA');
    expect(Activity::where('event', 'like', 'communication.%')->get()->toJson())->not->toContain('NombrePrivadoQA');
    app(NotificationTemplateService::class)->retire($delivery->notificationVersion);
    expect($delivery->fresh()->status)->toBe('accepted')->and($delivery->fresh()->message_snapshot['body'])->toContain('NombrePrivadoQA');
});

test('sin selección global no se reutiliza el texto antiguo de una regla', function () {
    [, $credit, $delivery, $rule] = notificationPreparedEmail($this);
    $rule->update(['notification_template_id' => null]);
    $delivery->update(['status' => 'queued']);
    config(['comunicaciones.send_enabled' => true]);
    expect(app(App\Services\Communications\CommunicationOrchestrator::class)->send($delivery->id))->toBeFalse();
    expect($delivery->fresh()->status)->toBe('cancelled');
    Mail::assertNothingSent();
});

test('permiso de creación permite gestionar el catálogo sin ser Super Admin', function () {
    $admin = actingAsSuperAdmin();
    $operator = User::factory()->withPersonalTeam()->create(['current_sucursal_id' => $admin->current_sucursal_id]);
    grantTeamPermissions($operator, $operator->currentTeam, ['read plantillas-notificaciones', 'create plantillas-notificaciones', 'update plantillas-notificaciones', 'version plantillas-notificaciones', 'activate plantillas-notificaciones', 'retire plantillas-notificaciones']);
    $this->actingAs($operator)->post(route('notificaciones.store'), notificationPayload())->assertSessionHasNoErrors();
    $template = NotificationTemplate::sole();
    $version = $template->versions()->sole();
    $this->post(route('notificaciones.activate', $version))->assertSessionHasNoErrors();
    $this->post(route('notificaciones.retire', $version))->assertSessionHasNoErrors();
    expect($version->fresh()->state)->toBe('retired');
});

test('previa estima codificación unidades y segmentos después de sustituir', function (string $body, int $segments, string $encoding, int $units) {
    $this->actingAs(actingAsSuperAdmin())->postJson(route('notificaciones.preview-draft'), ['channel' => 'sms', 'format' => 'text', 'body' => $body])
        ->assertOk()->assertJsonPath('measurement.segments', $segments)->assertJsonPath('measurement.encoding', $encoding)->assertJsonPath('measurement.units', $units);
})->with([[str_repeat('a', 306), 2, 'GSM-7', 306], [str_repeat('a', 307), 3, 'GSM-7', 307],
    [str_repeat('á', 134), 2, 'Unicode (UTF-16)', 134], [str_repeat('á', 135), 3, 'Unicode (UTF-16)', 135],
    ['{nombre}', 1, 'Unicode (UTF-16)', 5]]);

test('version_id solo resuelve su plantilla exacta aunque los filtros la oculten', function () {
    $this->actingAs(actingAsSuperAdmin());
    notificationTemplateQa(['name' => 'Primera']);
    $target = notificationTemplateQa(['name' => 'Segunda']);
    $version = $target->versions()->sole();
    $this->get(route('notificaciones.index', ['version_id' => $version->id, 'buscar' => 'sin resultados']))
        ->assertOk()->assertInertia(fn (Assert $p) => $p->where('selected.id', $target->id)->where('version.id', $version->id));
    expect(fn () => $version->update(['state' => 'draft']))->toThrow(ValidationException::class);
});

test('eliminar la cuenta autora conserva la versión y su contenido institucional', function () {
    $author = User::factory()->create();
    $template = notificationTemplateQa(['created_by' => $author->id]);
    $version = $template->versions()->sole();
    $hash = $version->content_hash;
    Illuminate\Support\Facades\DB::table('users')->where('id', $author->id)->delete();
    expect($version->fresh()->created_by)->toBeNull()->and($version->fresh()->content_hash)->toBe($hash)
        ->and($version->fresh()->state)->toBe('active');
});

test('correo de texto conserva entidades y etiquetas literales iguales a su previa sin habilitar HTML', function () {
    $this->actingAs(actingAsSuperAdmin());
    $body = 'Hola {nombre}: á &amp; <b>literal</b>.';
    $preview = $this->postJson(route('notificaciones.preview-draft'), ['channel' => 'email', 'format' => 'text', 'subject' => 'Aviso', 'body' => $body])->assertOk();
    $mail = new App\Mail\CommunicationMail('Aviso', $preview->json('body'), 'qa@example.test');
    expect($mail->render())->toBe($preview->json('body')."\n");
    expect($mail->build()->textView)->toBe('emails.communication')->and($mail->messageFormat)->toBe('text');
    Http::assertNothingSent();
    Mail::assertNothingSent();
});

test('comunicaciones selecciona metadatos de versiones activas sin ambigüedad ni conceder lectura del cuerpo', function () {
    $admin = actingAsSuperAdmin();
    $template = notificationTemplateQa(['body' => 'CONTENIDO-INSTITUCIONAL-RESERVADO']);
    $reader = User::factory()->withPersonalTeam()->create(['current_sucursal_id' => $admin->current_sucursal_id]);
    grantTeamPermissions($reader, $reader->currentTeam, ['read comunicaciones']);
    App\Models\CommunicationRule::create(['team_id' => $reader->current_team_id, 'event_type' => 'payment_received', 'channel' => 'sms',
        'notification_template_id' => $template->id, 'body' => '']);
    $response = $this->actingAs($reader)->get(route('comunicaciones.index'))->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('canReadNotifications', false)
            ->where('notificationTemplates.0.active_version.number', 1)->where('rules.0.notification_template.active_version.number', 1));
    expect($response->getContent())->not->toContain('CONTENIDO-INSTITUCIONAL-RESERVADO');
    $this->get(route('notificaciones.index'))->assertForbidden();
});

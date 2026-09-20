<?php

use App\Enums\SolicitudEstado;
use App\Models\Cliente;
use App\Models\ProductoVersion;
use App\Models\Solicitud;
use App\Models\SolicitudRevision;
use App\Models\Sucursal;
use App\Services\FechaEmpresa;
use App\Services\ProductoVersionService;
use Database\Seeders\ModulesAndPermissionsSeeder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function solicitudProducto(int $userId): ProductoVersion
{
    $producto = app(ProductoVersionService::class)->crear([
        'clave' => 'SOL-'.Str::random(8), 'nombre' => 'Crédito de solicitud',
        'version' => [
            'monto_minimo' => '5000.00', 'monto_maximo' => '100000.00',
            'tasa_ordinaria_anual' => '36.00', 'tasa_moratoria_anual' => '72.00',
            'dias_gracia_mora' => 3, 'cat_aplica' => true,
            'periodicidades' => [['periodicidad' => 'mensual', 'plazo_minimo' => 3, 'plazo_maximo' => 24, 'plazo_predeterminado' => 12]],
            'reglas' => ['metodos_amortizacion' => ['cuota_nivelada', 'capital_fijo'], 'permite_prepago_parcial' => true,
                'permite_liquidacion_anticipada' => true, 'monto_minimo_prepago' => '500.00', 'aplicacion_prepago' => 'reducir_plazo'],
            'comisiones' => [],
        ],
    ], $userId);

    return app(ProductoVersionService::class)->activar($producto->versiones()->first(), app(FechaEmpresa::class)->hoy()->toDateString());
}

function solicitudDatos(Cliente $cliente, ProductoVersion $producto): array
{
    return [
        'cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid(),
        'producto_version_id' => $producto->id, 'monto' => '10000.00',
        'periodicidad' => 'mensual', 'plazo' => 12, 'metodo' => 'cuota_nivelada',
        'destino' => 'Compra de herramienta.', 'fecha_estimada' => app(FechaEmpresa::class)->hoy()->addWeek()->toDateString(),
    ];
}

test('crea un borrador mínimo reanudable sin aprobar ni consultar SIC', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $data = ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()];
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
    $solicitud = Solicitud::firstOrFail();
    expect($solicitud->estado)->toBe(SolicitudEstado::Borrador)
        ->and($solicitud->responsable_id)->toBe($user->id);
    $this->get(route('solicitudes.edit', $solicitud))->assertOk();
    $this->get(route('solicitudes.show', $solicitud))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Solicitudes/Show')->where('revision', null)->has('solicitud.eventos', 1));
    $this->get(route('solicitudes.trabajo'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 1));
    $this->assertDatabaseCount('sic_queries', 0);
    $this->assertDatabaseCount('solicitud_revisiones', 0);
});

test('crear es idempotente y no reutiliza clave con otro payload', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $data = ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()];
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $this->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $this->post(route('solicitudes.store'), [...$data, 'destino' => 'Otro'])->assertSessionHasErrors('clave_creacion');
    $this->assertDatabaseCount('solicitudes', 1);
    $this->assertDatabaseCount('solicitud_eventos', 1);
});

test('guardar rechaza edición concurrente y conserva cliente y sucursal originales', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $solicitud = Solicitud::firstOrFail();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'destino' => 'Equipo'])
        ->assertSessionHasNoErrors();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'destino' => 'Sobrescribir'])
        ->assertSessionHasErrors(['solicitud' => 'La solicitud cambió. Actualiza la página antes de continuar.']);
    expect($solicitud->fresh()->destino)->toBe('Equipo');
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 1, 'cliente_id' => $cliente->id])
        ->assertSessionHasErrors('cliente_id');
    $otra = Sucursal::create(['nombre' => 'Otra', 'clave' => 'OTRA', 'activa' => true]);
    $cliente->update(['sucursal_id' => $otra->id]);
    expect($solicitud->fresh()->sucursal_id)->toBe($user->current_sucursal_id);
});

test('enviar valida producto y congela revisión y uso sin duplicación', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $version = solicitudProducto($user->id);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, $version))->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEstado::EnRevision);
    $revision = SolicitudRevision::firstOrFail();
    expect($revision->snapshot['condiciones']['monto'])->toBe('10000.00')
        ->and($revision->snapshot['simulacion_informativa']['tabla'])->toHaveCount(13);
    $this->assertDatabaseHas('producto_version_usos', ['usable_type' => 'solicitud_revisiones', 'usable_id' => $revision->id]);
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasErrors('solicitud');
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 1, 'monto' => '20000'])
        ->assertSessionHasErrors('solicitud');
    $this->assertDatabaseCount('solicitud_revisiones', 1);
    expect(fn () => $revision->update(['snapshot_hash' => 'alterada']))->toThrow(ValidationException::class);
    $this->get(route('solicitudes.show', $solicitud))->assertOk();
});

test('producto retirado datos incompletos y condiciones fuera de rango impiden envío', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $version = solicitudProducto($user->id);
    $data = solicitudDatos($cliente, $version);
    $data['monto'] = '1.00';
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasErrors();
    app(ProductoVersionService::class)->retirar($version);
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertSessionHasErrors('producto_version_id');
    $this->assertDatabaseCount('solicitud_revisiones', 0);
    $this->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $this->post(route('solicitudes.enviar', Solicitud::latest('id')->first()), ['lock_version' => 0])
        ->assertSessionHasErrors('producto_version_id');
});

test('permisos de solicitudes y sucursal se verifican también en servidor', function () {
    $this->seed(ModulesAndPermissionsSeeder::class);
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $data = ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()];
    $user->forceFill(['is_super_admin' => false])->save();
    $user->givePermissionTo('read clientes');
    $this->actingAs($user)->post(route('solicitudes.store'), $data)->assertForbidden();
    $this->get(route('solicitudes.index'))->assertForbidden();
    $this->get(route('solicitudes.clientes', ['buscar' => 'Ma']))->assertForbidden();
    $user->givePermissionTo(['read solicitudes', 'create solicitudes']);
    $this->post(route('solicitudes.store'), $data)->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    $this->post(route('solicitudes.enviar', $solicitud), ['lock_version' => 0])->assertForbidden();
    $this->patch(route('solicitudes.asignar', $solicitud), ['lock_version' => 0, 'responsable_id' => $user->id])->assertForbidden();
    $user->forceFill(['is_super_admin' => true, 'current_sucursal_id' => null])->save();
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'destino' => 'Cambio'])->assertSessionHasErrors('sucursal');
});

test('asignación genera tarea visible al responsable y rechaza usuarios inactivos', function () {
    $actor = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $actor->current_sucursal_id]);
    $this->actingAs($actor)->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $solicitud = Solicitud::firstOrFail();
    $recipient = actingAsSuperAdmin();
    $recipient->sucursales()->attach($actor->current_sucursal_id);
    $this->actingAs($actor)->patch(route('solicitudes.asignar', $solicitud), ['lock_version' => 0, 'responsable_id' => $recipient->id])->assertSessionHasNoErrors();
    $this->actingAs($recipient)->get(route('solicitudes.trabajo'))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 1));
    $this->actingAs($actor)->get(route('solicitudes.trabajo'))->assertInertia(fn (Assert $page) => $page->where('solicitudes.total', 0));
    $recipient->update(['status' => 'inactive']);
    $this->patch(route('solicitudes.asignar', $solicitud), ['lock_version' => 1, 'responsable_id' => $recipient->id])->assertSessionHasErrors('responsable_id');
});

test('solicitud impide eliminar el expediente del cliente', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $this->actingAs($user)->post(route('solicitudes.store'), ['cliente_id' => $cliente->id, 'clave_creacion' => (string) Str::uuid()]);
    $this->delete(route('clientes.destroy', $cliente))->assertSessionHasErrors('cliente');
    $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
});

test('validaciones de captura se presentan en español', function () {
    $this->actingAs(actingAsSuperAdmin())->post(route('solicitudes.store'), ['monto' => '1.123'])
        ->assertSessionHasErrors(['monto' => 'Escribe un monto positivo con máximo dos decimales.'])
        ->assertSessionHasErrors('cliente_id');
    expect(session('errors')->first('cliente_id'))->not->toContain('validation.');
});

test('un borrador permite quitar un producto retirado para recuperarse', function () {
    $user = actingAsSuperAdmin();
    $cliente = Cliente::factory()->create(['sucursal_id' => $user->current_sucursal_id]);
    $version = solicitudProducto($user->id);
    $this->actingAs($user)->post(route('solicitudes.store'), solicitudDatos($cliente, $version))->assertSessionHasNoErrors();
    $solicitud = Solicitud::firstOrFail();
    app(ProductoVersionService::class)->retirar($version);
    $this->put(route('solicitudes.update', $solicitud), ['lock_version' => 0, 'producto_version_id' => null])
        ->assertSessionHasNoErrors();
    expect($solicitud->fresh()->producto_version_id)->toBeNull();
});

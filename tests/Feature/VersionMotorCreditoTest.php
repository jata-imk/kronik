<?php

use App\Enums\ProductoVersionEstado;
use App\Enums\VersionMotorCredito;
use App\Services\ProductoVersionService;
use Illuminate\Support\Facades\DB;

function productoParaVersionMotor(): array
{
    return ['clave' => 'MOTOR-PRUEBA', 'nombre' => 'Producto sintético para motor', 'version' => [
        'monto_minimo' => '0.01', 'monto_maximo' => '100000', 'tasa_ordinaria_anual' => '9', 'tasa_moratoria_anual' => '18',
        'dias_gracia_mora' => 3, 'politica_mora' => ['gracia' => 'efectiva', 'intereses' => 'ambos'], 'cat_aplica' => true,
        'fiscalidad' => ['uso' => 'prueba', 'referencia' => 'Configuración sintética sin causación fiscal',
            'ordinario' => ['tratamiento' => 'exento'], 'moratorio' => ['tratamiento' => 'exento']],
        'periodicidades' => [['periodicidad' => 'semanal', 'plazo_minimo' => 1, 'plazo_maximo' => 600, 'plazo_predeterminado' => 1]],
        'reglas' => ['metodos_amortizacion' => ['cuota_nivelada', 'capital_fijo'], 'permite_prepago_parcial' => true,
            'permite_liquidacion_anticipada' => true, 'monto_minimo_prepago' => '0', 'aplicacion_prepago' => 'reducir_pago'],
        'comisiones' => [],
    ]];
}

test('la activación congela el motor nuevo y simulación y uso conservan su versión', function () {
    $actor = actingAsSuperAdmin();
    $servicio = app(ProductoVersionService::class);
    $producto = $servicio->crear(productoParaVersionMotor(), $actor->id);
    $version = $servicio->activar($producto->versiones()->first(), app(\App\Services\FechaEmpresa::class)->hoy()->toDateString());
    expect($version->snapshot['motor_calculo_version'])->toBe(2)
        ->and($version->snapshot['reglas']['convencion_interes'])->toBe('dias_reales_360');
    $antes = $version->getRawOriginal('snapshot');
    $this->actingAs($actor)->postJson(route('productos-crediticios.simular', $version), [
        'monto' => '100', 'periodicidad' => 'semanal', 'plazo' => 1, 'metodo' => 'capital_fijo', 'fecha' => '2026-01-01',
    ])->assertOk()->assertJsonPath('motor_calculo_version', 2)->assertJsonPath('convencion_interes', 'dias_reales_360')->assertJsonPath('tabla.1.interes', '0.18');
    $uso = $servicio->registrarUso($version, 'prueba_version_motor', 1);
    expect($uso->snapshot['motor_calculo_version'])->toBe(2)
        ->and($version->fresh()->getRawOriginal('snapshot'))->toBe($antes);
});

test('la evidencia antigua conserva su motor hasta crear una nueva versión de producto', function () {
    $actor = actingAsSuperAdmin();
    $servicio = app(ProductoVersionService::class);
    $producto = $servicio->crear(productoParaVersionMotor(), $actor->id);
    $version = $servicio->activar($producto->versiones()->first(), app(\App\Services\FechaEmpresa::class)->hoy()->toDateString());
    $snapshot = $version->snapshot;
    unset($snapshot['motor_calculo_version']);
    unset($snapshot['reglas']['convencion_interes']);
    // Simula evidencia anterior a esta entrega; no es un procedimiento operativo.
    $json = json_encode($snapshot, JSON_THROW_ON_ERROR);
    DB::table('producto_versiones')->where('id', $version->id)->update(['snapshot' => $json, 'snapshot_hash' => hash('sha256', $json)]);
    $version = $version->fresh();
    $antes = $version->getRawOriginal('snapshot');
    $this->actingAs($actor)->postJson(route('productos-crediticios.simular', $version), [
        'monto' => '100', 'periodicidad' => 'semanal', 'plazo' => 1, 'metodo' => 'capital_fijo', 'fecha' => '2026-01-01',
    ])->assertOk()->assertJsonPath('motor_calculo_version', 1)->assertJsonPath('tabla.1.interes', '0.17');
    $copia = $servicio->nuevaVersion($producto, $version, $actor->id);
    $this->travel(1)->days();
    $nueva = $servicio->activar($copia, app(\App\Services\FechaEmpresa::class)->hoy()->toDateString());
    expect($nueva->snapshot['motor_calculo_version'])->toBe(VersionMotorCredito::actual()->value)
        ->and($version->fresh()->getRawOriginal('snapshot'))->toBe($antes)
        ->and($version->fresh()->estado)->toBe(ProductoVersionEstado::Retirada);
});

test('la simulación rechaza el calendario inválido con un mensaje financiero en español', function () {
    $actor = actingAsSuperAdmin();
    $producto = app(ProductoVersionService::class)->crear(productoParaVersionMotor(), $actor->id);
    $this->actingAs($actor)->postJson(route('productos-crediticios.simular', $producto->versiones()->first()), [
        'monto' => '0.06', 'periodicidad' => 'semanal', 'plazo' => 12, 'metodo' => 'capital_fijo', 'fecha' => '2026-01-01',
    ])->assertUnprocessable()->assertJsonValidationErrors('metodo')
        ->assertJsonPath('errors.metodo.0', 'El monto, la tasa y el calendario no permiten amortizaciones positivas dentro del capital pendiente. Revisa el plazo, el monto o el método seleccionado.');
});

test('activar el motor corregido conserva un solo evento con actor contexto y metadatos permitidos', function () {
    $actor = actingAsSuperAdmin();
    $producto = app(ProductoVersionService::class)->crear(productoParaVersionMotor(), $actor->id);
    $version = $producto->versiones()->first();
    $this->actingAs($actor)->post(route('productos-crediticios.activar', $version), [
        'vigente_desde' => app(\App\Services\FechaEmpresa::class)->hoy()->toDateString(),
    ])->assertSessionHasNoErrors();
    $evento = \App\Models\Activity::where('event', \App\Enums\ActivityEvent::CreditProductActivated->value)->sole();
    expect($evento->causer_id)->toBe($actor->id)->and($evento->subject_id)->toBe($producto->id)
        ->and($evento->team_id)->toBe($actor->current_team_id)->and($evento->sucursal_id)->toBe($actor->current_sucursal_id)
        ->and($evento->properties->all())->not->toHaveKeys(['snapshot', 'condiciones', 'capital', 'intereses', 'fiscalidad']);
    $this->postJson(route('productos-crediticios.simular', $version), [
        'monto' => '100', 'periodicidad' => 'semanal', 'plazo' => 1, 'metodo' => 'capital_fijo', 'fecha' => '2026-01-01',
    ])->assertOk();
    expect(\App\Models\Activity::where('event', \App\Enums\ActivityEvent::CreditProductActivated->value)->count())->toBe(1);
});

test('el pago real conserva motor y numeradores sin duplicar auditoría al reintentar', function () {
    [$actor, $credito, $data] = creditoPagableQa($this);
    expect($credito->condiciones['producto']['motor_calculo_version'])->toBe(2)
        ->and($credito->cronogramas()->first()->snapshot['motor_calculo_version'])->toBe(2)
        ->and($credito->condiciones['producto']['reglas']['convencion_interes'])->toBe('dias_reales_360')
        ->and($credito->cronogramas()->first()->snapshot['convencion_interes'])->toBe('dias_reales_360');
    $data['previa_hash'] = $this->postJson(route('creditos.pagos.preview', $credito), $data)->assertOk()->json('previa_hash');
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    $this->post(route('creditos.pagos.store', $credito), $data)->assertSessionHasNoErrors();
    $pago = $credito->pagos()->sole();
    expect($pago->snapshot['despues']['producto']['motor_calculo_version'])->toBe(2)
        ->and($pago->snapshot['despues']['cargos']['1:ordinario'])->toHaveKey('generado_numerador');
    $evento = \App\Models\Activity::where('event', \App\Enums\ActivityEvent::CreditPaymentRecorded->value)->sole();
    expect($evento->causer_id)->toBe($actor->id)->and($evento->subject_id)->toBe($credito->id)
        ->and($evento->sucursal_id)->toBe($credito->sucursal_id)
        ->and($evento->properties->all())->not->toHaveKeys(['snapshot', 'referencia', 'importe', 'capital', 'intereses']);
});

test('no acepta una convención futura al guardar un producto ni emite actividad por el intento', function () {
    $actor = actingAsSuperAdmin();
    $data = productoParaVersionMotor();
    $data['version']['reglas']['convencion_interes'] = 'dias_reales_365';
    $eventos = \App\Models\Activity::count();
    $respuesta = $this->actingAs($actor)->postJson(route('productos-crediticios.store'), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('version.reglas.convencion_interes');
    expect($respuesta->json('errors')['version.reglas.convencion_interes'][0])
        ->toBe('La convención de días seleccionada no está habilitada. Actualmente solo se admiten días reales sobre base 360.');
    $this->assertDatabaseCount('productos_crediticios', 0);
    expect(\App\Models\Activity::count())->toBe($eventos);
});

test('simular y activar bloquean una convención no soportada sin cambiar estado ni emitir activación', function () {
    $actor = actingAsSuperAdmin();
    $producto = app(ProductoVersionService::class)->crear(productoParaVersionMotor(), $actor->id);
    $version = $producto->versiones()->first();
    $version->reglas()->update(['convencion_interes' => '30e_360']);
    $this->actingAs($actor)->postJson(route('productos-crediticios.simular', $version), [
        'monto' => '100', 'periodicidad' => 'semanal', 'plazo' => 1, 'metodo' => 'capital_fijo', 'fecha' => '2026-01-01',
    ])->assertUnprocessable()->assertJsonValidationErrors('convencion_interes')
        ->assertJsonPath('errors.convencion_interes.0', 'La convención de días de este registro no está soportada. Solicita revisión sin modificar sus condiciones históricas.');
    $this->postJson(route('productos-crediticios.activar', $version), [
        'vigente_desde' => app(\App\Services\FechaEmpresa::class)->hoy()->toDateString(),
    ])->assertUnprocessable()->assertJsonValidationErrors('convencion_interes');
    expect($version->fresh()->estado)->toBe(ProductoVersionEstado::Borrador)
        ->and($version->fresh()->snapshot)->toBeNull()
        ->and(\App\Models\Activity::where('event', \App\Enums\ActivityEvent::CreditProductActivated->value)->count())->toBe(0);
});

test('la simulación toma la convención congelada aunque se altere la fila mutable de reglas', function () {
    $actor = actingAsSuperAdmin();
    $servicio = app(ProductoVersionService::class);
    $producto = $servicio->crear(productoParaVersionMotor(), $actor->id);
    $version = $servicio->activar($producto->versiones()->first(), app(\App\Services\FechaEmpresa::class)->hoy()->toDateString());
    $antes = $version->getRawOriginal('snapshot');
    // Alteración sintética para comprobar qué evidencia consume; no es una operación soportada.
    $version->reglas()->update(['convencion_interes' => '30e_360']);
    $this->actingAs($actor)->postJson(route('productos-crediticios.simular', $version), [
        'monto' => '100', 'periodicidad' => 'semanal', 'plazo' => 1, 'metodo' => 'capital_fijo', 'fecha' => '2026-01-01',
    ])->assertOk()->assertJsonPath('convencion_interes', 'dias_reales_360')->assertJsonPath('tabla.1.interes', '0.18');
    expect($version->fresh()->getRawOriginal('snapshot'))->toBe($antes);
});

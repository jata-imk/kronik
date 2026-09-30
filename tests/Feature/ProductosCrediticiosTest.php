<?php

use App\Enums\ProductoVersionEstado;
use App\Models\ConceptoComision;
use App\Models\ProductoVersion;
use App\Services\ProductoVersionService;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

function productoPayload(array $version = []): array
{
    return [
        'clave' => 'CS-001',
        'nombre' => 'Crédito Simple Esencial',
        'descripcion' => 'Producto de prueba',
        'version' => [
            'monto_minimo' => '5000.00',
            'monto_maximo' => '100000.00',
            'tasa_ordinaria_anual' => '36.00',
            'tasa_moratoria_anual' => '72.00',
            'dias_gracia_mora' => 3,
            'cat_aplica' => true,
            'cat_no_aplica_motivo' => null,
            'vigente_desde' => null,
            'periodicidades' => [['periodicidad' => 'mensual', 'plazo_minimo' => 3, 'plazo_maximo' => 24, 'plazo_predeterminado' => 12]],
            'reglas' => ['metodos_amortizacion' => ['cuota_nivelada', 'capital_fijo'], 'permite_prepago_parcial' => true, 'permite_liquidacion_anticipada' => true, 'monto_minimo_prepago' => '500.00', 'aplicacion_prepago' => 'reducir_plazo'],
            'comisiones' => [],
            ...$version,
        ],
    ];
}

function fiscalidadPrueba(): array
{
    return ['uso' => 'prueba', 'referencia' => 'Configuración sintética QA',
        'ordinario' => ['tratamiento' => 'exento', 'tasa' => null, 'base' => null],
        'moratorio' => ['tratamiento' => 'gravado', 'tasa' => '16.12345678', 'base' => 'importe_concepto']];
}

function escenarioFiscal(array $extra = []): array
{
    return ['monto' => '10000', 'periodicidad' => 'mensual', 'plazo' => 3, 'metodo' => 'capital_fijo', 'fecha' => '2026-01-01', 'incluir_impuestos' => true, ...$extra];
}

test('simulación desglosa interés e impuesto sin convertir el principal en base ni alterar CAT', function () {
    $user = actingAsSuperAdmin();
    $policy = fiscalidadPrueba();
    $policy['ordinario'] = ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto'];
    $product = app(ProductoVersionService::class)->crear(productoPayload(['fiscalidad' => $policy]), $user->id);
    $version = $product->versiones()->first();
    $url = route('productos-crediticios.simular', $version);
    $base = $this->actingAs($user)->postJson($url, escenarioFiscal(['incluir_impuestos' => false]))->assertOk();
    $tax = $this->postJson($url, escenarioFiscal())->assertOk();
    $tax->assertJsonPath('tabla.1.interes', '310.00')->assertJsonPath('tabla.1.impuestos', '49.60')
        ->assertJsonPath('tabla.1.pago_total', '3692.93')->assertJsonPath('tabla.3.saldo', '0.00')
        ->assertJsonPath('fiscalidad.estado', 'proyeccion')->assertJsonPath('fiscalidad.uso', 'prueba');
    expect($tax->json('cat'))->toBe($base->json('cat'));
    $total = '0.00';
    foreach ($tax->json('tabla') as $row) {
        $total = App\Support\Decimal::add($total, $row['impuestos'], 2);
        if ($row['numero']) {
            $payment = App\Support\Decimal::add(App\Support\Decimal::add($row['capital'], $row['interes']), $row['impuestos'], 2);
            expect($row['pago_total'])->toBe($payment);
        }
    }
    expect($tax->json('total_impuestos'))->toBe($total)
        ->and($base->json('fiscalidad.estado'))->toBe('no_calculada')
        ->and($base->json('total_impuestos'))->toBeNull();
});

test('impuesto de comisiones sigue modalidad sin duplicarse en cuotas', function (string $modalidad, string $saldo, string $efectivo, string $pagoInicial) {
    $user = actingAsSuperAdmin();
    $concepto = ConceptoComision::create(['clave' => 'COM-FISCAL', 'nombre' => 'Apertura QA', 'activo' => true]);
    $product = app(ProductoVersionService::class)->crear(productoPayload(['tasa_ordinaria_anual' => '0', 'fiscalidad' => fiscalidadPrueba(), 'comisiones' => [[
        'concepto_comision_id' => $concepto->id, 'tipo_importe' => 'fijo', 'importe' => '100', 'base_calculo' => 'no_aplica', 'momento_cobro' => 'inicio', 'modalidad_cobro' => $modalidad, 'obligatoria' => true,
        'fiscalidad' => ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto'],
    ]]]), $user->id);
    $version = $product->versiones()->first();
    $url = route('productos-crediticios.simular', $version);
    $base = $this->actingAs($user)->postJson($url, escenarioFiscal(['incluir_impuestos' => false]))->assertOk();
    $result = $this->postJson($url, escenarioFiscal())->assertOk();
    $result->assertJsonPath('escenario.saldo_financiado', $saldo)->assertJsonPath('escenario.efectivo_entregado', $efectivo)
        ->assertJsonPath('tabla.0.pago_total', $pagoInicial)->assertJsonPath('tabla.0.comisiones', '100.00')
        ->assertJsonPath('tabla.0.impuestos', '16.00')->assertJsonPath('total_impuestos', '16.00')
        ->assertJsonPath('tabla.1.impuestos', '0.00')->assertJsonPath('tabla.3.saldo', '0.00');
    expect($result->json('cat'))->toBe($base->json('cat'));
    $capital = array_reduce($result->json('tabla'), fn ($sum, $row) => App\Support\Decimal::add($sum, $row['capital'], 2), '0.00');
    expect($capital)->toBe($saldo)->and($result->json('total_pagar'))->toBe(App\Support\Decimal::add($saldo, $pagoInicial, 2));
})->with([
    ['financiada', '10116.00', '10000.00', '0.00'],
    ['descuento_desembolso', '10000.00', '9884.00', '0.00'],
    ['pago_separado', '10000.00', '10000.00', '116.00'],
]);

test('solo valida impuestos de conceptos aplicados y bloquea opcionales sin definir al seleccionarlas', function () {
    $user = actingAsSuperAdmin();
    $concepto = ConceptoComision::create(['clave' => 'OPT-FISCAL', 'nombre' => 'Asistencia QA', 'activo' => true]);
    $product = app(ProductoVersionService::class)->crear(productoPayload(['fiscalidad' => fiscalidadPrueba(), 'comisiones' => [[
        'concepto_comision_id' => $concepto->id, 'tipo_importe' => 'fijo', 'importe' => '100', 'base_calculo' => 'no_aplica', 'momento_cobro' => 'cada_pago', 'obligatoria' => false,
        'fiscalidad' => ['tratamiento' => 'no_definido'],
    ]]]), $user->id);
    $version = $product->versiones()->first();
    $url = route('productos-crediticios.simular', $version);
    $this->actingAs($user)->postJson($url, escenarioFiscal())->assertOk()->assertJsonPath('total_impuestos', '0.00');
    $result = $this->postJson($url, escenarioFiscal(['comisiones_opcionales' => [$version->comisiones()->first()->id]]))->assertUnprocessable()->assertJsonValidationErrors('fiscalidad');
    expect($result->json('errors.fiscalidad.0'))->toContain('Asistencia QA')->not->toContain('validation.');
    $version->comisiones()->first()->update(['fiscalidad' => ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto']]);
    $this->postJson($url, escenarioFiscal(['comisiones_opcionales' => [$version->comisiones()->first()->id]]))
        ->assertOk()->assertJsonPath('total_impuestos', '48.00')->assertJsonPath('tabla.1.impuestos', '16.00');
});

test('simulación antigua no asume impuesto cero y la nueva bloquea fiscalidad ausente', function () {
    $user = actingAsSuperAdmin();
    $product = app(ProductoVersionService::class)->crear(productoPayload(), $user->id);
    $url = route('productos-crediticios.simular', $product->versiones()->first());
    $this->actingAs($user)->postJson($url, escenarioFiscal(['incluir_impuestos' => false]))->assertOk()->assertJsonPath('fiscalidad.estado', 'no_calculada');
    $response = $this->postJson($url, escenarioFiscal())->assertUnprocessable()->assertJsonValidationErrors('fiscalidad');
    expect($response->json('errors.fiscalidad.0'))->toContain('configuración fiscal')->not->toContain('validation.');
    $this->postJson($url, escenarioFiscal(['incluir_impuestos' => 'incorrecto']))->assertUnprocessable()->assertJsonValidationErrors('incluir_impuestos');
});

test('impuesto financiado participa del saldo y del límite sin mutar la versión histórica', function () {
    $user = actingAsSuperAdmin();
    $concepto = ConceptoComision::create(['clave' => 'FIN-IMP', 'nombre' => 'Financiada QA', 'activo' => true]);
    $fiscal = fiscalidadPrueba();
    $fiscal['ordinario'] = ['tratamiento' => 'gravado', 'tasa' => '16', 'base' => 'importe_concepto'];
    $service = app(ProductoVersionService::class);
    $product = $service->crear(productoPayload(['fiscalidad' => $fiscal, 'comisiones' => [[
        'concepto_comision_id' => $concepto->id, 'tipo_importe' => 'fijo', 'importe' => '100', 'base_calculo' => 'no_aplica', 'momento_cobro' => 'inicio', 'modalidad_cobro' => 'financiada', 'obligatoria' => true, 'fiscalidad' => $fiscal['ordinario'],
    ]]]), $user->id);
    $version = $service->activar($product->versiones()->first(), today()->toDateString());
    $snapshot = $version->snapshot;
    $hash = $version->snapshot_hash;
    $this->actingAs($user)->postJson(route('productos-crediticios.simular', $version), escenarioFiscal())->assertOk()
        ->assertJsonPath('tabla.1.interes', '313.60')->assertJsonPath('tabla.1.impuestos', '50.18')
        ->assertJsonPath('tabla.1.pago_total', '3735.78')->assertJsonPath('totales.impuestos_financiados', '16.00')
        ->assertJsonPath('totales.financiado_comisiones', '100.00');
    expect($version->refresh()->snapshot)->toBe($snapshot)->and($version->snapshot_hash)->toBe($hash);
    $copy = $service->nuevaVersion($product, $version, $user->id)->refresh();
    $copy->update(['monto_maximo' => '10100']);
    $this->postJson(route('productos-crediticios.simular', $copy), escenarioFiscal())->assertUnprocessable()->assertJsonValidationErrors('monto');
});

test('política de mora es explícita versionada e inmutable sin reinterpretar históricos', function () {
    $user = actingAsSuperAdmin();
    $service = app(ProductoVersionService::class);
    $policy = ['gracia' => 'retroactiva', 'intereses' => 'sustituye'];
    $data = productoPayload(['politica_mora' => $policy]);
    $this->actingAs($user)->post(route('productos-crediticios.store'), $data)->assertSessionHasNoErrors();
    $version = ProductoVersion::firstOrFail();
    expect($version->politica_mora)->toBe($policy);
    unset($data['version']['politica_mora']);
    $this->put(route('productos-crediticios.update', [$version->producto, $version]), $data)->assertSessionHasNoErrors();
    expect($version->refresh()->politica_mora)->toBe($policy);
    $service->activar($version, today()->toDateString());
    $version->refresh();
    expect($version->snapshot['politica_mora'])->toBe($policy);
    expect(fn () => $version->update(['politica_mora' => null]))->toThrow(Illuminate\Validation\ValidationException::class);
    $copy = $service->nuevaVersion($version->producto, $version->fresh(), $user->id);
    expect($copy->politica_mora)->toBe($policy);
    $copy->refresh()->update(['politica_mora' => null]);
    expect($version->fresh()->snapshot['politica_mora'])->toBe($policy);
    expect($copy->fresh()->politica_mora)->toBeNull();
});

test('mora histórica no adquiere reglas por defecto y la validación protege el servicio', function () {
    $service = app(ProductoVersionService::class);
    $version = $service->crear(productoPayload(), null)->versiones()->first();
    expect($version->politica_mora)->toBeNull();
    $service->activar($version, today()->toDateString());
    expect($version->fresh()->snapshot['politica_mora'])->toBeNull();
    $data = productoPayload(['politica_mora' => ['gracia' => 'efectiva', 'intereses' => 'inventado']]);
    $data['clave'] = 'INVALIDA';
    expect(fn () => $service->crear($data, null))->toThrow(Illuminate\Validation\ValidationException::class);
});

test('política de mora inválida muestra errores en español', function (mixed $policy) {
    $user = actingAsSuperAdmin();
    $this->actingAs($user)->post(route('productos-crediticios.store'), productoPayload(['politica_mora' => $policy]))->assertSessionHasErrors();
    expect(implode(' ', session('errors')->all()))->not->toContain('validation.');
    $this->assertDatabaseCount('producto_versiones', 0);
})->with([[[]], [['gracia' => 'efectiva', 'intereses' => null]], [['gracia' => 'otra', 'intereses' => 'ambos']], [['gracia' => 'efectiva', 'intereses' => 'ambos', 'extra' => true]]]);

test('fiscalidad se conserva por concepto en versiones snapshots y clientes anteriores', function () {
    $user = actingAsSuperAdmin();
    $concepto = ConceptoComision::create(['clave' => 'FISCAL-QA', 'nombre' => 'Comisión QA', 'activo' => true]);
    $comision = ['concepto_comision_id' => $concepto->id, 'tipo_importe' => 'fijo', 'importe' => '100', 'base_calculo' => 'no_aplica', 'momento_cobro' => 'cada_pago', 'obligatoria' => true,
        'fiscalidad' => ['tratamiento' => 'no_causa', 'tasa' => null, 'base' => null]];
    $data = productoPayload(['fiscalidad' => fiscalidadPrueba(), 'comisiones' => [$comision]]);
    $this->actingAs($user)->post(route('productos-crediticios.store'), $data)->assertSessionHasNoErrors();
    $version = ProductoVersion::firstOrFail();
    expect($version->fiscalidad)->toBe(fiscalidadPrueba())
        ->and($version->comisiones()->first()->fiscalidad['tratamiento'])->toBe('no_causa');

    unset($data['version']['fiscalidad'], $data['version']['comisiones'][0]['fiscalidad']);
    $this->put(route('productos-crediticios.update', [$version->producto, $version]), $data)->assertSessionHasNoErrors();
    expect($version->refresh()->fiscalidad)->toBe(fiscalidadPrueba())
        ->and($version->comisiones()->first()->fiscalidad['tratamiento'])->toBe('no_causa');
    $service = app(ProductoVersionService::class);
    $version = $service->activar($version, today()->toDateString());
    $hash = $version->snapshot_hash;
    expect($version->snapshot['fiscalidad'])->toBe(fiscalidadPrueba())
        ->and($version->snapshot['comisiones'][0]['fiscalidad']['tratamiento'])->toBe('no_causa');
    $copy = $service->nuevaVersion($version->producto, $version, $user->id);
    expect($copy->fiscalidad)->toBe($version->fiscalidad)
        ->and($copy->comisiones()->first()->fiscalidad)->toBe($version->comisiones()->first()->fiscalidad);
    $copy->refresh()->update(['fiscalidad' => null]);
    expect($version->refresh()->snapshot_hash)->toBe($hash)
        ->and(fn () => $version->update(['fiscalidad' => null]))->toThrow(Illuminate\Validation\ValidationException::class)
        ->and(fn () => $version->comisiones()->first()->update(['fiscalidad' => null]))->toThrow(Illuminate\Validation\ValidationException::class);
});

test('fiscalidad inválida se rechaza con mensajes españoles sin asumir tasa o base', function (string $path, mixed $value, string $error) {
    $user = actingAsSuperAdmin();
    $data = productoPayload(['fiscalidad' => fiscalidadPrueba()]);
    data_set($data, $path, $value);
    $this->actingAs($user)->post(route('productos-crediticios.store'), $data)->assertSessionHasErrors([$error]);
    expect(implode(' ', session('errors')->all()))->not->toContain('validation.');
    $this->assertDatabaseCount('productos_crediticios', 0);
})->with([
    ['version.fiscalidad.moratorio.tasa', null, 'version.fiscalidad.moratorio.tasa'],
    ['version.fiscalidad.moratorio.base', null, 'version.fiscalidad.moratorio.base'],
    ['version.fiscalidad.moratorio.base', 'monto_credito', 'version.fiscalidad.moratorio.base'],
    ['version.fiscalidad.moratorio.tasa', '-1', 'version.fiscalidad.moratorio.tasa'],
    ['version.fiscalidad.moratorio.tasa', '16.123456789', 'version.fiscalidad.moratorio.tasa'],
    ['version.fiscalidad.ordinario.tasa', '0', 'version.fiscalidad.ordinario.tasa'],
    ['version.fiscalidad.ordinario.tratamiento', 'otro', 'version.fiscalidad.ordinario.tratamiento'],
    ['version.fiscalidad.ordinario', 'exento', 'version.fiscalidad.ordinario'],
    ['version.fiscalidad.uso', 'produccion', 'version.fiscalidad.uso'],
    ['version.fiscalidad', ['uso' => 'institucional', 'ordinario' => ['tratamiento' => 'no_definido'], 'moratorio' => ['tratamiento' => 'no_definido']], 'version.fiscalidad.referencia'],
    ['version.fiscalidad.extra', 'no permitido', 'version.fiscalidad'],
    ['version.fiscalidad', [], 'version.fiscalidad'],
]);

test('fiscalidad legacy queda indefinida y validación también protege llamadas directas', function () {
    $service = app(ProductoVersionService::class);
    $product = $service->crear(productoPayload(), null);
    $version = $service->activar($product->versiones()->first(), today()->toDateString());
    expect($version->fiscalidad)->toBeNull()->and($version->snapshot['fiscalidad'])->toBeNull();
    $bad = fiscalidadPrueba();
    $bad['moratorio']['tasa'] = null;
    $data = productoPayload(['fiscalidad' => $bad]);
    $data['clave'] = 'FISCAL-INVALID';
    expect(fn () => $service->crear($data, null))->toThrow(Illuminate\Validation\ValidationException::class);
});

test('la tasa cero requiere declaración explícita y las comisiones gravadas requieren su propia base', function () {
    $user = actingAsSuperAdmin();
    $concepto = ConceptoComision::create(['clave' => 'IMPUESTO-QA', 'nombre' => 'Cargo QA', 'activo' => true]);
    $fiscalidad = fiscalidadPrueba();
    $fiscalidad['uso'] = 'institucional';
    $data = productoPayload(['fiscalidad' => $fiscalidad, 'comisiones' => [[
        'concepto_comision_id' => $concepto->id, 'tipo_importe' => 'porcentaje', 'importe' => '2',
        'base_calculo' => 'monto_credito', 'momento_cobro' => 'cada_pago', 'obligatoria' => true,
        'fiscalidad' => ['tratamiento' => 'gravado', 'tasa' => '0', 'base' => null],
    ]]]);
    $this->actingAs($user)->post(route('productos-crediticios.store'), $data)->assertSessionHasErrors(['version.comisiones.0.fiscalidad.base']);
    expect(session('errors')->first('version.comisiones.0.fiscalidad.base'))->not->toContain('validation.');
    $data['version']['comisiones'][0]['fiscalidad']['base'] = 'importe_concepto';
    $this->post(route('productos-crediticios.store'), $data)->assertSessionHasNoErrors();
    $version = ProductoVersion::firstOrFail();
    expect($version->fiscalidad['uso'])->toBe('institucional')
        ->and($version->comisiones()->first()->fiscalidad)->toBe(['tratamiento' => 'gravado', 'tasa' => '0', 'base' => 'importe_concepto']);
});

test('una comisión fiscal configurada sin uso de prueba o institucional es rechazada', function () {
    $data = productoPayload();
    $data['version']['comisiones'] = [['fiscalidad' => ['tratamiento' => 'no_causa']]];
    expect(fn () => App\Support\FiscalidadProducto::validate($data['version']))->toThrow(Illuminate\Validation\ValidationException::class);
});

test('super admin puede crear y consultar productos globales', function () {
    $user = actingAsSuperAdmin();

    $this->actingAs($user)->post(route('productos-crediticios.store'), productoPayload())->assertRedirect();

    $this->assertDatabaseHas('productos_crediticios', ['clave' => 'CS-001']);
    $this->assertDatabaseHas('producto_versiones', ['numero' => 1, 'estado' => 'borrador']);
    $this->actingAs($user)->get(route('productos-crediticios.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('ProductosCrediticios/Index')->has('productos', 1));
});

test('activar congela la versión y versionar conserva el histórico', function () {
    $user = actingAsSuperAdmin();
    $producto = app(ProductoVersionService::class)->crear(productoPayload(), $user->id);
    $version = $producto->versiones()->first();

    $this->actingAs($user)->post(route('productos-crediticios.activar', $version), ['vigente_desde' => today()->toDateString()])->assertSessionHasNoErrors()->assertRedirect();
    $version->refresh();

    expect($version->estado)->toBe(ProductoVersionEstado::Activa)
        ->and($version->snapshot_hash)->toHaveLength(64);
    expect(fn () => $version->update(['monto_maximo' => '999999']))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'inmutables');

    $this->actingAs($user)->post(route('productos-crediticios.versionar', [$producto, $version]))->assertRedirect();
    expect($producto->versiones()->count())->toBe(2)
        ->and($producto->versiones()->latest('numero')->first()->estado)->toBe(ProductoVersionEstado::Borrador);
});

test('una versión utilizada tampoco admite edición destructiva', function () {
    $user = actingAsSuperAdmin();
    $producto = app(ProductoVersionService::class)->crear(productoPayload(), $user->id);
    $version = $producto->versiones()->first();
    app(ProductoVersionService::class)->registrarUso($version, 'solicitudes', 42);

    $payload = productoPayload(['monto_maximo' => '120000.00']);
    $this->actingAs($user)->put(route('productos-crediticios.update', [$producto, $version]), $payload)
        ->assertSessionHasErrors(['version']);
});

test('validación visible es clara y rechaza comisión tardía junto con mora', function () {
    $user = actingAsSuperAdmin();
    $concepto = ConceptoComision::create(['clave' => 'PAGO_TARDIO', 'nombre' => 'Pago tardío', 'activo' => true]);
    $payload = productoPayload([
        'monto_minimo' => null,
        'comisiones' => [[
            'concepto_comision_id' => $concepto->id,
            'tipo_importe' => 'fijo', 'importe' => '100', 'base_calculo' => 'no_aplica',
            'momento_cobro' => 'evento', 'obligatoria' => true, 'incluye_cat' => false,
        ]],
    ]);

    $response = $this->actingAs($user)->post(route('productos-crediticios.store'), $payload);
    $response->assertSessionHasErrors(['version.monto_minimo', 'version.comisiones.0.concepto_comision_id']);
    expect(session('errors')->get('version.monto_minimo')[0])->not->toContain('validation.')
        ->and(session('errors')->get('version.comisiones.0.concepto_comision_id')[0])->toContain('tasa moratoria');
    expect(collect(session('errors')->all())->implode(' '))->not->toContain('validation.')
        ->and(session('errors')->get('version.monto_maximo')[0])->toBe('El monto máximo debe ser mayor o igual que el monto mínimo.');
});

test('simulador produce tabla sin residuos y CAT informativo', function () {
    $user = actingAsSuperAdmin();
    $producto = app(ProductoVersionService::class)->crear(productoPayload(), $user->id);
    $version = $producto->versiones()->first();

    $response = $this->actingAs($user)->postJson(route('productos-crediticios.simular', $version), [
        'monto' => '15000', 'periodicidad' => 'mensual', 'plazo' => 12,
        'metodo' => 'cuota_nivelada', 'fecha' => '2026-01-31',
    ])->assertOk();

    $response->assertJsonPath('tabla.12.saldo', '0.00')
        ->assertJsonPath('tabla.0.tipo', 'disposicion')
        ->assertJsonPath('tabla.1.dias', 28);
    expect($response->json('cat'))->not->toBeNull();
});

test('simulador hace visible apertura y comisión porcentual de cada pago', function () {
    $user = actingAsSuperAdmin();
    $apertura = ConceptoComision::create(['clave' => 'APERTURA-TEST', 'nombre' => 'Apertura', 'activo' => true]);
    $administracion = ConceptoComision::create(['clave' => 'ADMIN-TEST', 'nombre' => 'Administración', 'activo' => true]);
    $producto = app(ProductoVersionService::class)->crear(productoPayload([
        'monto_minimo' => '5000.00',
        'comisiones' => [
            [
                'concepto_comision_id' => $apertura->id, 'tipo_importe' => 'fijo', 'importe' => '500',
                'base_calculo' => 'no_aplica', 'momento_cobro' => 'inicio', 'modalidad_cobro' => 'pago_separado',
                'obligatoria' => true, 'incluye_cat' => true,
            ],
            [
                'concepto_comision_id' => $administracion->id, 'tipo_importe' => 'porcentaje', 'importe' => '1',
                'base_calculo' => 'monto_credito', 'momento_cobro' => 'cada_pago', 'modalidad_cobro' => null,
                'obligatoria' => true, 'incluye_cat' => true,
            ],
        ],
    ]), $user->id);

    $response = $this->actingAs($user)->postJson(route('productos-crediticios.simular', $producto->versiones()->first()), [
        'monto' => '5000', 'periodicidad' => 'mensual', 'plazo' => 12,
        'metodo' => 'cuota_nivelada', 'fecha' => '2026-08-16',
    ])->assertOk();

    $response
        ->assertJsonPath('tabla.0.comisiones', '500.00')
        ->assertJsonPath('tabla.1.saldo_inicial', '5000.00')
        ->assertJsonPath('tabla.1.capital', '348.64')
        ->assertJsonPath('tabla.1.interes', '155.00')
        ->assertJsonPath('tabla.1.comisiones', '50.00')
        ->assertJsonPath('tabla.1.pago_total', '553.64')
        ->assertJsonPath('tabla.1.saldo_final', '4651.36')
        ->assertJsonPath('tabla.12.saldo_final', '0.00')
        ->assertJsonPath('total_intereses', '1043.68')
        ->assertJsonPath('total_comisiones', '1100.00')
        ->assertJsonPath('total_pagar', '7143.68');
});

test('comisión financiada consume el monto máximo del producto', function () {
    $user = actingAsSuperAdmin();
    $apertura = ConceptoComision::create(['clave' => 'APERTURA-FIN', 'nombre' => 'Apertura financiada', 'activo' => true]);
    $producto = app(ProductoVersionService::class)->crear(productoPayload([
        'monto_minimo' => '1000.00', 'monto_maximo' => '5000.00',
        'comisiones' => [[
            'concepto_comision_id' => $apertura->id, 'tipo_importe' => 'fijo', 'importe' => '500',
            'base_calculo' => 'no_aplica', 'momento_cobro' => 'inicio', 'modalidad_cobro' => 'financiada',
            'obligatoria' => true, 'incluye_cat' => true,
        ]],
    ]), $user->id);

    $this->actingAs($user)->postJson(route('productos-crediticios.simular', $producto->versiones()->first()), [
        'monto' => '4800', 'periodicidad' => 'mensual', 'plazo' => 12,
        'metodo' => 'cuota_nivelada', 'fecha' => '2026-08-16',
    ])->assertUnprocessable()->assertJsonValidationErrors('monto');
});

test('simulador distingue las tres modalidades de comisión inicial', function (string $modalidad, string $saldo, string $entregado, string $flujoNeto, string $pagoInicial) {
    $user = actingAsSuperAdmin();
    $concepto = ConceptoComision::create([
        'clave' => 'INICIAL-'.strtoupper($modalidad),
        'nombre' => 'Comisión inicial',
        'activo' => true,
    ]);
    $producto = app(ProductoVersionService::class)->crear(productoPayload([
        'comisiones' => [[
            'concepto_comision_id' => $concepto->id,
            'tipo_importe' => 'fijo',
            'importe' => '500',
            'base_calculo' => 'no_aplica',
            'momento_cobro' => 'inicio',
            'modalidad_cobro' => $modalidad,
            'obligatoria' => true,
            'incluye_cat' => true,
        ]],
    ]), $user->id);

    $response = $this->actingAs($user)->postJson(route('productos-crediticios.simular', $producto->versiones()->first()), [
        'monto' => '5000', 'periodicidad' => 'mensual', 'plazo' => 12,
        'metodo' => 'cuota_nivelada', 'fecha' => '2026-08-16',
    ])->assertOk();

    $response
        ->assertJsonPath('escenario.saldo_financiado', $saldo)
        ->assertJsonPath('escenario.efectivo_entregado', $entregado)
        ->assertJsonPath('escenario.flujo_neto_inicial', $flujoNeto)
        ->assertJsonPath('tabla.0.pago_total', $pagoInicial)
        ->assertJsonPath('tabla.0.comisiones_detalle.0.modalidad', $modalidad)
        ->assertJsonPath('tabla.12.saldo_final', '0.00');
})->with([
    'pago separado' => ['pago_separado', '5000.00', '5000.00', '4500.00', '500.00'],
    'descuento de disposición' => ['descuento_desembolso', '5000.00', '4500.00', '4500.00', '0.00'],
    'financiada' => ['financiada', '5500.00', '5000.00', '5000.00', '0.00'],
]);

test('clave duplicada de concepto devuelve mensaje legible en español', function () {
    $user = actingAsSuperAdmin();
    ConceptoComision::create(['clave' => 'DUPLICADA', 'nombre' => 'Primera', 'activo' => true]);

    $this->actingAs($user)->post(route('conceptos-comision.store'), [
        'clave' => 'DUPLICADA', 'nombre' => 'Segunda', 'descripcion' => null, 'referencia_reco' => null,
        'es_oficial_reco' => false, 'revisado' => false, 'activo' => true,
    ])->assertSessionHasErrors(['clave' => 'La clave del concepto de comisión ya está en uso.']);
});

test('tratamiento CAT se deriva de obligatoriedad y momento de cobro', function () {
    $user = actingAsSuperAdmin();
    $apertura = ConceptoComision::create(['clave' => 'CAT-APERTURA', 'nombre' => 'Apertura', 'activo' => true]);
    $opcional = ConceptoComision::create(['clave' => 'CAT-OPCIONAL', 'nombre' => 'Servicio opcional', 'activo' => true]);
    $evento = ConceptoComision::create(['clave' => 'CAT-EVENTO', 'nombre' => 'Evento', 'activo' => true]);
    $producto = app(ProductoVersionService::class)->crear(productoPayload([
        'comisiones' => [
            ['concepto_comision_id' => $apertura->id, 'tipo_importe' => 'fijo', 'importe' => '100', 'base_calculo' => 'no_aplica', 'momento_cobro' => 'inicio', 'modalidad_cobro' => 'pago_separado', 'obligatoria' => true, 'incluye_cat' => false],
            ['concepto_comision_id' => $opcional->id, 'tipo_importe' => 'fijo', 'importe' => '100', 'base_calculo' => 'no_aplica', 'momento_cobro' => 'cada_pago', 'modalidad_cobro' => null, 'obligatoria' => false, 'incluye_cat' => true],
            ['concepto_comision_id' => $evento->id, 'tipo_importe' => 'fijo', 'importe' => '100', 'base_calculo' => 'no_aplica', 'momento_cobro' => 'evento', 'modalidad_cobro' => null, 'obligatoria' => true, 'incluye_cat' => true],
        ],
    ]), $user->id);

    $comisiones = $producto->versiones()->first()->comisiones()->get()->keyBy('concepto_comision_id');
    expect($comisiones[$apertura->id]->incluye_cat)->toBeTrue()
        ->and($comisiones[$opcional->id]->incluye_cat)->toBeFalse()
        ->and($comisiones[$evento->id]->incluye_cat)->toBeFalse();
});

test('simulador permite opcionales determinísticas sin alterar el CAT base', function () {
    $user = actingAsSuperAdmin();
    $apertura = ConceptoComision::create(['clave' => 'SIM-BASE', 'nombre' => 'Apertura base', 'activo' => true]);
    $opcional = ConceptoComision::create(['clave' => 'SIM-OPCIONAL', 'nombre' => 'Asistencia opcional', 'activo' => true]);
    $producto = app(ProductoVersionService::class)->crear(productoPayload([
        'comisiones' => [
            ['concepto_comision_id' => $apertura->id, 'tipo_importe' => 'fijo', 'importe' => '100', 'base_calculo' => 'no_aplica', 'momento_cobro' => 'inicio', 'modalidad_cobro' => 'pago_separado', 'obligatoria' => true],
            ['concepto_comision_id' => $opcional->id, 'tipo_importe' => 'fijo', 'importe' => '500', 'base_calculo' => 'no_aplica', 'momento_cobro' => 'inicio', 'modalidad_cobro' => 'financiada', 'obligatoria' => false],
        ],
    ]), $user->id);
    $version = $producto->versiones()->first();
    $opcionalId = $version->comisiones()->where('concepto_comision_id', $opcional->id)->value('id');
    $payload = ['monto' => '5000', 'periodicidad' => 'mensual', 'plazo' => 12, 'metodo' => 'cuota_nivelada', 'fecha' => '2026-08-16'];

    $base = $this->actingAs($user)->postJson(route('productos-crediticios.simular', $version), $payload)->assertOk();
    $seleccionada = $this->actingAs($user)->postJson(route('productos-crediticios.simular', $version), [...$payload, 'comisiones_opcionales' => [$opcionalId]])->assertOk();

    expect($seleccionada->json('cat'))->toBe($base->json('cat'))
        ->and($seleccionada->json('cat_contexto'))->toBe('base_obligatorio')
        ->and($seleccionada->json('escenario.saldo_financiado'))->toBe('5500.00')
        ->and($seleccionada->json('total_intereses'))->not->toBe($base->json('total_intereses'))
        ->and($seleccionada->json('comisiones_opcionales_seleccionadas.0.id'))->toBe($opcionalId)
        ->and($seleccionada->json('tabla.0.comisiones_detalle.1.obligatoria'))->toBeFalse();
});

test('simulador rechaza comisiones ajenas o condicionadas con mensaje en español', function () {
    $user = actingAsSuperAdmin();
    $evento = ConceptoComision::create(['clave' => 'SIM-EVENTO', 'nombre' => 'Liquidación', 'activo' => true]);
    $producto = app(ProductoVersionService::class)->crear(productoPayload([
        'comisiones' => [[
            'concepto_comision_id' => $evento->id, 'tipo_importe' => 'fijo', 'importe' => '100', 'base_calculo' => 'no_aplica',
            'momento_cobro' => 'liquidacion', 'modalidad_cobro' => null, 'obligatoria' => false,
        ]],
    ]), $user->id);
    $version = $producto->versiones()->first();

    $this->actingAs($user)->postJson(route('productos-crediticios.simular', $version), [
        'monto' => '5000', 'periodicidad' => 'mensual', 'plazo' => 12, 'metodo' => 'cuota_nivelada',
        'fecha' => '2026-08-16', 'comisiones_opcionales' => [$version->comisiones()->value('id')],
    ])->assertUnprocessable()->assertJsonValidationErrors(['comisiones_opcionales'])
        ->assertJsonPath('errors.comisiones_opcionales.0', 'Seleccione únicamente comisiones opcionales de inicio o de cada pago pertenecientes a esta versión.');
});

test('programación usa fecha empresarial y conserva activa anterior hasta la vigencia', function () {
    CarbonImmutable::setTestNow('2026-08-22 12:00:00');
    $user = actingAsSuperAdmin();
    $service = app(ProductoVersionService::class);
    $producto = $service->crear(productoPayload(), $user->id);
    $primera = $producto->versiones()->first();
    $service->activar($primera, '2026-08-22');
    $segunda = $service->nuevaVersion($producto, $primera, $user->id);

    $this->actingAs($user)->post(route('productos-crediticios.activar', $segunda), ['vigente_desde' => '2026-08-24'])->assertSessionHasNoErrors();
    expect($segunda->refresh()->estado)->toBe(ProductoVersionEstado::Programada)
        ->and($segunda->snapshot_hash)->toHaveLength(64)
        ->and($primera->refresh()->estado)->toBe(ProductoVersionEstado::Activa);

    CarbonImmutable::setTestNow('2026-08-24 00:01:00');
    expect($service->activarProgramadas())->toBe(1)
        ->and($segunda->refresh()->estado)->toBe(ProductoVersionEstado::Activa)
        ->and($primera->refresh()->estado)->toBe(ProductoVersionEstado::Retirada);
    CarbonImmutable::setTestNow();
});

test('activación rechaza fechas pasadas y retiro excluye nuevas originaciones', function () {
    CarbonImmutable::setTestNow('2026-08-22 12:00:00');
    $user = actingAsSuperAdmin();
    $producto = app(ProductoVersionService::class)->crear(productoPayload(), $user->id);
    $version = $producto->versiones()->first();

    $this->actingAs($user)->post(route('productos-crediticios.activar', $version), ['vigente_desde' => '2026-08-21'])
        ->assertSessionHasErrors(['vigente_desde' => 'La fecha de vigencia debe ser hoy o una fecha futura según la zona horaria de la empresa.']);
    app(ProductoVersionService::class)->activar($version, '2026-08-22');
    $version->refresh();
    expect(ProductoVersion::query()->disponiblesParaOriginacion(CarbonImmutable::parse('2026-08-22'))->pluck('id'))->toContain($version->id);

    app(ProductoVersionService::class)->registrarUso($version, 'creditos', 99);
    app(ProductoVersionService::class)->retirar($version);
    expect(ProductoVersion::query()->disponiblesParaOriginacion(CarbonImmutable::parse('2026-08-22'))->pluck('id'))->not->toContain($version->id)
        ->and($version->usos()->first()->snapshot)->not->toBeEmpty();
    CarbonImmutable::setTestNow();
});

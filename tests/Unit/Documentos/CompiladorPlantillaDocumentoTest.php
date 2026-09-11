<?php

use App\Enums\DocumentoPlantillaTipo;
use App\Services\Documentos\CompiladorPlantillaDocumento;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

test('resuelve variables conocidas y escapa valores antes de insertarlos', function () {
    $compiler = app(CompiladorPlantillaDocumento::class);
    $html = '<h1>Hola {{cliente.nombre_completo}}</h1><script>alert(1)</script><img src="x" onerror="alert(2)">';

    $result = $compiler->render($html, ['cliente.nombre_completo' => '<b>Cliente</b>']);

    expect($result)->toContain('&lt;b&gt;Cliente&lt;/b&gt;')
        ->not->toContain('<script')
        ->not->toContain('alert(1)')
        ->not->toContain('<img')
        ->not->toContain('onerror');
});

test('extrae variables sin duplicados y rechaza claves arbitrarias', function () {
    $compiler = app(CompiladorPlantillaDocumento::class);

    expect($compiler->variables('{{cliente.rfc}}', '{{ cliente.rfc }} {{empresa.rfc}}'))
        ->toBe(['cliente.rfc', 'empresa.rfc']);

    expect(fn () => $compiler->validateForType(DocumentoPlantillaTipo::Contrato, '{{cliente.__secret}}'))
        ->toThrow(ValidationException::class, 'no existe');
});

test('impide usar variables de garantía en otra clase documental', function () {
    expect(fn () => app(CompiladorPlantillaDocumento::class)->validateForType(
        DocumentoPlantillaTipo::ConsentimientoSic,
        '{{garantia.descripcion}}',
    ))->toThrow(ValidationException::class, 'no está disponible');
});

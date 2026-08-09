<?php

use App\Enums\ClienteDocumentoEstado;
use App\Enums\ClienteDocumentoTipo;
use App\Services\ActivityLogService;
use App\Services\ClienteDocumentoService;

test('required document checklist has the four KYC document types', function () {
    expect(array_map(fn ($tipo) => $tipo->value, ClienteDocumentoTipo::requeridos()))
        ->toBe([
            'ine',
            'comprobante_domicilio',
            'constancia_fiscal',
            'comprobante_ingresos',
        ]);
});

test('document status transitions preserve the review workflow', function () {
    $service = new ClienteDocumentoService(new ActivityLogService);

    expect($service->canTransition(ClienteDocumentoEstado::Recibido, ClienteDocumentoEstado::Validado))->toBeTrue()
        ->and($service->canTransition(ClienteDocumentoEstado::Recibido, ClienteDocumentoEstado::Rechazado))->toBeTrue()
        ->and($service->canTransition(ClienteDocumentoEstado::Validado, ClienteDocumentoEstado::Vencido))->toBeTrue()
        ->and($service->canTransition(ClienteDocumentoEstado::Pendiente, ClienteDocumentoEstado::Validado))->toBeFalse()
        ->and($service->canTransition(ClienteDocumentoEstado::Rechazado, ClienteDocumentoEstado::Recibido))->toBeFalse();
});

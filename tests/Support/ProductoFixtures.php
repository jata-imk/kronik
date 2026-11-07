<?php

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

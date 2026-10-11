<?php

return [
    'qa_habilitado' => env('SIC_QA_HABILITADO', false),
    'host' => env('CIRCULO_CREDITO_HOST', 'https://services.circulodecredito.com.mx'),
    'api_key' => env('CIRCULO_CREDITO_API_KEY'),
    'sandbox' => env('CIRCULO_CREDITO_SANDBOX', true),
    'ca_bundle' => env('SIC_CA_BUNDLE'),
    'connect_timeout' => 10,
    'timeout' => 30,
    'servicios' => [
        'fico_score_v2' => ['nombre' => 'FICO Score v2', 'ruta' => '/sandbox/v2/ficoscore'],
        'fintech' => ['nombre' => 'Fintech Score', 'ruta' => '/sandbox/v1/fintechscore/'],
        'rc_fico_score' => ['nombre' => 'Reporte de Crédito + FICO', 'ruta' => '/sandbox/v1/rcficoscore'],
    ],
];

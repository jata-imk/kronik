<?php

return [
    // Explicit deployment gate. Do not enable before validating operator policies.
    'aprobaciones_habilitadas' => env('ORIGINACION_APROBACIONES_HABILITADAS', false),
    // QA documents and manual signature review never authorize real-money operation.
    'paquetes_qa_habilitados' => env('ORIGINACION_PAQUETES_QA_HABILITADOS', false),
    'firmas_qa_habilitadas' => env('ORIGINACION_FIRMAS_QA_HABILITADAS', false),
    'desembolsos_qa_habilitados' => env('ORIGINACION_DESEMBOLSOS_QA_HABILITADOS', false),
];

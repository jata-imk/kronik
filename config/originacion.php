<?php

return [
    // Explicit deployment gate. Do not enable before validating operator policies.
    'aprobaciones_habilitadas' => env('ORIGINACION_APROBACIONES_HABILITADAS', false),
    // P4a only generates conspicuously marked QA documents, never signed contracts.
    'paquetes_qa_habilitados' => env('ORIGINACION_PAQUETES_QA_HABILITADOS', false),
];

<?php

return [
    'disk' => env('DOCUMENTOS_DISK', 'local'),
    'renderer' => env('DOCUMENTOS_PDF_RENDERER', 'browsershot'),
    'renderer_timeout' => (int) env('DOCUMENTOS_PDF_TIMEOUT', 55),
    'max_upload_kb' => (int) env('DOCUMENTOS_MAX_UPLOAD_KB', 10240),
    'node_binary' => env('DOCUMENTOS_NODE_BINARY'),
    'npm_binary' => env('DOCUMENTOS_NPM_BINARY'),
    'node_modules_path' => env('DOCUMENTOS_NODE_MODULES_PATH'),
    'chrome_path' => env('DOCUMENTOS_CHROME_PATH'),
];

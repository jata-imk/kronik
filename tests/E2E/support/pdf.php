<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (! app()->environment('testing')) {
    fwrite(STDERR, "La comprobación PDF requiere APP_ENV=testing.\n");
    exit(1);
}

// No domain data or database writes; renderer failures retain their actual cause in CI.
try {
    $pdf = app(App\Contracts\DocumentoPdfRenderer::class)->render(
        '<p>Documento sintético aislado.</p>',
        '<p>Encabezado de prueba con fuente local.</p>',
        '<p>Pie de prueba.</p>',
    );
    if (! str_starts_with($pdf, '%PDF-')) {
        throw new RuntimeException('El renderer no produjo un PDF válido.');
    }
    echo 'PDF real aprobado: '.strlen($pdf)." bytes.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.substr($exception->getMessage(), 0, 12000)."\n");
    exit(1);
}

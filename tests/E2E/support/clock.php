<?php

// Only the isolated runner loads this file; it never changes the OS or QA clock.
if (getenv('E2E_BULLET_FIXTURES') !== 'true') {
    return;
}
$root = dirname(__DIR__, 3);
$database = getenv('DB_DATABASE');
if (getenv('APP_ENV') !== 'e2e' || getenv('E2E_DATABASE') !== 'true'
    || ! ((getenv('DB_CONNECTION') === 'sqlite' && str_replace('\\', '/', $database) === str_replace('\\', '/', $root.'/storage/framework/testing/kronik-e2e.sqlite'))
        || (getenv('DB_CONNECTION') === 'mysql' && $database === 'kronik_e2e'))) {
    throw new RuntimeException('El reloj de pruebas requiere la base E2E aislada.');
}
require_once $root.'/vendor/autoload.php';
$value = trim(file_get_contents($root.'/storage/framework/testing/bullet-e2e-clock.txt'));
if (! preg_match('/^2027-\d{2}-\d{2}T12:00:00-06:00$/D', $value)) {
    throw new RuntimeException('Fecha del escenario Bullet E2E inválida.');
}
$clock = \Carbon\CarbonImmutable::parse($value);
\Carbon\CarbonImmutable::setTestNow($clock);
\Illuminate\Support\Carbon::setTestNow($clock);

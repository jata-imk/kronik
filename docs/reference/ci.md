# CI

## Decision

El proyecto debe tener CI real en GitHub Actions ademas de hooks locales.

## Diferencia con Githooks

`.githooks` corre en la maquina local antes de commit o push. CI corre en GitHub al hacer push o actualizar una PR, en un entorno limpio y reproducible.

## Workflow Actual

`.github/workflows/ci.yml` ejecuta:

- Checkout del repositorio.
- PHP 8.3 con extensiones requeridas.
- Node 22 con cache de npm.
- MariaDB 11 como base de datos de testing.
- `composer install`.
- `npm ci`.
- Verificacion de documentacion para cambios de codigo/config/base de datos/frontend/tests.
- `php artisan test`.
- `npm run build`.

## Politica

Una PR no debe considerarse lista si CI falla. Si un cambio de codigo no requiere documentacion, debe quedar justificado en la PR.

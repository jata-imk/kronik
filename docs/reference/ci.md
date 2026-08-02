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
- `npm run build`.
- `npm run test:unit` con Vitest, Vue Test Utils y jsdom.
- `php artisan test`.

El build frontend corre antes de los tests porque las pruebas HTTP que renderizan Inertia necesitan `public/build/manifest.json` en el runner limpio de GitHub Actions.

Vitest carga `vite.config.js` en modo `test`. En ese modo se omite `laravel-vite-plugin` para que las pruebas unitarias no intenten iniciar ni validar el servidor HMR dentro de CI.

## Politica

Una PR no debe considerarse lista si CI falla. Si un cambio de codigo no requiere documentacion, debe quedar justificado en la PR.

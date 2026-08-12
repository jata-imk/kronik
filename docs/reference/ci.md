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

Playwright no se ejecuta en GitHub Actions. Los recorridos E2E son una
verificacion local deliberada porque requieren un navegador completo y se
ejecutan bajo demanda en la computadora de desarrollo. CI conserva el build,
Vitest y Pest como verificaciones obligatorias de cada PR.

El build frontend corre antes de los tests porque las pruebas HTTP que renderizan Inertia necesitan `public/build/manifest.json` en el runner limpio de GitHub Actions.

Vitest carga `vite.config.js` en modo `test`. En ese modo se omite `laravel-vite-plugin` para que las pruebas unitarias no intenten iniciar ni validar el servidor HMR dentro de CI.

## Playwright local

Playwright es exclusivamente local: no debe ejecutarse en GitHub Actions ni en
la VPS de QA/produccion.

La primera vez se instala Chromium con:

```bash
npx playwright install chromium
```

Despues de construir el frontend, estan disponibles estos modos:

```bash
npm run build
npm run test:e2e
npm run test:e2e:headed
npm run test:e2e:ui
```

El lanzador prepara la base, inicia un servidor PHP temporal en
`http://127.0.0.1:8015`, ejecuta las pruebas en serie y detiene el servidor. Los
recursos externos de mapas, geocodificacion, fuentes y Twemoji se sustituyen por
respuestas deterministas; un fallo de esos proveedores no vuelve inestable la
suite ni oculta errores internos de consola.

### Proteccion de datos E2E

Por defecto solo puede recrearse el archivo
`storage/framework/testing/kronik-e2e.sqlite`. Para usar MySQL deben definirse
las variables `E2E_DB_*` y la base debe llamarse exactamente `kronik_e2e`. El
seeder exige simultaneamente `APP_ENV=e2e` y `E2E_DATABASE=true`; cualquier otra
combinacion se rechaza antes de borrar o sembrar datos.

Nunca se deben apuntar las variables E2E a la base de desarrollo, VPS o
produccion. `migrate:fresh` forma parte deliberada de este entorno descartable.

## Politica

Una PR no debe considerarse lista si CI falla. Si un cambio de codigo no requiere documentacion, debe quedar justificado en la PR.

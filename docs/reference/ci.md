# CI

## Recorridos compartidos E2E

El helper de consola responde localmente a la geolocalización IP con México;
los recorridos de captura no dependen de disponibilidad o tiempos de ese proveedor.

Los escenarios ejecutados juntos comparten los datos aislados del seeder. Un
recorrido que añade una política debe abrir explícitamente **Crear política** o
**Nueva versión de política** y comprobar el número resultante desde la versión
vigente, sin asumir que es la primera. Los filtros duplicados por la disposición
responsive se localizan por su rol y visibilidad.

Los recorridos largos de documentos e integrantes tienen un presupuesto total
de 180 segundos; la solicitud que captura, configura política y registra dos
revisiones tiene 240. No amplían los plazos de cada aserción ni omiten pasos.
Una espera sobre un control deshabilitado se corrige siguiendo el modo de la UI,
sin convertir consulta en edición dentro de la aplicación.

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
- Concurrencia de folios y auditoría con procesos independientes en MariaDB aislada.

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

Las expectativas esperan hasta 15 segundos por su condición observable; cada
recorrido dispone de 90 segundos salvo que declare otro límite. El recorrido dual
completo (PDF, firmas, desembolso, pagos, anticipos, liquidación y reversos) dispone
de 300 segundos; mantiene los mismos límites por condición. Estos límites
permiten completar redirecciones y recargas Inertia en desarrollo sin pausas
fijas. Una condición incorrecta sigue haciendo fallar la prueba. Revisar traza,
captura, vídeo y consola ante fallos.

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

## Concurrencia de folios

Después de Pest, `php tests/Concurrency/folios.php` ejecuta ocho procesos independientes sobre la MariaDB aislada `kronik_test`; exige `APP_ENV=testing`. Comprueba 40 creaciones paralelas y otras dos con barreras que fuerzan un cruce de bloqueos de actor y numeración, con 42 folios consecutivos únicos y reservas persistidas. Después comprueba una creación directa con fechas explícitas y el folio 43, fuera de una transacción de Pest. No utilizar la base de `.env` para esta prueba.

## Concurrencia de auditoría

`php tests/Concurrency/auditoria.php` exige también `APP_ENV=testing`, driver
MySQL y base `kronik_test`. Tres procesos leen los mismos candidatos antes de
competir por los bloqueos: deben producir exactamente dos activaciones y dos
retiros, sin eventos duplicados y con una sola versión final vigente. No consulta
proveedores ni usa datos de la aplicación. Ver el
[contrato de auditoría](auditoria-eventos.md) y la evidencia del commit en su
[Agent Note](../agent-notes/auditoria-actividad.md).

## Concurrencia financiera y E2E Bullet

Después de las verificaciones anteriores, CI ejecuta
`php tests/Concurrency/bullet.php`. Exige `APP_ENV=testing`, driver MySQL,
base `kronik_test` y servidor MariaDB; falla antes de escribir si no coinciden.
Los productos, solicitudes, firmas y desembolsos se preparan mediante los
servicios reales, con PDF sintético solo en este harness financiero.

Cada política prueba ocho competencias con procesos/conexiones independientes:
desembolsos con igual/distinta idempotencia, anticipo y reverso idempotentes,
anticipo frente a liquidación, referencia única entre créditos, pagos con previa
común y reverso frente a pago. El coordinador retiene las filas mientras ambos
trabajadores alcanzan su consulta bloqueante. Se comprueban estados, rollback,
calendarios originales y actividad sin duplicación ni datos reservados.
El desembolso se audita sobre la solicitud; pagos y reversos sobre el crédito.
El harness verifica el sujeto real de cada emisor y su contexto original.
El reloj transmitido conserva el instante y el nombre IANA de la zona de testNow:
convertirlo a la zona de la aplicación, o conservar solo el offset de enero,
alteraría casts de fechas históricas y sus huellas. La fecha de nacimiento de la
fixture cae en horario de verano histórico para comprobar también ese límite.

Playwright instala Chromium para la interfaz; PDF usa el Google Chrome del
runner, resuelto con `command -v google-chrome`, y conserva su sandbox. Antes de
los recorridos, `php tests/E2E/support/pdf.php` exige `APP_ENV=testing` y renderiza
contenido sintético sin acceder a datos de dominio; un fallo conserva la causa
operativa en CI. No se habilita `--no-sandbox` ni se cambia el renderer de QA.
Los binarios descargados pueden estar restringidos por AppArmor en Ubuntu; ver
la [referencia de Chromium](https://chromium.googlesource.com/chromium/src/+/main/docs/security/apparmor-userns-restrictions.md).
Con
`E2E_BULLET_FIXTURES=true`, el runner aislado ejecuta `bullet-ciclo.spec.js` y
`productos-bullet.spec.js`. El reloj está exclusivamente en los archivos de
pruebas, con fecha controlada por escenario y guardas de base/entorno; las
sesiones del escenario usan la tabla aislada con duración suficiente para sus
saltos de fecha. No se cambia el reloj de la aplicación desplegada ni se
desactiva CSRF. La regresión dual existente se ejecuta en otra preparación,
con su configuración habitual. CI conserva artefactos de navegador siete días.
El ciclo Bullet consume borradores y no tiene reintento automático: repetirlo
requiere preparar de nuevo su base aislada. Un fallo conserva su primer punto
de error, sin intentar editar una versión que el recorrido anterior ya activó.

Guion para despliegue y validación compartida: [Bullet QA](../how-to/qa-bullet.md).

Para E2E que generan PDF puede indicarse `DOCUMENTOS_CHROME_PATH` con la ruta
del Chrome instalado; preparación y servidor conservan ese valor. No se fija
una versión ni ruta del equipo de desarrollo en el repositorio.

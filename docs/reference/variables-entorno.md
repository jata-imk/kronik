# Variables de Entorno

## Aplicacion

- `APP_ENV`: use `production` en la VPS.
- `APP_KEY`: clave de cifrado. Se genera una sola vez, se respalda y no se
  comparte entre instancias.
- `APP_DEBUG`: debe ser `false` en un dominio público.
- `APP_URL`: URL HTTPS canónica.
- `APP_TIMEZONE`: zona de ejecución, normalmente `America/Mexico_City`.
- `APP_LOCALE` y `APP_FALLBACK_LOCALE`: idioma de la aplicación.

## Base de Datos

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Use un usuario exclusivo por instancia y no exponga MariaDB a Internet.

## Estado de la aplicación

- `SESSION_DRIVER`: `database` por defecto.
- `SESSION_SECURE_COOKIE`: `true` cuando HTTPS esté activo.
- `CACHE_STORE`: `database` por defecto.
- `QUEUE_CONNECTION`: `database` por defecto; requiere un worker permanente.
- `FILESYSTEM_DISK`: `local`; documentos KYC quedan en almacenamiento privado.

Las tablas para sesiones, cache, jobs y jobs fallidos están incluidas en las
migraciones.

## Documentos privados y generación PDF

- `DOCUMENTOS_DISK`: disco privado; normalmente `local`.
- `DOCUMENTOS_PDF_RENDERER`: use `browsershot`.
- `DOCUMENTOS_PDF_TIMEOUT`: límite por render, 55 segundos por defecto.
- `DOCUMENTOS_MAX_UPLOAD_KB`: máximo para PDF, JPG y PNG cargados.
- `DOCUMENTOS_NODE_BINARY`, `DOCUMENTOS_NPM_BINARY`,
  `DOCUMENTOS_NODE_MODULES_PATH` y `DOCUMENTOS_CHROME_PATH`: rutas opcionales
  cuando los binarios no están en el `PATH` del worker.

El usuario del worker debe poder leer `node_modules`, ejecutar Node y Chromium y
escribir en `storage/app/private`; no use `--no-sandbox` como configuración
normal de producción.

## Testing/CI

CI usa PHP 8.3 y MariaDB 11 con:

- `DB_CONNECTION=mysql`
- `DB_DATABASE=kronik_test`
- `DB_USERNAME=kronik`
- `DB_PASSWORD=secret`

`phpunit.xml` fuerza SQLite `:memory:` para ejecuciones locales de
`php artisan test`. GitHub Actions declara explícitamente MariaDB y reemplaza
esa configuración con las variables del workflow.

## Integraciones

Las credenciales de infraestructura y Círculo de Crédito viven en `.env`.
`empresa_configuraciones.integraciones` usa un cast cifrado y la interfaz nunca
devuelve una API key completa, pero `.env` sigue siendo la fuente requerida por
los servicios SIC actuales.

Variables actuales:

- `CIRCULO_CREDITO_HOST`.
- `CIRCULO_CREDITO_API_KEY`.
- `CIRCULO_CREDITO_SANDBOX`.

El geocodificador actual usa Nominatim y no consume una API key configurable.

## Correo y logs

- `MAIL_*`: configure SMTP antes de enviar correo real. `MAIL_MAILER=log` solo
  sirve para pruebas controladas.
- `LOG_CHANNEL`, `LOG_STACK` y `LOG_LEVEL`: en producción se recomienda log
  diario y evitar nivel `debug`.

Consulte [Desplegar Kronik en una VPS Debian 12](../how-to/desplegar-vps-nuevo.md)
para un ejemplo completo sin secretos reales.

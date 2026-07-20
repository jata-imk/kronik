# Variables de Entorno

## Aplicacion

- `APP_ENV`
- `APP_KEY`
- `APP_URL`

## Base de Datos

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## Testing/CI

CI usa MariaDB 11 con:

- `DB_CONNECTION=mysql`
- `DB_DATABASE=kronik_test`
- `DB_USERNAME=kronik`
- `DB_PASSWORD=secret`

## Integraciones

Las credenciales externas no deben guardarse en `empresa_configuraciones.integraciones`. Esa columna guarda referencias o banderas; los secretos viven en `.env`.

Variables previstas:

- `CDC_*` para Circulo de Credito.
- `GEOCODING_API_KEY` para proveedor de geocoding.

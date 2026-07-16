---
type: reference
area: configuration
status: active
---

# Variables de Entorno

`.env.example` debe contener nombres y valores seguros de ejemplo, nunca secretos reales.

## Aplicación

- `APP_NAME`
- `APP_ENV`
- `APP_KEY`
- `APP_DEBUG`
- `APP_TIMEZONE`
- `APP_URL`
- `APP_LOCALE`
- `APP_FALLBACK_LOCALE`
- `APP_FAKER_LOCALE`

En producción o migración de VPS, conserva el `APP_KEY` existente. Cambiarlo puede invalidar datos cifrados y sesiones.

## Base de Datos

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## Círculo de Crédito

- `CIRCULO_CREDITO_HOST`
- `CIRCULO_CREDITO_API_KEY`
- `CIRCULO_CREDITO_SANDBOX`

Estas variables son sensibles. Deben venir del entorno real o sandbox correspondiente.

## Frontend

- `VITE_APP_NAME`
- `JS_LOCAL_STORAGE_KEY`
- `VITE_JS_LOCAL_STORAGE_KEY`

`JS_LOCAL_STORAGE_KEY` se usa en la plantilla Blade para persistir preferencias de layout. La variante `VITE_` queda disponible para código compilado por Vite.

Relacionado:

- [Migrar VPS](../how-to/migrar-vps.md)
- [Comandos Artisan](comandos-artisan.md)
- [Glosario de negocio](glosario-negocio.md)

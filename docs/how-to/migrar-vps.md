# Migrar VPS

Esta guía cubre una actualización de código sobre una instalación existente.
Para crear el sitio, configurar CloudPanel/Plesk, PHP, Nginx, TLS, workers,
scheduler, respaldos y catálogos, consulte
[Desplegar Kronik en una VPS Debian 12](desplegar-vps-nuevo.md).

## Principio

En un VPS con datos reales no uses `migrate:fresh --seed`. Ese comando destruye tablas antes de reconstruirlas.

## Procedimiento Base

1. Crear backup de base de datos y archivos persistentes.
2. Activar mantenimiento cuando el cambio de esquema lo requiera:

```bash
php artisan down --retry=60
```

3. Actualizar código mediante `git pull --ff-only origin main`.
4. Instalar dependencias PHP y Node:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
```

5. Revisar `.env` contra [Variables de entorno](../reference/variables-entorno.md).
6. Ejecutar migraciones pendientes:

```bash
php artisan migrate --force
```

7. Optimizar cachés y reiniciar workers:

```bash
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

8. Sacar la aplicación de mantenimiento:

```bash
php artisan up
```

9. Validar `/up`, workers, scheduler, storage, logs y el flujo afectado.

Si un paso falla, no continúe a ciegas. Mantenga la aplicación en
mantenimiento, corrija o restaure el respaldo y la versión anterior. No ejecute
`migrate:rollback` sin revisar la migración.

## Desarrollo

`php artisan dev:reset-data` es solo para entornos descartables. No lo ejecutes en produccion.

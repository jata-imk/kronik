# Migrar VPS

## Principio

En un VPS con datos reales no uses `migrate:fresh --seed`. Ese comando destruye tablas antes de reconstruirlas.

## Procedimiento Base

1. Crear backup de base de datos y archivos persistentes.
2. Actualizar codigo en el servidor.
3. Instalar dependencias PHP y Node.
4. Revisar `.env` contra [Variables de entorno](../reference/variables-entorno.md).
5. Ejecutar migraciones pendientes:

```bash
php artisan migrate --force
```

6. Optimizar caches:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

7. Validar workers, scheduler, storage y logs.

## Desarrollo

`php artisan dev:reset-data` es solo para entornos descartables. No lo ejecutes en produccion.

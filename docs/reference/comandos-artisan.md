# Comandos Artisan

## Desarrollo

```bash
php artisan serve
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
php artisan dev:reset-data --dry-run
php artisan test
```

Para revisar o preparar una instalación exclusivamente QA sin recrear demos:

```bash
php artisan dev:reset-data --minimal --admin-email=administrador@ejemplo.com --dry-run
php artisan dev:reset-data --minimal --admin-email=administrador@ejemplo.com --confirm-qa
```

El borrado requiere `--confirm-qa` en todos los modos. Fuera de local/testing
requiere mantenimiento; con `APP_ENV=production` solo permite el modo mínimo.
Respaldo, detención de procesos, alcance y recuperación de archivos pendientes:
[Reiniciar datos de desarrollo o QA](../how-to/reset-datos-desarrollo.md).

`phpunit.xml` fuerza SQLite `:memory:` para la ejecución local. CI reemplaza
esa configuración con una base MariaDB aislada. Las pruebas no deben usar la
base configurada para la aplicación.

## Catalogos

```bash
php artisan db:seed --class=PaisesSeeder --force
php artisan sat-cfdi-v4:update
php artisan sepomex:update
php artisan db:seed --class=SystemSeeder --force
```

`sat-cfdi-v4:update` necesita `libreoffice --headless` si el archivo disponible
es XLS. Consulte [Restaurar catálogos](../how-to/restaurar-catalogos.md).

Solo en entornos que usan exclusivamente datos de prueba:

```bash
php artisan db:seed --class=DevelopmentSeeder --force
```

## Permisos

Para cargar el catálogo contextual después de un reinicio QA, sin activar sus
entradas en las páginas:

```bash
php artisan db:seed --class=MenubarItemsSeeder --force
```

Consulta [Cargar el catálogo del menú contextual](../how-to/configurar-menu-contextual.md).

```bash
php artisan permission:create-permission "read clientes" web --module-id=1
```

## Colas

```bash
php artisan queue:work
```

En desarrollo, `composer dev` ejecuta servidor Laravel, queue listener y Vite en paralelo.

En producción use `queue:work` bajo Supervisor u otro monitor y reinícielo
después de desplegar:

```bash
php artisan queue:restart
```

## Producción

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan down --retry=60
php artisan up
```

No use `migrate:fresh`, `dev:reset-data` o `DevelopmentSeeder` sobre una
instancia con datos reales.

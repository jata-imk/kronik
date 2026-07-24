# Comandos Artisan

## Desarrollo

```bash
php artisan serve
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
php artisan dev:reset-data
php artisan test
```

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

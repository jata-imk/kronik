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

`phpunit.xml` no fuerza SQLite actualmente; usa la conexion de base de datos resuelta por el entorno. Antes de correr pruebas, configura una base de testing o confirma que la conexion actual sea descartable.

## Catalogos

```bash
php artisan sat-cfdi-v4:update
php artisan sepomex:update
```

Estos comandos descargan y procesan catalogos externos. No forman parte del reset rapido de desarrollo.

## Permisos

```bash
php artisan permission:create-permission "read clientes" web --module-id=1
```

## Colas

```bash
php artisan queue:work
```

En desarrollo, `composer dev` ejecuta servidor Laravel, queue listener y Vite en paralelo.

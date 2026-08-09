# Seeders

## Estructura

- `CatalogSeeder`: catalogos base y pesados, incluyendo paises, SAT CFDI y SEPOMEX.
- `SystemSeeder`: datos de sistema, modulos, permisos, roles globales, menubar y SICs.
- `DevelopmentSeeder`: datos demo para desarrollo.
- `DatabaseSeeder`: ejecuta catalogos, sistema y desarrollo.

## Idempotencia

Los seeders de sistema y desarrollo deben poder re-ejecutarse sin duplicar registros. Para eso usan `firstOrCreate`, `updateOrCreate` o sincronizacion de permisos.

## Reset de Desarrollo

Para volver a datos de prueba sin borrar SAT/SEPOMEX:

```bash
php artisan dev:reset-data
```

Catalogos protegidos por el reset:

- `paises`
- `regimenes_fiscales`
- `divisiones_administrativas`
- `codigos_postales`

El comando toma conteos antes de truncar datos volatiles y falla si un catalogo pierde registros. Puede permitir aumentos idempotentes cuando el seeder completa un registro faltante.

## Reconstruccion de Catalogos

Ejecutar en orden:

```bash
php artisan db:seed --class=PaisesSeeder --force
php artisan sat-cfdi-v4:update
php artisan sepomex:update
```

`CatalogSeeder` agrupa esos pasos, pero para operaciones manuales se prefieren comandos separados porque permiten ver exactamente cual fuente fallo.

`DevelopmentSeeder` solo crea catalogos minimos de respaldo cuando falta Mexico, un regimen fisico/moral o cualquier codigo postal. No agrega divisiones demo cuando los catalogos completos ya existen.

La empresa y la sucursal demo usan la localidad canonica `Centro (Area 1)` del codigo postal `06000` y guardan sus IDs de SEPOMEX. Al reejecutar el seeder se corrigen registros demo antiguos que conservaban solamente el texto `Centro`.

Las pruebas locales usan SQLite `:memory:` mediante `phpunit.xml`. La migracion de direcciones conserva la columna de coordenadas y omite el indice espacial solo en SQLite; el modelo representa temporalmente el punto como texto WKT en ese motor. MySQL/MariaDB conservan `POINT`, `ST_GeomFromText` e indice espacial. El archivo `.env` nunca debe proporcionar la base de pruebas.

Datos demo actuales:

- `test@example.com` con password de factory `password`.
- `consulta.clientes@example.test` con password `password`: puede consultar clientes, expedientes y descargar archivos, pero no modificarlos.
- `editor.expedientes@example.test` con password `password`: puede consultar y modificar el expediente, sin permisos de alta o baja de clientes.
- `sin.acceso.clientes@example.test` con password `password`: permite comprobar la denegacion de acceso al modulo de clientes.
- Empresa demo singleton.
- Sucursal `MATRIZ`.
- Cliente persona fisica con score SIC exitoso.
- Cliente tipo moral con consulta SIC pendiente.
- Perfil economico KYC en ambos clientes demo.
- Checklist documental pendiente, referencia personal y ejemplo de aval/garantia.

Relacionado:

- [Reset de datos de desarrollo](../how-to/reset-datos-desarrollo.md)
- [Comandos Artisan](comandos-artisan.md)

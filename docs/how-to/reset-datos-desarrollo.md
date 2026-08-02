# Reset de Datos de Desarrollo

Usa este comando cuando quieras volver a datos demo sin borrar catalogos pesados SAT/SEPOMEX:

```bash
php artisan dev:reset-data
```

El comando comprueba los conteos de `paises`, `regimenes_fiscales`, `divisiones_administrativas` y `codigos_postales` antes y despues. Falla si alguno disminuye; los seeders pueden completar registros faltantes.

`dev:reset-data` no recrea un catalogo que ya estaba vacio. Para ese caso use [Restaurar catalogos](restaurar-catalogos.md).

Las pruebas automatizadas usan SQLite en memoria. La migracion de direcciones omite solamente el indice espacial en SQLite porque ese motor no lo soporta; MySQL y MariaDB siguen creandolo. Las pruebas nunca deben apuntar a la base configurada en `.env`.

El arranque inicial de `php artisan test` tampoco consulta permisos en la base de la aplicacion; PHPUnit registra su entorno aislado despues de ese arranque.

## Que conserva

- Paises.
- Regimenes fiscales SAT cuando ya existen.
- Codigos postales SEPOMEX cuando ya existen.
- Modulos, permisos, roles globales, menubar y SICs se re-siembran de forma idempotente.

## Que reinicia

- Usuarios y equipos demo.
- Clientes, datos fiscales, direcciones y consultas SIC demo.
- Configuracion de empresa y sucursales demo.
- Pivotes de roles/permisos por modelo y roles scoped por equipo.
- Tablas volatiles de sesiones, jobs y activity log si existen.

## Datos creados

- Usuario `test@example.com` con password de factory (`password`).
- Usuario `consulta.clientes@example.test` con password `password` y acceso de solo lectura a clientes.
- Usuario `editor.expedientes@example.test` con password `password` y acceso de lectura/edicion al expediente.
- Usuario `sin.acceso.clientes@example.test` con password `password` y sin acceso al modulo de clientes.
- Empresa demo en `empresa_configuraciones.singleton_key = default`.
- Sucursal demo `MATRIZ`.
- Dos clientes demo con datos fiscales, direccion y consultas SIC fake.

Para truncar sin sembrar de nuevo:

```bash
php artisan dev:reset-data --no-seed
```

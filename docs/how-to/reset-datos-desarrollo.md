# Reset de Datos de Desarrollo

Usa este comando cuando quieras volver a datos demo sin borrar catalogos pesados SAT/SEPOMEX:

```bash
php artisan dev:reset-data
```

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
- Empresa demo en `empresa_configuraciones.singleton_key = default`.
- Sucursal demo `MATRIZ`.
- Dos clientes demo con datos fiscales, direccion y consultas SIC fake.

Para truncar sin sembrar de nuevo:

```bash
php artisan dev:reset-data --no-seed
```

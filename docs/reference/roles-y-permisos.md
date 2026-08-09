# Roles y Permisos

## Capas

Kronik usa tres conceptos distintos:

- Jetstream teams: membresia y equipo actual para departamentos; la autorizacion funcional no usa sus roles genericos `admin` y `editor`.
- Spatie Permission: permisos de modulos, administracion, empresa, sucursales, clientes y roles operativos.
- Sucursal actual: contexto operativo de los datos, sin alterar los roles del equipo.

## Decision

Los equipos (`teams`) representan departamentos o grupos de trabajo, no la financiera. Por eso las acciones de Jetstream sobre equipos usan `TeamPolicy` (`ownsTeam`, `belongsToTeam`) y no dependen de que los seeders Spatie hayan corrido.

Spatie sigue siendo la fuente para permisos funcionales del sistema, como:

- `read configuracion-empresa`
- `update configuracion-empresa`
- `create sucursales`
- `read sucursales`
- `update sucursales`
- `delete sucursales`

El expediente KYC usa `ClientePolicy` y reutiliza `read clientes` para consulta/descarga y `update clientes` para cambios. No crea un segundo modulo de permisos para los recursos anidados.

`users.is_super_admin` concede acceso global mediante `Gate::before`. El rol historico llamado `Super Admin` se conserva solo para compatibilidad de migracion y no es la fuente de autorizacion. El permiso `transfer clientes` controla el cambio explicito de sucursal responsable.

Ver [Usuarios, equipos y sucursales](usuarios-equipos-sucursales.md).

## Pruebas

La suite de Jetstream debe permanecer verde porque CI ejecuta `php artisan test` completo.

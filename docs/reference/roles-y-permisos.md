# Roles y Permisos

## Capas

Kronik usa dos capas distintas:

- Jetstream teams: roles `admin` y `editor` para miembros dentro de equipos/departamentos.
- Spatie Permission: permisos de modulos, administracion, empresa, sucursales, clientes y roles operativos.

## Decision

Los equipos (`teams`) representan departamentos o grupos de trabajo, no la financiera. Por eso las acciones de Jetstream sobre equipos usan `TeamPolicy` (`ownsTeam`, `belongsToTeam`) y no dependen de que los seeders Spatie hayan corrido.

Spatie sigue siendo la fuente para permisos funcionales del sistema, como:

- `read configuracion-empresa`
- `update configuracion-empresa`
- `create sucursales`
- `read sucursales`
- `update sucursales`
- `delete sucursales`

## Pruebas

La suite de Jetstream debe permanecer verde porque CI ejecuta `php artisan test` completo.

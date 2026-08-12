# Usuarios, equipos y sucursales

## Modelo operativo

- Equipo actual: determina roles y permisos Spatie.
- Sucursal actual: determina dónde se crean y modifican operaciones.
- Sucursal principal: contexto inicial estable del usuario.
- Super Admin: acceso global mediante `users.is_super_admin`.

Cambiar de equipo no cambia la sucursal y cambiar de sucursal no cambia el equipo.

## Alta y estado de usuarios

Las cuentas se crean en **Administración > Usuarios**. Es obligatorio asignar al menos un equipo, sus roles, una sucursal principal y las sucursales permitidas. La cuenta queda `pending` y recibe un enlace de restablecimiento para definir su contraseña. Al completarlo pasa a `active` y verifica el correo.

Estados:

- `pending`: invitación pendiente; no puede iniciar sesión.
- `active`: acceso habilitado.
- `inactive`: acceso revocado sin eliminar historial.

No existen autorregistro, borrado de cuenta por el usuario ni invitaciones de miembros por el flujo genérico de Jetstream.

## Equipos

Los equipos se administran como departamentos institucionales. Sus roles se copian desde las plantillas globales al crear el equipo. Un equipo no puede desactivarse mientras algún usuario lo conserve como equipo actual.

## Sucursales y clientes

Un usuario normal solo puede activar una sucursal asignada y activa. Super Admin puede activar cualquier sucursal activa.

Cada cliente tiene una sucursal responsable:

- el listado abre en **Sucursal actual** y permite consultar **Todas**;
- `read clientes` permite consulta entre sucursales;
- `create clientes` crea en la sucursal actual;
- `update clientes` y `delete clientes` además exigen que la sucursal actual sea la responsable;
- `transfer clientes` traslada explícitamente el cliente entre sucursales asignadas;
- Super Admin omite las restricciones de membresía y permisos mediante el bypass global.

Una sucursal no puede desactivarse mientras tenga usuarios activos o clientes. Primero deben reasignarse.

## Verificación manual

1. Iniciar sesión como Super Admin y crear una sucursal, un equipo y un usuario pendiente.
2. Confirmar que el correo de invitación permite establecer contraseña y activa la cuenta.
3. Entrar como usuario operativo, alternar entre sus sucursales y comprobar que una no asignada devuelve 403.
4. Crear un cliente y confirmar que queda en la sucursal actual.
5. Consultar clientes de todas las sucursales; comprobar que un cliente remoto se puede leer pero no editar.
6. Trasladar un cliente y confirmar que cambia la sucursal responsable y se registra actividad.
7. Intentar desactivar una sucursal o equipo con dependencias y confirmar el mensaje de reasignación.

## Operación de migración

Antes de ejecutar la migración en un entorno con datos debe existir una sucursal activa con clave `MATRIZ`. La migración asigna allí usuarios, clientes y actividad histórica sin sucursal. No ejecutar seeders para preparar producción: crear o verificar `MATRIZ` de forma controlada y respaldar la base primero.

Relacionado: [ADR 0005](../explanation/adr-0005-contextos-equipo-sucursal.md).

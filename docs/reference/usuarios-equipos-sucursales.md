# Usuarios, equipos y sucursales

## Modelo operativo

- Equipo actual: determina roles y permisos Spatie.
- Sucursal actual: determina dónde se crean y modifican operaciones.
- Sucursal principal: contexto inicial estable del usuario.
- Super Admin: acceso global mediante `users.is_super_admin`.

Cambiar de equipo no cambia la sucursal y cambiar de sucursal no cambia el equipo.

## Alta y estado de usuarios

Las invitaciones administrativas tienen vigencia propia de 72 horas configurables.
El listado muestra envío y vencimiento real; reenviar antes de 60 segundos pide
esperar un minuto. Reenviar invalida el enlace anterior. Aceptar activa una cuenta
pendiente y verifica su correo, una sola vez. Recuperación conserva 60 minutos.
Véase [ADR 0026](../explanation/adr-0026-invitaciones-vigencia-propia.md).

Las cuentas se crean en **Administración > Usuarios**. Es obligatorio asignar al menos un equipo, sus roles, una sucursal principal y las sucursales permitidas. La cuenta queda `pending` y recibe un enlace de restablecimiento para definir su contraseña. Al completarlo pasa a `active` y verifica el correo.

Estados:

- `pending`: invitación pendiente; no puede iniciar sesión.
- `active`: acceso habilitado.
- `inactive`: acceso revocado sin eliminar historial.

No existen autorregistro, borrado de cuenta por el usuario ni invitaciones de miembros por el flujo genérico de Jetstream.

## Equipos

Desde el equipo se abre el mismo formulario de Usuarios para invitar o editar,
sin navegar al listado global. Editar conserva equipos, roles y sucursales ajenos
al equipo visible, incluidas asignaciones históricas inactivas. Estas no pueden
asignarse a usuarios nuevos ni seleccionarse como equipo actual/sucursal principal.

**Reenviar invitación** requiere `create users`; **Editar/Reactivar cuenta**,
`update users`; **Desactivar cuenta**, `delete users`. Son acciones centrales y
no cambian sus permisos por ejecutarse desde un equipo. Desactivar afecta todos
los equipos y conserva historial; su confirmación explica ese alcance.

**Quitar del equipo** requiere `remove members teams` en el seleccionado y solo
retira su membresía, roles y permisos directos. No aplica al responsable; el
equipo actual o único activo exige reasignación previa. No se ofrece quitarse
a uno mismo desde este panel. Véase [ADR 0028](../explanation/adr-0028-acciones-cuenta-membresia.md).

Las acciones de cuenta, invitación y membresía emiten auditoría con actor,
identificadores, resultado y contexto original; la contraseña nunca se registra.
Desactivar desde el formulario también revoca sesiones y tokens de acceso.
Reenviar se audita como aceptado por el transporte, no como correo entregado.
Consultar la [matriz de eventos](auditoria-eventos.md).

La configuración distingue equipo actual de seleccionado. Desde ella se consultan
integrantes, invitaciones pendientes y resumen de roles con al menos una persona
activa del equipo, incluido el responsable cuando tiene asignación explícita.
Los conteos excluyen pendientes e inactivos. Las tarjetas cuadradas se adaptan
al ancho disponible; la primera abre la configuración completa y los roles no
visibles en el resumen. El catálogo para invitar conserva todos los roles.
Las acciones de integrantes se presentan en una fila horizontal. La actividad
se consulta mediante el botón a Bitácora, sin cargar un listado en Equipo.
Los enlaces a Roles y Bitácora conservan la selección sin cambiar
el equipo actual. Sucursales y configuración de empresa mantienen sus ámbitos.

El responsable es informativo: sus facultades dependen de roles Spatie, sin
privilegio por ser owner. Transferir requiere `update teams`, confirmación y otro
integrante activo; conserva membresías, roles y contextos, y registra la operación.
Desactivar/reactivar está disponible en la configuración con los mismos permisos
y restricciones del listado. `activo=false` tampoco permite desactivar equipos
que aún tengan usuarios actuales. Véase [ADR 0027](../explanation/adr-0027-equipo-seleccionado-responsable.md).

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

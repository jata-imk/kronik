# Roles y Permisos

## Capas

Kronik usa tres conceptos distintos:

- Jetstream teams: membresia y equipo actual para departamentos; la autorizacion funcional no usa sus roles genericos `admin` y `editor`.
- Spatie Permission: permisos de modulos, administracion, empresa, sucursales, clientes y roles operativos.
- Sucursal actual: contexto operativo de los datos, sin alterar los roles del equipo.

## Decision

Los equipos (`teams`) representan departamentos o grupos de trabajo, no la
financiera. `TeamPolicy` comprueba pertenencia y permisos Spatie del equipo
afectado; ser responsable no concede privilegios. Super Admin conserva su acceso
global explícito. Véase [ADR 0027](../explanation/adr-0027-equipo-seleccionado-responsable.md).

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

## Administración de roles

Roles acepta `?team=ID` para administrar el seleccionado; sin ese parámetro usa
el actual. La selección no cambia el equipo ni sucursal actuales. Consulta,
alta, edición y eliminación autorizan los permisos del seleccionado, y las
capacidades de navegación permanecen en el contexto actual.

Las escrituras de Roles se restringen al equipo seleccionado autorizado. Enviar
`team_id` en el cuerpo no cambia ese destino. Actualizar o eliminar un rol de
otro equipo o una plantilla global devuelve 404, incluso para Super Admin.
Las plantillas continúan administradas mediante seeders.

El nombre es único por equipo y guard; puede repetirse en otros equipos.
Enviar una lista de permisos vacía los retira todos. Eliminar un rol requiere
retirar previamente sus permisos y asignaciones a usuarios.

Módulos y permisos presentan `etiqueta` y `descripcion` en español, con ayuda
contextual y sin cambiar `name`, las claves Spatie ni `auth.permissions`.
La migración aditiva completa los registros existentes; los modelos utilizan
el catálogo compartido para completar nuevas altas y seeders sin sobrescribir
textos personalizados. Los permisos desconocidos muestran «Permiso personalizado».

La matriz es de solo lectura sin `update roles`; crear y eliminar requieren
sus permisos respectivos. Véase [ADR 0025](../explanation/adr-0025-roles-aislamiento-presentacion.md).

## Pruebas

La suite de Jetstream debe permanecer verde porque CI ejecuta `php artisan test` completo.

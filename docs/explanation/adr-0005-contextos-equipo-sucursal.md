# ADR 0005: Contextos de equipo y sucursal

## Estado

Aceptado el 2026-08-08.

## Contexto

Kronik ya utilizaba equipos de Jetstream como contexto de roles Spatie y tenía un catálogo de sucursales sin relación con usuarios ni clientes. Equiparar ambos conceptos habría mezclado permisos organizativos con responsabilidad operativa y habría obligado a duplicar equipos por oficina.

El ERP necesita saber con qué permisos trabaja una persona y, por separado, qué oficina es responsable de una operación. También necesita conservar acceso administrativo global sin crear asignaciones artificiales en cada equipo y sucursal.

## Decisión

- Un equipo representa un departamento o contexto de permisos. Los roles Spatie continúan asignándose por equipo.
- Una sucursal representa una unidad operativa. Un usuario puede pertenecer a varias sucursales y conserva una sucursal principal y una sucursal actual.
- `is_super_admin` es una capacidad global explícita y protegida; no depende de un rol Spatie repetido por equipo.
- Cada cliente pertenece a una sucursal responsable. La consulta de clientes es global para quien tenga `read clientes`; crear y modificar usa la sucursal actual y responsable.
- El traslado de clientes es una acción explícita mediante `transfer clientes`, restringida a sucursales asignadas salvo para Super Admin.
- Usuarios se invitan desde la administración y pasan de `pending` a `active` al establecer su contraseña. No hay autorregistro, borrado de cuenta ni invitaciones paralelas de Jetstream.
- Usuarios, equipos y sucursales se desactivan conservando historial. Un equipo con usuarios que aún lo tienen como actual y una sucursal con usuarios activos o clientes no pueden desactivarse.
- Los datos existentes se asignan a la sucursal activa `MATRIZ` durante la migración. La migración falla de forma explícita si hay usuarios o clientes y no existe esa sucursal.

## Consecuencias

- Cambiar de equipo cambia permisos; cambiar de sucursal cambia el contexto operativo. Ninguna acción cambia automáticamente el otro contexto.
- Las policies deben autorizar cada escritura sobre entidades con sucursal; ocultar botones en Vue no sustituye esa validación.
- El registro de actividad conserva `team_id` y `sucursal_id`, por lo que Backlog 02.5 podrá definir su matriz de consulta sin inferir contexto desde el estado actual del usuario.
- Originación debe copiar o relacionar explícitamente la sucursal responsable de cada solicitud. Créditos, pagos y cobranza deberán definir si heredan esa sucursal de manera inmutable o si admiten transferencias con historial.
- No se decide todavía la asignación de ejecutivos, cajas, rutas de cobranza, documentos por sucursal ni reglas de folios para entidades futuras.

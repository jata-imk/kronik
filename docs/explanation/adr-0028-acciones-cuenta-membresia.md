# ADR 0028: acciones de cuenta y membresía

## Estado

Aceptado el 2026-10-25. [Tarea](https://app.notion.com/p/3fe61db7db7f81608ffac3898d5fbf9a).

## Decisión

Usuarios y Equipo comparten formulario, acciones y preparación de datos.
Las relaciones, asignaciones de roles y vigencias de invitación se cargan por
lote para evitar consultas adicionales por cada integrante de la pantalla.
Las escrituras de cuenta permanecen en `UserController` y `UserManagementService`,
con los permisos centrales existentes: crear/reenviar, actualizar/reactivar y
desactivar requieren `create users`, `update users` y `delete users` respectivamente.
La pantalla desde la que se abre el modal no cambia esas autorizaciones.

Editar o reactivar transmite todas las asignaciones del usuario. Se conservan
las membresías históricas de equipos y sucursales inactivos ya asignados; no
pueden añadirse asignaciones inactivas nuevas. Equipo actual y sucursal principal
deben ser activos. Las opciones inactivas no asignadas se deshabilitan en el
formulario y las asignadas se identifican como históricas.

Quitar del equipo es independiente de desactivar la cuenta global. Usa la
autorización del equipo seleccionado, comprueba pertenencia y protege al
responsable. Bloquea retirar el equipo actual o el único activo: primero debe
reasignarse el usuario. En una transacción elimina la membresía y las asignaciones
Spatie de ese equipo, conservando las demás y el estado de la cuenta.
La salida propia por el endpoint Jetstream conserva su contrato; no se ofrece
en estas acciones para evitar abandonar la pantalla cuyo acceso se retiraría.

## Consecuencias

Actualización aceptada el 26/10/2026: estas acciones mantienen su auditoría en la
misma entrega, conforme a la regla nueva de AGENTS.md. El servicio de cuentas
registra creación pendiente, cambios reales de campos/estado y asignaciones.
Las rutas alternativas de miembros Jetstream registran altas, bajas y cambio de
su rol genérico; este último no se presenta como un cambio de permisos Spatie.
El transporte de invitaciones registra aceptación, reenvío, fallo y throttle;
aceptación por transporte no prueba entrega al destinatario. Activación inicial,
recuperación y cambio autenticado de contraseña tienen eventos distintos.

Se capturan equipo y sucursal antes de modificar preferencias; una sucursal
desconocida permanece nula. Los eventos de membresía usan el equipo afectado y
la sucursal original del actor. Los de cuenta usan el contexto original del
administrador; los de contraseña, el de la cuenta que prueba su identidad.
Los emisores son síncronos: no hay cola ni contexto reconstruido al ejecutarla.
Se omiten eventos en reintentos sin cambios, y las escrituras transaccionales
del dominio y sus eventos se revierten juntas. Nunca se copian valores de
identidad, contraseñas, hashes, tokens, URLs ni errores sensibles del transporte.
La [matriz canónica](../reference/auditoria-eventos.md) distingue esta cobertura
de la reconciliación general todavía pendiente del backlog de auditoría.

Invitar, editar, reenviar y gestionar estado se ejecutan desde el equipo y vuelven
a él. Desactivar confirma expresamente que revoca acceso a todos los equipos.
Los botones tienen nombre accesible. No se crea otra validación ni otro flujo de
invitaciones. No requiere migración ni permisos nuevos.

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

Invitar, editar, reenviar y gestionar estado se ejecutan desde el equipo y vuelven
a él. Desactivar confirma expresamente que revoca acceso a todos los equipos.
Los botones tienen nombre accesible. No se crea otra validación ni otro flujo de
invitaciones. No requiere migración ni permisos nuevos.

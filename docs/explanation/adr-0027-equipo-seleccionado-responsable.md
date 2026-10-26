# ADR 0027: equipo seleccionado y responsable informativo

## Estado

Aceptado el 2026-10-25. [Tarea](https://app.notion.com/p/3fe61db7db7f811cb466cc7258dd2246).
Complementa ADR 0005, 0025 y 0026.

## Decisión

Roles acepta `?team=ID`, con el actual como predeterminado. La selección se
resuelve y autoriza antes de comprobar los permisos de la operación. Exige
pertenencia al equipo salvo Super Admin. El cuerpo `team_id` continúa ignorado;
el rol destino debe pertenecer al equipo resuelto.

`TeamContext` evalúa permisos con el identificador Spatie del equipo seleccionado,
restaurando contexto y relaciones cargadas mediante `finally`. No cambia
`current_team_id` ni la sucursal. Las capacidades del equipo se entregan aparte
de `auth.permissions`, que conserva el contexto de navegación actual.

El responsable (`teams.user_id`) identifica a la persona de contacto. Ser owner
ya no evita permisos Spatie; Super Admin conserva su capacidad global explícita.
Transferir requiere `update teams`, confirmación e integrante activo. La
transacción bloquea equipo y destino, conserva al anterior como miembro, retira
la membresía duplicada del nuevo owner y conserva roles y contextos actuales.
La bitácora registra actor, equipo afectado y cambio de responsable.

La configuración separa integrantes e invitaciones pendientes. Incluye resumen
de roles y actividad reciente según permisos. La bitácora acepta `team_id`,
autoriza ese equipo y mantiene el filtro al paginar o exportar. No se amplía la
matriz de auditoría diferida.

Las rutas de actualización y desactivación comparten la regla de equipo sin
usuarios actuales; no es posible eludirla enviando `activo=false` al actualizar.
Los permisos se evalúan para el equipo afectado, también desde la administración.

## Consecuencias

No hay migración ni concesión automática de privilegios a antiguos responsables.
Antes del despliegue deben existir Super Admin o permisos explícitos para las
personas que administran los equipos. Un usuario que solo crea equipos necesitará
asignaciones explícitas para administrarlos; la creación no concede privilegios.
Los endpoints genéricos Jetstream conservan su contrato y ahora requieren los
permisos del equipo para las operaciones de administración.

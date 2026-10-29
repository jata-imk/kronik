# ADR 0031: Consulta de actividad por equipo y sucursal

## Estado

Aceptado el 2026-10-28. Alcance confirmado por el usuario al revisar el ajuste de Equipo.

## Contexto

La bitácora aceptaba `team_id` sin ofrecer selector. Los equipos representan
departamentos y las sucursales el contexto operativo. Restringir una consulta
de auditoría a las sucursales asignadas al lector cambiaría el alcance existente.

## Decisión

Un usuario normal necesita pertenencia y `read activity-log` en el equipo
consultado. Ese permiso permite consultar todas las sucursales con actividad
del equipo, estén o no asignadas al lector. Super Admin global puede consultar
todos los equipos, incluidos registros históricos sin equipo.

Equipo y Sucursal son filtros de consulta, no cambios de preferencias. Sin
selección explícita, el usuario normal consulta su equipo actual autorizado;
Super Admin consulta todos. Un contexto no autorizado devuelve 403. Una sucursal
sin registros dentro del equipo devuelve un resultado vacío.

La misma consulta autorizada alimenta listado, opciones dependientes y CSV.
Los equipos disponibles requieren permiso en su propio contexto. Las sucursales
proceden del historial del equipo e incluyen inactivas; no son solo las actuales
del lector. Cambiar equipo limpia sucursal y opciones dependientes. El equipo
seleccionado se conserva al limpiar, paginar y exportar.

## Consecuencias

No hay permisos ni migraciones nuevos. No se amplían permisos por ser responsable
del equipo. El filtro no constituye una restricción adicional por asignación
de sucursal. La política de registrar accesos/exportaciones como nuevos eventos
sigue pendiente del backlog de auditoría, sin crear registros recursivos al filtrar.

Referencias: [matriz y contrato de consulta](../reference/auditoria-eventos.md),
[usuarios y equipos](../reference/usuarios-equipos-sucursales.md) y
[ADR 0027](adr-0027-equipo-seleccionado-responsable.md).

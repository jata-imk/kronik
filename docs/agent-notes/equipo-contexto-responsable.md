# Equipo - Contexto seleccionado y responsable

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f811cb466cc7258dd2246
- Rama: `feat/equipo-contexto-responsable`
- PR: https://github.com/jata-imk/kronik/pull/49.
- ADR: [0027](../explanation/adr-0027-equipo-seleccionado-responsable.md).

## Objetivo

Administrar el equipo seleccionado sin alterar el actual y formalizar al responsable informativo.

## Estado actual

- Estado: integrado en `main` mediante `91b5096` (PR #49), con CI aprobado; sin despliegue acreditado.
- Última actualización: 2026-10-26.
- Último punto estable: suite completa, E2E y revisión medium completados.

## Decisiones pendientes

- Ninguna; responsable informativo e invitaciones separadas. Acciones locales de usuarios integradas en PR #50.

## Evidencia

- Pruebas: suite backend completa 457 aprobadas y 1 omitida; regresión final de contexto/validación/invitación 10 aprobadas / 87 aserciones; 4 Vue; build y Pint aprobados. E2E: 4 administración, 1 Roles y 1 configuración aprobados.
- Revisión medium: corregido fixture de invitación para otorgar permiso explícito; segunda pasada sin hallazgos. Captura y traza del primer E2E permitieron corregir el nombre accesible del selector de responsable. La captura móvil desactiva transiciones para reflejar el layout final.
- CI inicial: detectó la omisión de la ruta de transferencia en el commit; se incluye en el commit correctivo de la misma PR. El código de la ruta ya formaba parte de las pruebas y review locales.
- Capturas: [escritorio](../assets/equipo-seleccionado-desktop.png) y [móvil](../assets/equipo-seleccionado-mobile.png).
- Migraciones o seeders: sin migración; fixture E2E activo para transferir responsable.
- Review medium acumulado de PR #46–#50 sobre `a6057a0..6c30e3c`: sin hallazgos pendientes; 43 pruebas backend focalizadas y 323 aserciones aprobadas. Nueve E2E de la iniciativa aprobados antes del último merge.
- QA posterior del usuario: no se ha reportado para esta entrega.

## Siguiente paso

Despliegue y QA funcional cuando se programe; acciones de integrantes también integradas mediante PR #50.

## Cierre

- Commit o merge: `91b5096`, PR #49.
- Pendientes diferidos: despliegue separado.

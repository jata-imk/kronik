# Equipo - Contexto seleccionado y responsable

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f811cb466cc7258dd2246
- Rama: `feat/equipo-contexto-responsable`
- PR: enlace y merge finales en Notion.
- ADR: [0027](../explanation/adr-0027-equipo-seleccionado-responsable.md).

## Objetivo

Administrar el equipo seleccionado sin alterar el actual y formalizar al responsable informativo.

## Estado actual

- Estado: entregado; integración y CI final verificables en la PR y Notion.
- Última actualización: 2026-10-25.
- Último punto estable: suite completa, E2E y revisión medium completados.

## Decisiones pendientes

- Ninguna; responsable informativo e invitaciones separadas. Acciones locales de usuarios en la siguiente tarea.

## Evidencia

- Pruebas: suite backend completa 457 aprobadas y 1 omitida; regresión final de contexto/validación/invitación 10 aprobadas / 87 aserciones; 4 Vue; build y Pint aprobados. E2E: 4 administración, 1 Roles y 1 configuración aprobados.
- Revisión medium: corregido fixture de invitación para otorgar permiso explícito; segunda pasada sin hallazgos. Captura y traza del primer E2E permitieron corregir el nombre accesible del selector de responsable. La captura móvil desactiva transiciones para reflejar el layout final.
- CI inicial: detectó la omisión de la ruta de transferencia en el commit; se incluye en el commit correctivo de la misma PR. El código de la ruta ya formaba parte de las pruebas y review locales.
- Capturas: [escritorio](../assets/equipo-seleccionado-desktop.png) y [móvil](../assets/equipo-seleccionado-mobile.png).
- Migraciones o seeders: sin migración; fixture E2E activo para transferir responsable.

## Siguiente paso

Consultar CI y merge en Notion; continuar con acciones locales de integrantes tras integrar.

## Cierre

- Commit o merge: consultar PR y Notion.
- Pendientes diferidos: despliegue separado; acciones locales de integrantes en el siguiente PR.

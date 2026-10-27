# Equipo - Acciones de integrantes

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81608ffac3898d5fbf9a
- Rama: `feat/equipo-acciones-integrantes`
- PR: enlace y merge finales en Notion.
- ADR: [0028](../explanation/adr-0028-acciones-cuenta-membresia.md).

## Objetivo

Gestionar usuarios desde el equipo con formulario y acciones compartidos.

## Estado actual

- Estado: en progreso.
- Última actualización: 2026-10-25.
- Último punto estable: 25 pruebas backend / 195 aserciones y 6 Vue aprobadas; build aprobado.

## Decisiones pendientes

- Ninguna; quitar membresía y desactivar cuenta tienen alcances distintos.

## Evidencia

- Pruebas: conservación de asignaciones, incluidas inactivas, y limpieza aislada de membresía/permisos; restricciones de responsable y contexto actual/único.
- Revisión: suite completa, E2E y medium pendientes.
- Migraciones o seeders: sin migración; fixtures E2E separados para transferencia, reenvío y acciones.

## Siguiente paso

Completar pruebas, revisión medium y CI; integrar y hacer review final acumulado de los cinco PR.

## Cierre

- Commit o merge: consultar PR y Notion.
- Pendientes diferidos: despliegue independiente.

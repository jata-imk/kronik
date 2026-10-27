# Equipo - Acciones de integrantes

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81608ffac3898d5fbf9a
- Rama: `feat/equipo-acciones-integrantes`
- PR: enlace y merge finales en Notion.
- ADR: [0028](../explanation/adr-0028-acciones-cuenta-membresia.md).

## Objetivo

Gestionar usuarios desde el equipo con formulario y acciones compartidos.

## Estado actual

- Estado: implementado y validado localmente; pendiente de CI e integración.
- Última actualización: 2026-10-26.
- Integración y despliegue: sin PR todavía; base actualizada a `main` con PR #41 (`86a683a`). Despliegue pendiente.
- Último punto estable: 471 pruebas backend aprobadas, una omitida (40 808 aserciones); 99 Vue y nueve E2E aprobados; build aprobado.

## Decisiones pendientes

- Ninguna; quitar membresía y desactivar cuenta tienen alcances distintos.

## Evidencia

- Pruebas: conservación de asignaciones, incluidas inactivas, y limpieza aislada de membresía/permisos; restricciones de responsable y contexto actual/único.
- Revisión medium final contra `main`: sin hallazgos pendientes. Se corrigieron consultas por integrante mediante carga por lote y el registro de cambios exclusivos de sucursales secundarias, con regresiones de crecimiento y reintentos.
- Validación final: `php artisan test --compact` (471 aprobadas, una omitida); `npm run test:unit` (99); `npm run build`; Pint; nueve Playwright de administración, roles, invitaciones, solicitudes, contexto y acciones de equipo. E2E en SQLite aislado y correo array, sin servicios externos.
- Documentación: 140 Markdown y 73 documentos Diátaxis sin enlaces locales rotos ni faltantes en índices de sección y temas; `git diff --check` aprobado.
- Auditoría nueva: regla de `AGENTS.md` actualizada el 26/10/2026; cuentas, invitaciones, contraseñas y membresías incluidas en esta misma entrega, con matriz y regresiones. El backlog general de auditoría sigue pendiente.
- Confirmación de QA/usuario: no se ha reportado despliegue o QA de estas acciones.
- Migraciones o seeders: sin migración; fixtures E2E separados para transferencia, reenvío y acciones.

## Siguiente paso

Completar pruebas, revisión medium y CI; integrar y hacer review final acumulado de los cinco PR.

## Cierre

- Commit o merge: consultar PR y Notion.
- Pendientes diferidos: despliegue independiente.

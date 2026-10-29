# Equipo - Acciones de integrantes

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81608ffac3898d5fbf9a
- Rama: `feat/equipo-acciones-integrantes`
- PR: https://github.com/jata-imk/kronik/pull/50.
- ADR: [0028](../explanation/adr-0028-acciones-cuenta-membresia.md).

## Objetivo

Gestionar usuarios desde el equipo con formulario y acciones compartidos.

## Estado actual

- Estado: integrado en `main` mediante `6c30e3c` (PR #50), con CI aprobado.
- Última actualización: 2026-10-28.
- Integración y despliegue: PR #50 y PR #41 (`86a683a`) integradas. Despliegue normal en VPS y QA general satisfactorio reportados por el usuario para el lote de siete entregas.
- Último punto estable: 471 pruebas backend aprobadas, una omitida (40 808 aserciones); 99 Vue y nueve E2E aprobados; build aprobado.

## Decisiones pendientes

- Ninguna; quitar membresía y desactivar cuenta tienen alcances distintos.

## Evidencia

- Pruebas: conservación de asignaciones, incluidas inactivas, y limpieza aislada de membresía/permisos; restricciones de responsable y contexto actual/único.
- Revisión medium final contra `main`: sin hallazgos pendientes. Se corrigieron consultas por integrante mediante carga por lote y el registro de cambios exclusivos de sucursales secundarias, con regresiones de crecimiento y reintentos.
- Validación final: `php artisan test --compact` (471 aprobadas, una omitida); `npm run test:unit` (99); `npm run build`; Pint; nueve Playwright de administración, roles, invitaciones, solicitudes, contexto y acciones de equipo. E2E en SQLite aislado y correo array, sin servicios externos.
- Documentación: 140 Markdown y 73 documentos Diátaxis sin enlaces locales rotos ni faltantes en índices de sección y temas; `git diff --check` aprobado.
- Review medium acumulado de PR #46–#50 sobre `a6057a0..6c30e3c`: sin hallazgos pendientes; 43 pruebas backend focalizadas y 323 aserciones aprobadas. El CI de `main` también aprobó después del merge.
- Auditoría nueva: regla de `AGENTS.md` actualizada el 26/10/2026; cuentas, invitaciones, contraseñas y membresías incluidas en esta misma entrega, con matriz y regresiones. El backlog general de auditoría sigue pendiente.
- Confirmación de QA/usuario: confirmación general del 2026-10-28 («ya comprobé todo y quedó re bien»); su consulta posterior incluye esta entrega en el lote de siete. Sin SHA desplegado ni versiones informados; no se atribuyen resultados individuales no descritos.
- Migraciones o seeders: sin migración; fixtures E2E separados para transferencia, reenvío y acciones.

## Siguiente paso

Sin pendientes de esta entrega. Notion conserva estado Done y cierre de QA. Mantener separados el backlog general de auditoría del 31/10/2026 y el despliegue/QA de la mejora posterior de Equipo/bitácora (PR #55).

## Cierre

- Commit o merge: `6c30e3c`, PR #50.
- Pendientes diferidos: ninguno dentro de esta entrega; auditoría general y mejora posterior de UI se siguen en sus propias tareas/notas.

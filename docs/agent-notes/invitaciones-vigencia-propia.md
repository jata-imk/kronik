# Invitaciones - Vigencia propia

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81969ff5d3f224b3b2c5
- Rama: `fix/invitaciones-vigencia-propia`
- PR: https://github.com/jata-imk/kronik/pull/48.
- ADR: [0026](../explanation/adr-0026-invitaciones-vigencia-propia.md).

## Objetivo

Separar emisión y aceptación de invitaciones de la recuperación corta.

## Estado actual

- Estado: integrado en `main` mediante `554d690` (PR #48), con CI aprobado; despliegue normal en VPS y QA general satisfactorio reportados por el usuario.
- Última actualización: 2026-10-28.
- Último punto estable: implementación, validaciones locales y review medium completados.

## Decisiones pendientes

- Ninguna; 72 horas configurables y recuperación de 60 minutos.

## Evidencia

- Pruebas: 67 backend, 511 aserciones (incluye 34 del reinicio QA); 2 Vue; build y Pint aprobados; E2E Chromium aprobado: vigencia, activación, rechazo del uso repetido e inicio de sesión.
- Revisión medium: registrados y corregidos clasificación de la tabla en el reinicio y preferencia de un enlace corto vigente frente a una invitación vencida; segunda pasada sin hallazgos.
- Captura: [activación móvil](../assets/invitacion-vigencia-mobile.png).
- Migraciones: tabla propia de tokens; requiere migrar antes de enviar invitaciones.
- Configuración: `INVITATION_EXPIRE_MINUTES=4320`; sin credenciales nuevas.
- Review medium acumulado de PR #46–#50 sobre `a6057a0..6c30e3c`: sin hallazgos pendientes; 43 pruebas backend focalizadas y 323 aserciones aprobadas. Nueve E2E de la iniciativa aprobados antes del último merge.
- QA posterior del usuario: confirmación general del 2026-10-28 («ya comprobé todo y quedó re bien»); su consulta posterior incluye esta entrega en el lote de siete. Sin SHA desplegado ni versiones informados; no se atribuyen resultados individuales o entrega efectiva de correo no descritos.

## Siguiente paso

Sin pendientes de esta entrega. Notion conserva estado Done y cierre de QA; la mejora posterior de Equipo/bitácora (PR #55) se sigue por separado.

## Cierre

- Commit o merge: `554d690`, PR #48.
- Pendientes diferidos: ninguno dentro de esta entrega.

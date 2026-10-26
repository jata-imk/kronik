# Invitaciones - Vigencia propia

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81969ff5d3f224b3b2c5
- Rama: `fix/invitaciones-vigencia-propia`
- PR: enlace y merge finales en Notion.
- ADR: [0026](../explanation/adr-0026-invitaciones-vigencia-propia.md).

## Objetivo

Separar emisión y aceptación de invitaciones de la recuperación corta.

## Estado actual

- Estado: entregado; integración y CI final verificables en la PR y Notion.
- Última actualización: 2026-10-25.
- Último punto estable: implementación, validaciones locales y review medium completados.

## Decisiones pendientes

- Ninguna; 72 horas configurables y recuperación de 60 minutos.

## Evidencia

- Pruebas: 67 backend, 511 aserciones (incluye 34 del reinicio QA); 2 Vue; build y Pint aprobados; E2E Chromium aprobado: vigencia, activación, rechazo del uso repetido e inicio de sesión.
- Revisión medium: registrados y corregidos clasificación de la tabla en el reinicio y preferencia de un enlace corto vigente frente a una invitación vencida; segunda pasada sin hallazgos.
- Captura: [activación móvil](../assets/invitacion-vigencia-mobile.png).
- Migraciones: tabla propia de tokens; requiere migrar antes de enviar invitaciones.
- Configuración: `INVITATION_EXPIRE_MINUTES=4320`; sin credenciales nuevas.

## Siguiente paso

Consultar CI y merge en Notion; continuar con configuración del equipo tras integrar.

## Cierre

- Commit o merge: consultar PR y Notion.
- Pendientes diferidos: despliegue independiente.

# Documentación viva e índice por temas

## Referencias

- Proyecto en Notion: https://app.notion.com/p/1a261db7db7f800c809bf96e22e0d05a.
- La antigua nota técnica de Notion figura eliminada al consultar el 26/10/2026. El seguimiento técnico permanece en esta Agent Note y en la PR; no se restaura ni duplica la nota eliminada.
- Rama: `docs/indice-temas-documentacion-viva`; base inicial `06a5723`, sincronizada con `main` en `735e822` el 26/10/2026.
- PR: https://github.com/jata-imk/kronik/pull/41 (borrador; sin integrar).
- Regla: [documentación viva](../reference/documentacion-viva.md).
- Navegación: [índice por temas](../topic/index.md).

## Objetivo

Aplicar la organización solicitada por el usuario: una ficha breve por tema que
enlace toda la documentación Diátaxis correspondiente y reglas explícitas para
mantenerla vigente después de implementar, integrar, desplegar y recibir QA.

## Estado actual

- Estado: documentación implementada, pendiente de revisión.
- Actualización: 2026-10-26.
- Se mantiene el contenido canónico en Diátaxis; las fichas solo orientan y enlazan.
- Resueltos los conflictos con `main` en `AGENTS.md` y los índices de explicación y Agent Notes; se conservan los ADR y notas incorporados después de abrir la PR.
- Instrucciones relevantes de `AGENTS.md` y `CLAUDE.md` unificadas en `AGENTS.md`, en español. `CLAUDE.md` solo referencia e importa la guía canónica.
- Se conserva la regla obligatoria de auditoría de `main` y su repriorización; se corrigen referencias antiguas a PDF, emisores SIC y registro automático de actividad.
- Fichas temáticas actualizadas para los nuevos ADR y notas de equipos, roles, invitaciones, SIC y solicitudes.
- Sin merge de esta PR, sin despliegue ni cambios de comportamiento.
- `AGENTS.md`, definición de terminado y flujo de PR incorporan actualización por confirmaciones de QA e índices de sección/tema.
- Se corrige el resumen P7/P8 de `AGENTS.md` y el estado de las entregas integradas de reset y correos.
- El usuario confirmó recepción, uso de invitaciones y visualización correcta del correo en Outlook clásico; la versión exacta no fue informada.

## Evidencia

- Revisión histórica del 18/10/2026: 15 temas y 73 documentos Diátaxis.
- Revisión renovada del 26/10/2026: 140 archivos Markdown, 15 fichas temáticas y 76 documentos Diátaxis; sin enlaces locales rotos, documentos fuera de su índice de sección ni documentos sin tema. `git diff --check` aprobado; sin conflictos sin resolver ni marcadores residuales. El CI del commit actualizado se consulta en la PR; no reutilizar el resultado histórico.
- Pruebas funcionales nuevas: no aplican; solo documentación y metadatos, sin cambios de aplicación o configuración.
- Migraciones, seeders y variables: ninguna.

## Siguiente paso

Revisar e integrar la entrega documental. Mantener índices y estados según la
regla al realizar nuevos cambios; no usar el control de presencia de docs como
sustituto de revisión de exactitud.

## Cierre

- Commit/PR: evidencia final en la PR de esta rama.
- Límites: no se realizó una certificación completa del contenido histórico ni de todas las variantes de correo; las confirmaciones conservan su alcance reportado.

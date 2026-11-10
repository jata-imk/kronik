# Días de gracia predeterminados de Empresa

## Referencias

- Notion: https://app.notion.com/p/41461db7db7f8169bbc5cbebcda5ee19
- Rama: `fix/empresa-dias-gracia`; base `640dc5f`.
- PR: pendiente.
- Contrato: [Empresa](../reference/configuracion-empresa.md#días-de-gracia-predeterminados).

## Estado actual — 2026-11-10

Implementado localmente; integración, despliegue y QA del usuario pendientes.
Empresa prellena nuevas capturas, respeta cero y usa 3 al faltar el valor.
Los productos existentes conservan condiciones. Sin migraciones, seeders ni variables nuevas.
Se conservan los eventos actuales; el prellenado no registra actividad.

## Evidencia

- Pruebas focalizadas backend: 11 aprobadas / 106 aserciones.
- Frontend, suite completa, build y E2E: en verificación.
- Revisión medium y barrido acumulado: pendientes antes de integración.

## Siguiente paso

Completar validación, registrar commit/PR y revisión; no inferir despliegue de la integración.

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
- Suite backend: 890 aprobadas / 46378 aserciones / 1 omitida (SQLite en memoria).
- Frontend: 153 aprobadas en 58 archivos. Build aprobado.
- E2E general inicial: 39 aprobadas, 4 omitidas por flags y 6 fallidas. El nuevo
  recorrido de días de gracia pasó. Revalidación en curso de PDF/documentos,
  filtros responsive, consulta/creación de política y recorrido largo de integrantes.
- Diagnósticos iniciales conservados en `storage/framework/testing/gracia-e2e-inicial`;
  log general en `storage/logs/dias-gracia-e2e-general.log` y revalidación en
  `storage/logs/gracia-e2e-revalidacion.log`.
- Revisión medium inicial contra main, código `d730cae`: sin hallazgos accionables.
  Revisión final y barrido acumulado pendientes antes de integrar.
- 96 enlaces locales revisados sin rotos.

## Siguiente paso

Completar validación, registrar commit/PR y revisión; no inferir despliegue de la integración.

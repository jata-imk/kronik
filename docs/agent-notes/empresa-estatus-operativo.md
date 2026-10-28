# Estatus operativo de empresa

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81f8a941ffe166235c8b
- Rama: `feat/empresa-estatus-operativo`; base `bc28e5e`.
- PR: https://github.com/jata-imk/kronik/pull/52.
- Decisión: [ADR 0029](../explanation/adr-0029-estatus-operativo-empresa.md).

## Estado actual

- 2026-10-27: integrado mediante PR #52, merge `234ec3d`. CI de PR y `main` aprobado; sin despliegue ni QA posterior del usuario acreditados.
- Decisiones pendientes: ninguna; captura/simulación en Borrador, suspensión de
  toda originación y RFC/correo obligatorios en documentos finales.
- Reporteo SIC y monitoreo/reporte PLD de cartera previa permitidos en Suspendida;
  esta tarea no implementa esos módulos.

## Evidencia y siguiente paso

- Suite backend del hook pre-push sobre `6e57010`: 497 aprobadas, una omitida, 41057 aserciones; build aprobado.
- Corregida atribución automática de Spatie: ejecutor sistema no hereda al usuario
  autenticado cuando se ejecuta síncronamente.
- Frontend: 100 pruebas Vue aprobadas, build aprobado; E2E Chromium aprobado.
- Review medium: corregidos sujeto UUID incompatible con auditoria numerica y admision atomica del PDF; regression de orden transaccional aprobada.
- Captura: [empresa suspendida](../assets/empresa-suspendida.png).
- Revision medium final sin hallazgos accionables. Regresion atomica y 23 pruebas de estatus aprobadas.
- Siguiente paso: aplicar migración y comprobar entorno cuando se programe despliegue; la implementación de folios continúa en otra PR con revisión final acumulada contra `bc28e5e`.
- Migración: contexto original documental; no reconstruye historia. Sin nuevas
  variables ni seeders productivos; el fixture E2E activa su empresa explícitamente.

## Cierre de implementación

- Implementación `6e57010`, notas `a41b769`, integración `234ec3d`.
- CI de PR: runs `40454032328` y `40626605579`; CI de `main`: `40627021433`, aprobado.

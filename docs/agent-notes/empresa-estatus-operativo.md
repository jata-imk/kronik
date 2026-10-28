# Estatus operativo de empresa

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81f8a941ffe166235c8b
- Rama: `feat/empresa-estatus-operativo`; base `bc28e5e`.
- PR: https://github.com/jata-imk/kronik/pull/52.
- Decisión: [ADR 0029](../explanation/adr-0029-estatus-operativo-empresa.md).

## Estado actual

- 2026-10-26: pausado por solicitud del usuario al terminar la jornada. Implementación publicada en `6e57010`, PR #52 abierta; sin integración ni despliegue acreditados.
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
- Siguiente paso al retomar: consultar CI de PR #52 y corregir si corresponde; integrar solo con checks aprobados. Continuar después con folios y ayuda contextual y cerrar con review medium del conjunto contra `bc28e5e`.
- La pausa se registró después de crear PR #52. No se comprobó todavía su CI; no se hizo merge.
- Migración: contexto original documental; no reconstruye historia. Sin nuevas
  variables ni seeders productivos; el fixture E2E activa su empresa explícitamente.

## Continuacion 2026-10-27

- Retomado por el usuario. CI de PR #52 aprobado sobre `6e57010` (run 40454032328); merge pendiente de completar en esta sesion.

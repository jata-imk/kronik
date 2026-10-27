# Estatus operativo de empresa

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81f8a941ffe166235c8b
- Rama: `feat/empresa-estatus-operativo`; base `bc28e5e`.
- PR: pendiente de apertura.
- Decisión: [ADR 0029](../explanation/adr-0029-estatus-operativo-empresa.md).

## Estado actual

- 2026-10-26: implementación en curso, sin integración ni despliegue acreditados.
- Decisiones pendientes: ninguna; captura/simulación en Borrador, suspensión de
  toda originación y RFC/correo obligatorios en documentos finales.
- Reporteo SIC y monitoreo/reporte PLD de cartera previa permitidos en Suspendida;
  esta tarea no implementa esos módulos.

## Evidencia y siguiente paso

- Suite backend inicial: 484 aprobadas, una omitida, 40920 aserciones.
- Corregida atribución automática de Spatie: ejecutor sistema no hereda al usuario
  autenticado cuando se ejecuta síncronamente.
- Frontend: 100 pruebas Vue aprobadas, build aprobado; E2E Chromium aprobado.
- Review medium: corregidos sujeto UUID incompatible con auditoria numerica y admision atomica del PDF; regression de orden transaccional aprobada.
- Captura: [empresa suspendida](../assets/empresa-suspendida.png).
- Revision medium final sin hallazgos accionables. Regresion atomica y 23 pruebas de estatus aprobadas.
- Pendientes: apertura de PR y CI.
- Migración: contexto original documental; no reconstruye historia. Sin nuevas
  variables ni seeders productivos; el fixture E2E activa su empresa explícitamente.

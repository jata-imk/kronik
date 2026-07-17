---
type: reference
area: workflow
status: active
---

# Flujo de Tareas, Pruebas y PR

## Regla General

Toda tarea funcional debe cerrar con código, pruebas aplicables, documentación Diataxis y evidencia en Notion.

## Pruebas

- Backend Laravel: Pest/PHPUnit para controllers, requests, services, policies, comandos y migraciones con comportamiento observable.
- Frontend Vue/Inertia: pruebas de componente cuando exista harness.
- Flujos críticos: Playwright e2e o, como mínimo temporal, screenshot manual documentado hasta que el harness e2e quede formalizado.
- Documentación o investigación: no requiere test automatizado, pero sí fuentes, fecha de consulta y trazabilidad.

## ADR

Crear un ADR en `docs/explanation/` cuando la tarea:

- Define un modelo de dominio.
- Cambia límites entre módulos.
- Introduce una integración externa.
- Cambia permisos, seguridad o despliegue.
- Decide entre alternativas técnicas con impacto futuro.

## Notion

Cada tarea funcional debe recibir comentario con:

- Qué se hizo.
- Qué pruebas se ejecutaron.
- Qué documentos se actualizaron.
- Riesgo residual o pendientes.

## PR

Crear PR por tarea cuando el cambio incluya migraciones, UI, permisos, integraciones, reglas de negocio o impacto operativo. Cambios menores de documentación o limpieza pueden ir como commit directo si no afectan comportamiento.

Checklist mínimo de PR:

- Tests ejecutados y resultado.
- Capturas o e2e si cambia UI.
- Documentación actualizada.
- Notion actualizado.
- Notas de migración, seeders o variables de entorno.

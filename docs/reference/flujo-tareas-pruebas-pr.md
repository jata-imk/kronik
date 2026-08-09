# Flujo de Tareas, Pruebas y PR

## Regla General

Toda tarea funcional debe cerrar con codigo, pruebas aplicables, documentacion Diataxis y evidencia en Notion o en la PR.

## Pruebas

- Backend Laravel: Pest/PHPUnit para controllers, requests, services, policies, comandos y migraciones con comportamiento observable.
- Frontend Vue/Inertia: pruebas de componente cuando exista harness.
- Flujos criticos: Playwright e2e o, como minimo temporal, screenshot manual documentado hasta que el harness e2e quede formalizado.
- Documentacion o investigacion: no requiere test automatizado, pero si fuentes, fecha de consulta y trazabilidad.

## ADR

Crear ADR en `docs/explanation/` cuando la tarea define modelo de dominio, cambia limites entre modulos, introduce integraciones, cambia permisos/seguridad/despliegue o decide entre alternativas tecnicas con impacto futuro.

## Agent Notes

Mantener una nota por backlog en `docs/agent-notes/` mientras exista trabajo activo. La nota registra contexto operativo, rama y PR, estado, evidencia, bloqueos y siguiente paso; no reemplaza Notion, los ADR ni la documentacion Diataxis. Al cerrar el backlog debe condensarse como entrega final y dejar de usarse como bitacora diaria.

## PR

Checklist minimo:

- Tests ejecutados y resultado.
- Capturas o e2e si cambia UI.
- Documentacion actualizada.
- Notion actualizado.
- Agent Note del backlog actualizada o cerrada.
- Notas de migracion, seeders o variables de entorno.

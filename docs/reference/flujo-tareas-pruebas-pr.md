# Flujo de Tareas, Pruebas y PR

## Regla General

Toda tarea funcional debe cerrar con codigo, pruebas aplicables, documentacion Diataxis y evidencia en Notion o en la PR.

Aplicar la [regla de documentación viva](documentacion-viva.md): actualizar
documentos, índices de sección y [temas](../topic/index.md) afectados. Registrar
también integración, despliegue y confirmaciones posteriores de QA en las notas
y Notion; esos resultados no deben seguir figurando como pendientes.

## Pruebas

- Backend Laravel: Pest/PHPUnit para controllers, requests, services, policies, comandos y migraciones con comportamiento observable.
- Frontend Vue/Inertia: pruebas de componente con Vitest y Vue Test Utils para logica, permisos y estados visibles.
- Flujos criticos: Playwright e2e en Chromium. La validacion manual complementa los escenarios visuales o exploratorios, pero no sustituye la regresion automatizada.
- Documentacion o investigacion: no requiere test automatizado, pero si fuentes, fecha de consulta y trazabilidad.

## ADR

Crear ADR en `docs/explanation/` cuando la tarea define modelo de dominio, cambia limites entre modulos, introduce integraciones, cambia permisos/seguridad/despliegue o decide entre alternativas tecnicas con impacto futuro.

## Agent Notes

Mantener una nota por backlog en `docs/agent-notes/` mientras exista trabajo activo. La nota registra contexto operativo, rama y PR, estado, evidencia, bloqueos y siguiente paso; no reemplaza Notion, los ADR ni la documentacion Diataxis. Al cerrar el backlog debe condensarse como entrega final y dejar de usarse como bitacora diaria.

## PR

Cada PR debe pasar `/review medium` antes de integración. Registrar base, commit
revisado, resultado y resolución de hallazgos; repetir revisión si cambia el código
después de corregirlos. La revisión complementa las pruebas, no las sustituye.

Una tarea puede requerir varias PR. Antes de cerrar su funcionalidad, revisar el
diff acumulado y la interacción de todas sus PR contra la base funcional, incluidos
contratos, migraciones, permisos, auditoría y regresiones. Registrar PR incluidas,
base y resultado; no limitar ese barrido a revisar cada diff por separado.

Checklist minimo:

- Tests ejecutados y resultado.
- Capturas o e2e si cambia UI.
- Documentacion actualizada.
- Índices de sección y tema actualizados; enlaces locales revisados.
- Notion actualizado.
- `/review medium` de la PR, hallazgos resueltos y evidencia del commit revisado.
- Barrido acumulado de las PR si esta entrega cierra una funcionalidad.
- Agent Note del backlog actualizada o cerrada.
- Notas de migracion, seeders o variables de entorno.
- Diagnosticos Playwright revisados cuando falle un recorrido: traza, captura, video y error de consola.

## Sesiones y traspasos

Organizar sesiones por tarea o incremento coherente y verificable, sin exigir una
sesión por PR. Si una tarea crece, continuar en otra sesión usando Notion y su
Agent Note: estado fechado, rama, base, PR/commits, decisiones aprobadas y abiertas,
pruebas/reviews y siguiente paso. No depender de reconstruir el historial del chat.
Una sesión nueva no reabre decisiones aceptadas ni acredita trabajo pendiente.

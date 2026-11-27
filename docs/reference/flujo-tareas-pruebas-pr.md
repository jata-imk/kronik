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

Durante cambios de UI, usar Playwright MCP para recorrer la aplicación y revisar
interacciones mientras se implementa, cuando esté disponible. Mantener también
los E2E reproducibles del repositorio; una exploración MCP no los sustituye. Si
el MCP no está disponible, usar el harness Playwright y registrar el método real,
sin atribuir herramientas ni escenarios no ejecutados. Las acciones que mutan
negocio se prueban con datos y BD aislados. Incluir escritorio/móvil,
claro/oscuro, teclado, foco, errores y permisos según la superficie afectada.

## ADR

Crear ADR en `docs/explanation/` cuando la tarea define modelo de dominio, cambia limites entre modulos, introduce integraciones, cambia permisos/seguridad/despliegue o decide entre alternativas tecnicas con impacto futuro.

## Agent Notes

Mantener una nota por backlog en `docs/agent-notes/` mientras exista trabajo activo. La nota registra contexto operativo, rama y PR, estado, evidencia, bloqueos y siguiente paso; no reemplaza Notion, los ADR ni la documentacion Diataxis. Al cerrar el backlog debe condensarse como entrega final y dejar de usarse como bitacora diaria.

## PR

Dividir una tarea en varias PR cuando existan incrementos coherentes que se
puedan revisar y validar por separado; una entrega pequeña puede ocupar una sola.
Registrar dependencias y orden de integración, conservando una base funcional
identificable para el barrido final.

Cada descripción debe permitir revisar la entrega sin reconstruir el chat:
problema, comportamiento final, tarea, validaciones y límites, y efectos de
migraciones/seeders/configuración. Si cambia la UI, incluir capturas revisadas
con datos sintéticos, preferentemente antes/después cuando expliquen el cambio.
Usar enlaces a commits o artefactos conservados para que las imágenes sigan
disponibles al eliminar la rama; no publicar datos personales, sesiones ni secretos.

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

La actualización documental es una responsabilidad de la entrega, no una tarea
resuelta automáticamente por hooks o CI. Estos comprueban presencia de cambios;
revisar además exactitud, vigencia, enlaces y cobertura temática conforme a la
[regla de documentación viva](documentacion-viva.md). Mantener Notion y Agent
Note ante publicación, integración, despliegue y resultados de QA; integrar una
PR no cierra por sí solo la aceptación funcional del usuario.

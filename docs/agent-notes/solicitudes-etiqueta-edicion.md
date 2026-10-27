# Solicitudes - Etiqueta de edición

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81d9ae1ff08faf029e47
- Rama: `fix/solicitudes-editar-etiqueta`
- PR: https://github.com/jata-imk/kronik/pull/46
- ADR relacionados: no requiere; ajuste de texto sin cambios de permisos o estados.

## Objetivo

Identificar la acción de reanudar un borrador como «Editar solicitud».

## Estado actual

- Estado: integrado en `main` mediante `ff372d8` (PR #46), con CI aprobado; sin despliegue acreditado.
- Última actualización: 2026-10-26.
- Último punto estable: implementación y validación local completas; revisión medium corregida.

## Decisiones pendientes

- Ninguna.

## Evidencia

- Pruebas: Vitest del detalle: 8 aprobadas. Build aprobado; backend: 428 aprobadas, 1 omitida, 40,484 aserciones. E2E Chromium: 1 aprobado, captura/edición, devolución/corrección, reenvío y cierre; resultados finales en la PR y Notion.
- Captura: [detalle de borrador](../assets/solicitud-editar-desktop.png).
- Diagnóstico E2E: la traza y captura mostraron que en móvil el folio no es el nombre del enlace. La comprobación de Mi trabajo identifica ahora el destino de la solicitud, conservando la aserción de visibilidad. Sin errores de consola en la traza.
- Revisión medium: primera pasada sin hallazgos de producto; segunda pasada detectó que el selector nuevo debía usar la URL absoluta de Ziggy. Corregido. El revisor no pudo ejecutar Vitest en su sandbox; la ejecución local independiente sí pasó.
- Migraciones o seeders: no requiere.
- Review medium acumulado de PR #46–#50 sobre `a6057a0..6c30e3c`: sin hallazgos pendientes; 43 pruebas backend focalizadas y 323 aserciones aprobadas. Nueve E2E de la iniciativa aprobados antes del último merge.
- QA posterior del usuario: no se ha reportado para esta entrega.

## Siguiente paso

Despliegue y QA funcional cuando se programe; el lote de cinco tareas quedó integrado.

## Cierre

- Commit o merge: implementación `c11fb25`, corrección de E2E `b24b9b7`; merge `ff372d8` en PR #46.
- Pendientes diferidos: despliegue separado de la integración.

# Solicitudes - Etiqueta de edición

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81d9ae1ff08faf029e47
- Rama: `fix/solicitudes-editar-etiqueta`
- PR: pendiente de apertura.
- ADR relacionados: no requiere; ajuste de texto sin cambios de permisos o estados.

## Objetivo

Identificar la acción de reanudar un borrador como «Editar solicitud».

## Estado actual

- Estado: en progreso.
- Última actualización: 2026-10-25.
- Último punto estable: cambio de etiqueta y ocho regresiones Vue aprobadas.

## Decisiones pendientes

- Ninguna.

## Evidencia

- Pruebas: Vitest del detalle: 8 aprobadas. Build aprobado; backend: 428 aprobadas, 1 omitida, 40,484 aserciones. E2E en validación; resultados finales en la PR y Notion.
- Diagnóstico E2E: la traza y captura mostraron que en móvil el folio no es el nombre del enlace. La comprobación de Mi trabajo identifica ahora el destino de la solicitud, conservando la aserción de visibilidad. Sin errores de consola en la traza.
- Revisión medium: primera pasada sin hallazgos de producto; segunda pasada detectó que el selector nuevo debía usar la URL absoluta de Ziggy. Corregido. El revisor no pudo ejecutar Vitest en su sandbox; la ejecución local independiente sí pasó.
- Migraciones o seeders: no requiere.

## Siguiente paso

Completar validación local y CI antes de integrar; registrar resultados y merge en Notion.

## Cierre

- Commit o merge: pendiente.
- Pendientes diferidos: despliegue separado de la integración.

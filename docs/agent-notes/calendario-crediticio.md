# Calendario crediticio

- Rama `feat/calendario-crediticio`, base `7117c4c`, worktree aislado en `output/calendario-crediticio/worktree`.
- [Tarea de calendario](https://app.notion.com/p/41461db7db7f810da275d6aa2ff40fcd) y [periodicidad diaria](https://app.notion.com/p/41461db7db7f81bdb598f860b5c7426f).

## 18/11/2026

- [Calendario crediticio](../reference/calendario-crediticio.md).

Decisiones confirmadas: calendario institucional, plantilla CNBV revisable,
cobertura revisada obligatoria, ajuste al siguiente hábil del cierre y
vencimiento, publicación exclusiva de Super Admin. Se implementa en la rama
aislada; integración, despliegue y validación QA se registran con su evidencia.

La revisión del catálogo detectó una fecha emitida por el selector como objeto,
una previa que podía usar una revisión más reciente y un acceso lateral sujeto
al permiso de Productos. Se normalizó la fecha civil al capturarla, se validó
la revisión solicitada y se agregó un acceso lateral con permiso propio.

La prueba de reverso detectó lecturas del mismo día que omitían evidencia con
hora almacenada. Se usa un límite exclusivo al inicio del día siguiente y
se registra la fecha civil en nuevos reversos. Se verifican reconstrucción
y conservación del cronograma firmado en ambas políticas Bullet.

Evidencia local durante el desarrollo: 54 pruebas backend y 619 aserciones de
calendario, originación, tablero y reinicio. Vitest pasó 173 pruebas y Chromium
verificó publicación, previa por teclado, móvil y tema oscuro en SQLite aislada.
Las tareas de calendario y periodicidad fueron actualizadas en Notion.

Revisión medium acumulada sobre la primera implementación detectó que el CAT de
un solo pago podía perder un día en un cambio de horario y que omitir la política
al editar podía restablecerla. Se reutilizó el conteo de fechas civiles y se
conservó el valor guardado cuando el campo se omite. La revisión del catálogo
también llevó a fijar la revisión al confirmar y limpiar capturas al pasar a
consulta. Playwright MCP reprodujo la previa del 14 al 17 de noviembre.

## Entrega

- [PR #86 · Catálogo institucional](https://github.com/jata-imk/kronik/pull/86).
- [PR #87 · Aplicación al crédito](https://github.com/jata-imk/kronik/pull/87), dependiente de #86.
- Base de la entrega rebasada sobre `8884a78`; ambos incrementos conservan el sidebar integrado.
- Regresión local anterior a las correcciones de revisión: 983 pruebas backend aprobadas, 47776 aserciones y dos omitidas. El recorrido Chromium actualizado aprobó edición de la cobertura, publicación, teclado y modo oscuro.
- Capturas sintéticas del recorrido: [previa en escritorio](../audits/calendario-crediticio/calendario-ejemplo-claro.png) y [calendario móvil en oscuro](../audits/calendario-crediticio/calendario-movil-oscuro.png).
- Pruebas dirigidas del catálogo y originación sobre `3443685`: 11 aprobadas y 110 aserciones. La CI valida MariaDB y la concurrencia.

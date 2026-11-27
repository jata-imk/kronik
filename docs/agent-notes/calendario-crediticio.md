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
- La segunda revisión llevó a comprobar permisos de borradores antes de
  validar la previa. La lectura financiera y el tablero comparten el límite
  exclusivo del día siguiente y conservan cobertura cuando un reverso antiguo
  se guardó con hora. Se verifican liquidación y etiquetas de validación.
- La última corrección limita los motivos del calendario a lecturas autorizadas
  y redondea el CAT de pago único mediante comparaciones decimales exactas.
- El paquete fiscal reproduce el CAT aprobado. La búsqueda decimal de pago
  único usa cotas y reserva los enteros exactos para empates. El caso sintético
  de 18 263 días tardó 10 ms en PHP 8.3.16 en el worktree local.
- El recorrido de originación compara el paquete con una revisión sintética
  cuyo CAT aprobado difiere del cálculo actual. Prueba el valor conservado
  durante la formalización en ambas políticas Bullet.

## 21/11/2026

- La base de ambas PR incorpora `da529b2`. Calendario tiene destinos propios en
  el menú contextual. El permiso de consulta admite las publicaciones y el
  acceso a un borrador exige permiso de edición sobre el equipo activo.
- Chromium confirmó que cancelar el descarte conserva la cobertura capturada
  y que luego se puede guardar y publicar el borrador. El recorrido también
  comprobó la previa con teclado y la vista móvil en tema oscuro.
- La ejecución local dirigida de calendario, originación y menú aprobó 23
  pruebas y 504 aserciones sobre la base actualizada.
- Sobre `dbd4b99` aprobaron 1 013 pruebas backend, 48 011 aserciones y
  207 pruebas frontend. Dos pruebas backend se omitieron. También aprobó
  Chromium para la previa, el editor y la publicación.
- El intento de CI `44458919363` agotó el tiempo de la prueba de aprobación
  dual después del desembolso. Su reintento reutilizó la solicitud desembolsada.
  El recorrido local posterior aprobó en 2.7 minutos con SQLite nueva y sin
  reintentos. Cubrió formalización, desembolso, pago, reverso, anticipo y
  liquidación.
- La revisión medium corrigió el diagnóstico de fórmula del simulador. El
  caso mensual verifica el ancla nominal del 28 de febrero, el cierre ajustado
  del 2 de marzo y el siguiente vencimiento del 31 de marzo.
- El barrido acumulado sobre `da529b2` revisó ambas PR. La versión de calendario
  tiene un parámetro de menú propio y el editor se cierra al perder edición.
  Chromium verificó el foco en el error y que los errores de otras filas
  permanecen al quitar una. El CAT de pago único Act/360 usa los días efectivos
  con ambas políticas, con pruebas de equivalencia y de conservación del CAT
  aprobado en expedientes con y sin ajuste.
- Sobre `b9a29e5` aprobaron 1 016 pruebas backend (dos omitidas), 48 040
  aserciones y 208 pruebas frontend. La última regresión fiscal comprobó que
  formalizar usa directamente el CAT aprobado aunque la búsqueda periódica
  histórica no tenga un resultado utilizable. Aprobaron 72 pruebas dirigidas.
- El parámetro contextual de la versión cambió durante la revisión de PR.
  La base `da529b2` aún carece del catálogo y de ese destino configurable.
- Sobre `6d3d5c4` aprobaron 1 018 pruebas backend, 48 047 aserciones y dos
  omisiones. Se usó un directorio de archivos de prueba separado para evitar
  colisiones con el proceso de revisión. Chromium sobre `a2214e0` verificó que
  editar o quitar una fecha repetida elimina el aviso y conserva los demás.

## Integración del 21/11/2026

- [PR #86](https://github.com/jata-imk/kronik/pull/86) integrada en `main` como
  `b30a900`. [PR #87](https://github.com/jata-imk/kronik/pull/87) pasó a
  `main` después del catálogo y quedó integrada como `a14a959`. El árbol
  final coincide con `bb33689`, la revisión validada antes de integrar.
- CI de las PR aprobada en [catálogo](https://github.com/jata-imk/kronik/actions/runs/44983267126)
  y [originación](https://github.com/jata-imk/kronik/actions/runs/44983266826).
  La [ejecución en main](https://github.com/jata-imk/kronik/actions/runs/44984209293)
  corresponde a la integración de ambos incrementos.
- Siguiente paso en QA compartido. Aplicar la migración, actualizar los catálogos
  de permisos y menú, y seguir el [recorrido de validación](../how-to/qa-calendario-crediticio.md).
  Despliegue y aceptación pendientes de evidencia del responsable de QA.

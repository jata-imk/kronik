# Plantillas globales de Notificaciones

I3 administra SMS y correo dentro de Comunicaciones. Una instalación corresponde a
una financiera; los equipos son contextos de permisos y operación. Las plantillas
no pertenecen a equipos ni tienen variantes privadas. WhatsApp conserva sus reglas
y comprobaciones Meta anteriores.

## Catálogo y autorización

Se permiten varias plantillas globales por evento y canal. La clave, el canal y el
evento son estables. Eventos iniciales: pago recibido y próximo vencimiento.
Los permisos independientes de `plantillas-notificaciones` son `read`, `create`,
`update`, `version`, `activate` y `retire`. No exige Super Admin ni permisos
de documentos. Gate los evalúa en el equipo autorizado, sin convertirlo en dueño
del recurso. La navegación ofrece Notificaciones y Comunicaciones enlaza el catálogo.

El listado y las versiones se paginan de diez en diez. `plantilla_id`,
`version_id` y `modo=consulta|edicion` resuelven el ID exacto independientemente
de filtros y páginas. Una versión ajena devuelve 404. Consulta reutiliza el
formulario sin guardar, insertar variables ni ofrecer controles internos de edición.

## Versiones y uso

Una versión borrador es editable. Activa y retirada conservan contenido, formato,
asunto, hash y resumen inmutables. Para modificar una redacción se duplica una
versión exacta en un borrador numerado. Activar retira la activa anterior de esa
misma plantilla. Retirar no borra el historial ni permite reactivarlo.

Las reglas SMS/correo eligen una plantilla compatible y utilizan su versión activa.
Horario, anticipación, consentimiento, límites y elegibilidad siguen operando por
su contexto original. Una regla sin selección o cuya plantilla quedó sin versión activa puede desactivarse
sin elegir reemplazo; volver a habilitarla exige una plantilla compatible activa.
No se copia el texto a cada equipo ni se modifica una
plantilla mediante el permiso de gestionar reglas.

Cada salida conserva `notification_template_version_id`; al intentar el envío,
`message_snapshot` guarda cifrados asunto, cuerpo definitivo, formato y variables.
No se serializa en props ni en auditoría. El formato pasa explícitamente al
transporte junto al cuerpo preparado; no se consulta de nuevo una versión activa
mutable después de iniciar el intento.

Activar una sustituta, retirar o cambiar la selección cancela salidas preparadas
y en cola que todavía no tienen intento. Las filas se conservan y su unicidad por
evento/canal evita recrear el aviso. Los intentos iniciados, aceptados o inciertos
conservan su evidencia y nunca se reenvían por el cambio. Plantilla, regla, versión
y salida se revalidan bajo bloqueos; estado y auditoría comparten transacción.

## Texto, HTML y variables

SMS persiste texto plano; correo elige texto o HTML. Solo se admiten `{nombre}`
(primer nombre del titular) y `{fecha_vencimiento}` (fecha del evento original).
No se habilita el catálogo documental, expresiones ni propiedades arbitrarias.
Una variable utilizada sin dato produce un error legible.

Texto conserva acentos, saltos y entidades literales. HTML se sanea mediante el
compilador documental y sustituye valores escapados. El correo HTML admite formato
de texto del editor; no incluye imágenes privadas ni paginación. Su presentación
coincide con el HTML preparado para el Mailable. Correo de texto conserva la vista
de texto y SMTP; su parte MIME de texto conserva entidades y etiquetas literales sin aplicar escape HTML. Aceptación no acredita entrega al buzón.

La previa SMS mide el texto sintético ya sustituido mediante `SmsText`. Informa
codificación, unidades y segmentos estimados. El backend vuelve a medir el mensaje
real antes del intento y cancela si excede dos segmentos, sin truncar ni quitar
acentos. La muestra no demuestra facturación ni longitud de todos los destinatarios.

## Previsualización

GET de versión guardada requiere lectura y utiliza el ID exacto. POST de borrador
nuevo exige creación; con `version_id`, exige edición de un borrador efectivo.
Las previas usan datos sintéticos y no persisten recursos de negocio, documentos
ni salidas. Texto/HTML no resuelven renderer PDF, Chromium ni transportes.

El HTML aparece en iframe con sandbox sin capacidades y CSP restrictiva:
sin scripts, formularios, navegación, conexiones ni recursos externos. Los
documentos reutilizan recursos privados verificados y fuentes locales embebidas;
la consulta no concede acceso adicional a la biblioteca. Las respuestas no se
almacenan en caché. Cambiar contenido, selección, modo, permisos o contexto limpia
la previa, aborta peticiones y descarta respuestas tardías.

## Migración y límites

`2026_11_26_100000` añade catálogo/versiones y referencias nullable a reglas/salidas.
No convierte textos desechables ni rellena salidas anteriores. Las reglas SMS/correo
sin selección necesitan elegir una plantilla activa; no hay fallback al texto
antiguo. Las tablas se clasifican en el reinicio QA. No se ejecuta limpieza operativa.

La doble barrera SMS y general permanece cerrada. I2 conserva únicamente su núcleo
interno; recepción autenticada, cuenta/ruta y piloto siguen pendientes. No se
implementa Integraciones. Ver [canales](canales-comunicacion.md),
[seguimiento SMS](seguimiento-sms.md), [decisión I3](../explanation/adr-0051-notificaciones-globales-y-previas-html.md)
y [procedimiento de edición](../how-to/editar-notificaciones.md).

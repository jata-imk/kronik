# Evaluación del editor para plantillas de notificaciones

Fecha: 2026-11-24. Evaluación inicial; ver la decisión I3 vigente. Complementa la
[planeación SMS](integracion-sms-labsmobile.md) y el
[contrato documental](../reference/documentos-y-plantillas.md).

## Ampliación confirmada de I3 — 25/11/2026

José pidió que la integración del editor incluya una sección compartida de
previsualización por formato: texto plano para SMS y HTML renderizado para correo
y plantillas documentales. Para documentos se acepta HTML continuo, sin paginación
ni fidelidad exacta al PDF. Generar PDF no debe ser requisito para previsualizar;
conservarlo como una acción explícita opcional para documentos.

La vista previa debe admitir cambios sin guardar y versiones guardadas, con datos
sintéticos y las variables del recurso/canal. Mostrar formato y carácter estimado.
Reutilizar compilación, saneamiento, estilos y permisos existentes; no crear otro
motor de plantillas. La representación HTML debe estar aislada, sin scripts ni
acceso a recursos ajenos o externos no autorizados. Texto SMS conserva saltos,
acentos y entidades literales, sin interpretarse como HTML.

El correo actual usa `CommunicationMail::text()`. La previsualización HTML de correo
no puede ser solo un contenedor que muestre etiquetas como texto: debe corresponder
al cuerpo real del formato soportado. Integrar HTML saneado en la experiencia del
editor/correo cuando corresponda, conservando el correo de texto existente, SMTP,
permisos, consentimiento, auditoría y barreras. No habilitar envíos reales.

En el código revisado `c7a31bd` ya existe `DocumentoPlantillaController::preview()`:
devuelve encabezado/cuerpo/pie HTML compilados de una versión guardada. La UI actual
usa `DocumentPdfPreview` y endpoints PDF, incluso para cambios sin guardar.
Aprovechar la ruta HTML existente y completar borradores/autorización al implementar.
Las vistas de texto/HTML no llaman `DocumentoRenderService`, Chromium ni generan
archivos, versiones o registros de negocio. Mantener el flujo PDF y sus controles.

Pruebas de aceptación adicionales: texto/HTML sin invocar renderer PDF; versiones
guardadas exactas y cambios sin guardar; variables y recursos autorizados; aislamiento
HTML/XSS; consulta sin mutaciones, móvil y teclado; cancelación de solicitudes y
respuestas tardías al cambiar versión/modo; regresión de correo y generación PDF.
Registrar la decisión duradera correspondiente en el ADR de implementación I3.

Esta ampliación fija requisitos para la siguiente sesión; no está implementada.
Prevalece, junto con el [plan vigente](plan-incrementos-sms.md), sobre las alternativas
preliminares que siguen a continuación.

## Propuesta y valoración

Reutilizar la experiencia del editor existente para redactar notificaciones,
insertar variables y previsualizar, con una categoría visual propia. En SMS,
texto plano, sin formatos ni imágenes y con información de longitud/segmentos.

Es factible y recomendable reutilizar las piezas que encajen. El componente
actual y su modelo documental no son intercambiables con una plantilla SMS:
deben evaluarse límites de formato, contexto y autorización antes de añadir
un tipo al catálogo. No se adopta todavía una arquitectura de almacenamiento.

## Evidencia del código inspeccionado

| Pieza actual | Qué aporta | Diferencia que debe resolverse |
| --- | --- | --- |
| `Components/Documents/DocumentEditor.vue` | Inserción en cursor, deshacer/rehacer y consulta sin edición. | Usa Quill, publica HTML y permite recursos/saltos. Ocultar la barra no cambia el formato persistido. |
| `Pages/Documentos/Plantillas/Index.vue` | Panel de variables buscable y agrupado, selección y versiones. | El panel vive en la página; no es una capacidad autónoma del componente de edición. |
| `DocumentoPlantillaTipo` | Tipos visibles de consentimiento SIC, garantía y contrato. | La categoría Notificaciones no existe; no es solo agregar una etiqueta. |
| `DocumentoPlantillaVersion` y su servicio | Borrador/activa/retirada, inmutabilidad y hash. | El contenido es HTML con encabezado/pie/presentación y referencias a recursos/PDF. |
| `CatalogoVariablesDocumento`, compilador y resolver | Claves con metadatos, validación por tipo y sustitución. | El resolver exige contexto documental; el compilador sanea HTML y escapa valores como HTML. |
| `CommunicationRule` y `ConfigureRuleRequest` | Texto por equipo/evento/canal y contador de versión. | El cuerpo es texto con límite actual de 2,000, sin catálogo documental ni archivo de versiones completas. |

Las rutas exactas se pueden encontrar por estos nombres; la revisión corresponde
a `ecb9d4a`. El contrato del catálogo global y el histórico se explica en
[ADR 0008](adr-0008-arquitectura-documentos-y-plantillas.md).

## Reutilización que conviene evaluar primero

1. Compartir de forma acotada panel/buscador e inserción de variables. Mantener
   el formulario y los permisos del recurso donde se editan; no duplicar una
   administración completa ni crear un editor universal.
2. Evaluar un modo explícito de texto plano en la experiencia del editor actual.
   Si el núcleo Quill obliga a conservar HTML, una superficie de texto puede
   compartir el panel y la composición visual sin compartir el formato guardado.
3. Incorporar una categoría visual Notificaciones con identificación de canal y
   evento. La ubicación en el catálogo existente o en Comunicaciones se resolverá
   comparando el alcance y la autorización, no por conveniencia de la UI.
4. Vista previa de mensaje con datos sintéticos, contador de segmentos y avisos
   de variables faltantes, más consulta inmutable de versiones anteriores.

No basta ocultar negritas, imágenes o encabezados. Cubrir pegado con formato,
atajos, arrastre, contenido existente y validación de backend. Persistir texto
plano explícito: convertir HTML a último momento puede cambiar saltos, espacios
y entidades. No aplicar el escape HTML documental al texto final de un SMS.

## Variables y longitud

Documentos usa `{{clave.tecnica}}`; Comunicaciones usa `{nombre}` y
`{fecha_vencimiento}`. Definir sintaxis, mapeo y compatibilidad de las reglas
existentes; no hacer sustituciones ambiguas ni cambiar el significado de valores
históricos. En particular, el nombre corto de comunicaciones no equivale al
nombre completo del catálogo documental.

Agregar un tipo a `DocumentoPlantillaTipo` haría que las variables definidas con
“todos los tipos” incluyeran esa categoría. El catálogo contiene RFC, CURP y otros
datos que no deben habilitarse automáticamente para SMS. Definir una lista
permitida por canal/evento/finalidad y su origen autorizado; el panel no debe
inventar acceso libre a propiedades ni expresiones ejecutables.

La longitud se calcula con las variables sustituidas. El contador de la plantilla
sin resolver no asegura cuánto consumirá un mensaje real. Evaluar muestra y
límite de segmentos; validar otra vez en backend al preparar/despachar para el
destinatario real. Contar según codificación y unidades del canal, no solo por
caracteres visibles. Conservar la redacción y no quitar acentos automáticamente.
Una muestra de vista previa es estimación, no evidencia de facturación.

## Categoría, permisos y versiones

Las plantillas documentales son globales por instalación y usan permisos de
`plantillas-documentos`; las reglas de comunicaciones guardan `team_id` y se
gestionan con permisos de comunicaciones. Reutilizar UI no concede acceso a
plantillas o eventos de otro equipo. Determinar si habrá contenido institucional
global y personalización por prestamista o solo texto por equipo.

Relacionar la versión exacta de redacción con cada salida y resolver qué pasa si
cambia mientras está en cola. El entero `template_version` actual no conserva
por sí solo los textos antiguos. Si se añaden snapshots, su contenido y valores
sensibles deben permanecer en evidencia protegida; la auditoría recibe solo
identificadores y metadatos permitidos. No convertir un SMS en documento generado
ni llamar al renderer PDF para previsualizarlo.

Correo y WhatsApp mantienen sus contratos. Una plantilla local no reemplaza la
aprobación externa de Meta. El modo SMS no debe truncar ni retirar capacidades
de contratos, garantías o consentimientos ya existentes.

## Alternativas que debe resolver la siguiente sesión

| Alternativa | Ventaja | Costo o condición |
| --- | --- | --- |
| Añadir Notificaciones al catálogo y reutilizar también versiones documentales | Gestión y ciclo de versiones compartidos. | Separar texto/HTML, permisos, alcance global/equipo, variables y generación; afecta al módulo documental. |
| Reutilizar editor/panel y administrar textos con Comunicaciones | Conserva el contexto actual de equipo/canal/evento. | Definir versiones completas y experiencia visual coherente; compartir piezas sin duplicar formularios. |

Recomendación preliminar: probar primero la reutilización de UI con contenido de
texto plano y permisos de Comunicaciones, y contrastarla con el deseo de una
categoría en el catálogo existente. La decisión final requiere propuesta y
aceptación; esta evaluación no descarta la categoría solicitada ni impone un
segundo módulo. Si cambian modelos o límites, registrar el ADR correspondiente.

## Evidencia que deberá producir la implementación

- Regresión de contratos, garantías y SIC: edición, consulta, PDF e históricos.
- Inserción y sustitución de variables permitidas; rechazo claro de desconocidas,
  incompatibles o datos obligatorios faltantes, sin exponer claves `validation.*`.
- Texto plano por pegado, teclado y arrastre; caracteres especiales, nombres
  largos y segmentación tras sustitución; pruebas de frontend y backend.
- Permisos por equipo/sucursal, acceso directo y consulta sin mutaciones.
- Versiones exactas, cambios con salidas en cola, auditoría y ausencia de valores
  sensibles en logs. Las vistas previas no crean envíos ni documentos de negocio.
- E2E aislado de categoría/editor, variables, vista previa, móvil y teclado,
  con eventos auditables y matriz actualizados si se implementan acciones nuevas.

Esta entrega solo inspecciona código y deja la evaluación. No cambió componentes,
modelos, tipos, permisos, datos, eventos auditables ni reglas operativas.

## Resolución I3 — 26/11/2026

José confirmó varias plantillas globales seleccionables, exclusivamente SMS/correo. Se adopta persistencia propia en Comunicaciones, panel compartido y capacidades acotadas del editor, sin nuevo tipo documental. HTML continuo y PDF opcional se separan. La implementación y sus límites se rigen por [ADR 0051](adr-0051-notificaciones-globales-y-previas-html.md) y [el contrato](../reference/plantillas-notificaciones.md); las alternativas anteriores son antecedentes. Integración, CI final y revisión se acreditan en la Agent Note.

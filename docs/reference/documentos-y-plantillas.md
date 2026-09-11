# Documentos digitales y plantillas

## Alcance

El catálogo administra plantillas globales de consentimiento SIC, garantías y contratos. Cada plantilla conserva versiones borrador, activas y retiradas. Los contratos no generan archivo final hasta que exista el contexto de originación.

## Estados y reglas

- Borrador: editable si todavía no se utilizó.
- Activa: disponible para generar y completamente inmutable.
- Retirada: no genera documentos nuevos; conserva historial y archivos existentes.
- Pendiente/procesando/generado/fallido: estados operativos de un documento solicitado a la cola.

Activar una versión retira la activa anterior. Para cambiar una versión activa debe duplicarse. El hash de contenido identifica exactamente la redacción utilizada.

## Variables

Las variables usan sintaxis `{{clave.tecnica}}`. El panel del editor muestra únicamente las claves aplicables al tipo seleccionado. Una clave desconocida o incompatible impide guardar o activar. Los valores se escapan como texto y una variable obligatoria sin dato impide generar con un mensaje legible.

«Obligatoria» significa que necesita un dato si se utiliza, no que deba insertarse en todas las redacciones. El cuerpo exige texto visible; no admite únicamente espacios, imágenes o saltos de página.

## Edición y presentación

Quill admite títulos 1–3, fuentes locales sans/serif/monoespaciada, tamaños discretos de 8 a 36 pt, énfasis, alineación, sangrías, listas, citas, color y resaltado. Encabezado y pie se editan por separado. El cuerpo admite saltos manuales; el PDF usa A4 vertical y rechaza encabezados o pies superiores a 60 mm.

La presentación por versión contiene `formato: 2` y `marca_agua` (texto opcional, máximo 80 caracteres, sin variables). Los nuevos hashes incluyen esa presentación y los hashes de los recursos; la migración no recalcula hashes históricos.

La biblioteca global recibe imágenes privadas PNG/JPEG de hasta 2 MB y 2048 × 2048 px, recodificadas por GD a PNG. Recursos UUID inmutables y referencias por versión evitan cambios retroactivos de logotipos. Se permite ancho de 24–650 px y texto alternativo de hasta 160 caracteres. No hay URLs externas, SVG ni eliminación de recursos desde la aplicación. La carga registra un evento de auditoría sin contenido ni rutas del archivo.

Las previsualizaciones guardada y sin guardar usan PDF real con datos sintéticos, sin crear registros de generación. Cada solicitud exige permiso de lectura; previsualizar cambios y cargar recursos exige además creación o actualización. Ambos endpoints de trabajo tienen límite de 10 solicitudes/minuto/usuario. Ver [guía de edición y despliegue](../how-to/editar-plantillas-documentales.md).

## Archivos privados

PDF, JPG y PNG permanecen en el disco privado. Las rutas de vista y descarga autentican y autorizan cada solicitud, resuelven el archivo desde el modelo, impiden recorridos de ruta y vuelven a verificar MIME y extensión. Las respuestas no se almacenan en caché y no exponen la ruta física.

El visor obtiene un blob de una ruta autenticada y descarta su URL al cerrarse. Abrir el visor y descargar son eventos distintos; nunca se registra el contenido.

## Operación

La generación usa la cola configurada de Laravel. Un identificador UUID de idempotencia evita duplicados. Los reintentos reutilizan el mismo registro y un bloqueo evita dos renders simultáneos. Los errores visibles no incluyen HTML, datos personales, paths ni salida del proceso.

La configuración relacionada se encuentra en `config/documentos.php` y usa `DOCUMENTOS_DISK`, `DOCUMENTOS_PDF_RENDERER`, `DOCUMENTOS_PDF_TIMEOUT` y `DOCUMENTOS_MAX_UPLOAD_KB`.

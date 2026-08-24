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

## Archivos privados

PDF, JPG y PNG permanecen en el disco privado. Las rutas de vista y descarga autentican y autorizan cada solicitud, resuelven el archivo desde el modelo, impiden recorridos de ruta y vuelven a verificar MIME y extensión. Las respuestas no se almacenan en caché y no exponen la ruta física.

El visor obtiene un blob de una ruta autenticada y descarta su URL al cerrarse. Abrir el visor y descargar son eventos distintos; nunca se registra el contenido.

## Operación

La generación usa la cola configurada de Laravel. Un identificador UUID de idempotencia evita duplicados. Los reintentos reutilizan el mismo registro y un bloqueo evita dos renders simultáneos. Los errores visibles no incluyen HTML, datos personales, paths ni salida del proceso.

La configuración relacionada se encuentra en `config/documentos.php` y usa `DOCUMENTOS_DISK`, `DOCUMENTOS_PDF_RENDERER`, `DOCUMENTOS_PDF_TIMEOUT` y `DOCUMENTOS_MAX_UPLOAD_KB`.

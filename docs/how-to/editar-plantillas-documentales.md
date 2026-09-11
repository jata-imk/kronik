# Editar plantillas documentales

## Crear o modificar una redacción

1. Abre **Documentos y plantillas** desde el menú lateral o superior. Necesitas permiso de lectura y de creación o actualización.
2. Crea una plantilla o edita un borrador sin usos. Una versión activa, retirada o utilizada es inmutable: usa **Duplicar** para proponer cambios.
3. Edita por separado encabezado, contenido y pie. Cada sección conserva su cursor. Al pulsar una variable se inserta en el cursor o reemplaza la selección; deshacer/rehacer funciona también para esa inserción.
4. Usa títulos 1–3, fuentes sans/serif/monoespaciada, tamaños, listas, sangrías, alineación, negrita, cursiva, subrayado, tachado, citas, color y resaltado. Los saltos manuales de página se insertan solo en el contenido.
5. Pulsa **Previsualizar cambios** para revisar el PDF A4 vertical sin guardar. Puedes cambiar página, ajustar el ancho o ampliar. La vista usa datos sintéticos y el mismo motor de la generación final. Cerrar la vista previa no cierra el editor.
6. Guarda el borrador. La marca de agua y las imágenes se guardan en esa versión. Actívala cuando esté aprobada; se retira la versión activa anterior. Al volver a una plantilla se muestra su versión activa, si existe; después de crear o duplicar se selecciona el nuevo borrador.

Las variables marcadas como requeridas exigen un valor **si se usan**; no obligan a incluirlas todas. El contenido no puede estar vacío ni contener únicamente espacios, imágenes o saltos. No se ejecutan HTML activo, imágenes externas ni código pegado desde otros programas.

## Logotipos e imágenes

La opción **Imagen** abre una biblioteca global por instalación, privada y reutilizable. Admite PNG/JPG de hasta 2 MB y 2048 × 2048 píxeles. Las imágenes se verifican y normalizan a PNG; el límite de entrada no equivale al tamaño del PNG normalizado almacenado. SVG, enlaces externos y archivos disfrazados no se aceptan.

Selecciona un recurso existente o sube uno. Pulsa la imagen en el editor para ajustar su ancho entre 24 y 650 px y su texto alternativo. Usa la alineación del párrafo para colocarla. Quitar una imagen del texto no elimina el recurso de la biblioteca. Los recursos son inmutables para no alterar documentos históricos; para cambiar un logotipo, sube otro.

Encabezado y pie se repiten en cada página; el motor mide su altura y rechaza secciones mayores de 60 mm. El pie incluye el número de página. Comprueba siempre el PDF cuando uses imágenes o títulos grandes.

La **marca de agua** opcional admite hasta 80 caracteres de texto, sin variables, y se imprime en gris tenue diagonal en todas las páginas. No es una firma ni una protección anticopia.

## Documentos del expediente

- **Quitar selección** descarta el archivo elegido antes de enviarlo; no elimina documentos ya almacenados.
- El motivo de rechazo requiere entre 10 y 2000 caracteres, después de quitar espacios al inicio y al final. Al revisar otro documento el formulario empieza limpio.
- El historial de cada documento muestra su cadena de reemplazos con acceso privado a ver y descargar. No mezcla otros tipos documentales.

## Desplegar esta ampliación

1. Respalda base de datos y almacenamiento privado siguiendo el procedimiento habitual.
2. Instala GD para la versión PHP usada tanto por FPM como por CLI/worker. `composer install --no-dev --optimize-autoloader` valida `ext-gd`. No basta habilitarla solo en CLI.
3. Ejecuta `npm ci` y `npm run build`. Publica también `public/fonts/liberation/` con sus fuentes y licencia SIL OFL; son archivos versionados, no descargas en tiempo de ejecución.
4. Ejecuta `php artisan migrate --force`. La migración agrega recursos, referencias por versión y presentación; no reescribe versiones, hashes ni PDF existentes. No ejecutes `migrate:fresh` ni seeders generales en la VPS.
5. Conserva las rutas Node/Chromium propias del VPS en `DOCUMENTOS_*`; las pruebas E2E usan sus propias rutas locales. PHP web necesita ejecutar Chromium para la vista previa; el worker lo necesita para documentos finales.
6. Recarga la configuración y reinicia el worker mediante el mecanismo ya configurado. Verifica permisos de escritura sobre el disco privado de documentos y lectura de fuentes.
7. Prueba un borrador con logotipo, encabezado, pie, marca de agua y dos páginas. Comprueba también una generación final y la apertura de un PDF histórico. No se regenera ningún PDF histórico automáticamente.

Para la prueba local del binario real en PowerShell: `$env:DOCUMENT_RENDERER_INTEGRATION='1'; php artisan test --filter=DocumentoRendererIntegrationTest`. Los recorridos `npm run test:e2e -- documentos.spec.js documentos-editor.spec.js` preparan exclusivamente la base SQLite E2E. Debes construir el frontend antes de ejecutarlos.

## Alcance diferido

No incluye tablas editables ni tabla dinámica de amortización, DOCX, firma electrónica, orientación horizontal, marcas de agua de imagen o una biblioteca general de documentos. Quill ofrece edición documental básica; no es un procesador Word completo. La amortización depende del modelo de solicitud/crédito y debe coordinarse con Backlog 04.

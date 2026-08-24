# ADR 0008: arquitectura de documentos y plantillas

- Estado: aceptado
- Fecha: 2026-08-23

## Contexto

Kronik necesita conservar documentos digitalizados y generar nuevos documentos desde contenido administrable. Un texto comercial puede evolucionar, pero un documento utilizado debe seguir explicando qué versión y datos lo originaron. La firma electrónica pertenece a un proceso distinto y todavía no forma parte del alcance.

## Decisión

Separar cuatro conceptos:

1. `documento_plantillas` identifica la definición comercial global por instalación.
2. `documento_plantilla_versiones` conserva contenido saneado, número, estado y hash. Solo un borrador no utilizado puede editarse.
3. `documentos_generados` conserva la versión exacta, contexto de negocio, snapshot cifrado de los valores efectivamente usados, metadatos de procedencia, hash y archivo privado.
4. Los documentos previamente digitalizados permanecen en el expediente actual; no se convierten artificialmente en documentos generados.

Las variables provienen de un catálogo en código con clave, etiqueta, tipo, origen, formato, obligatoriedad y conducta ante ausencia. No se admiten expresiones, ejecución de código ni acceso libre a propiedades. El HTML del editor se reduce a una lista permitida y cada sustitución se escapa como texto.

Las plantillas son globales para la instancia. El cliente y la sucursal son contexto operativo y de autorización, no propietarios de la plantilla. Una nueva redacción se crea duplicando una versión. Activar una versión retira la activa anterior; retirar no elimina historia.

La generación es idempotente y se ejecuta en cola. La generación de consentimientos SIC se vincula al cliente y la de garantías a `ClienteGarantia`. Las plantillas de contratos pueden editarse y previsualizarse, pero el documento final esperará la solicitud o crédito introducido por Backlog 04.

La firma queda detrás de la frontera del documento generado. Este backlog no agrega proveedor, evidencia, estado ni interfaz de firma.

## Consecuencias

- Los cambios históricos destructivos fallan también en el modelo, no solamente en la UI.
- Se duplica únicamente el conjunto mínimo de valores necesario para explicar el documento, cifrado en base de datos; el activity log no recibe estos valores.
- Las entidades vinculadas a documentos generados no pueden eliminarse sin preservar primero la trazabilidad.
- Agregar una variable requiere código y pruebas, lo cual evita capacidades implícitas o inseguras.

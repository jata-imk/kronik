# ADR 0015: paquete contractual QA inmutable por aprobación

- Estado: aceptado para P4a
- Fecha: 2026-09-27
- Precedentes: ADR 0008, 0009, 0013 y 0014.

## Contexto

La aprobación de originación conserva una revisión y sus evidencias. La generación
genérica de documentos no admite contratos y no debe convertirse en un camino
alternativo que evada esa aprobación. El simulador actual es anterior a impuestos:
las decisiones fiscales están acordadas, pero su configuración y cálculo todavía
no están implementados. Tampoco hay una autorización jurídica de plantillas.

## Decisión

P4a prepara **solo paquetes QA, sin validez contractual**, mediante una acción
contextual de la solicitud. No cambia su estado `aprobada`, no crea crédito ni
permite firma, desembolso o cobro. El gate `ORIGINACION_PAQUETES_QA_HABILITADOS`
queda cerrado por defecto, además de los controles existentes de originación.

`SolicitudPaquete` tiene una relación única con la resolución aprobatoria, revisión,
versión de plantilla y creador. Su snapshot cifrado conserva condiciones, producto,
simulación existente, HTML/presentación contractual y variables resueltas una vez.
La huella SHA-256 cubre ese snapshot y se verifica al renderizar. No se recalcula
la tabla ni se consultan datos nuevos del cliente para construir el PDF.

Preparar bloquea solicitud, cliente y versión de producto dentro de una transacción.
Reutiliza la evaluación de requisitos, contrastando también vigencia de resolución,
revisión, expediente y dictámenes con la evidencia aprobada. La separación dual se
comprueba respecto de **quien aprobó**, no de quien prepara documentos. La sucursal
activa se exige incluso al administrador global. El permiso específico
`prepare package solicitudes` se combina con lectura de clientes/solicitudes/documentos
y generación documental; aprobar no concede preparación implícitamente.

Un paquete por aprobación hace idempotente la repetición, incluso con otra clave
del navegador. Cambiar de plantilla requiere devolución, revisión y aprobación
nuevas. El historial no se sobrescribe. La unicidad está respaldada en base de datos;
la solicitud serializa las peticiones competidoras de la misma aprobación.

Se reutilizan `DocumentoGenerado`, su job, estados, visor privado y auditoría.
El contrato y un anexo construido por el servidor forman un PDF, con encabezado y
marca de agua QA en todas las páginas. El anexo identifica explícitamente
`fiscalidad=no_definida`: no significa impuesto cero ni exención. Esta modalidad
es preparatoria, no sustituye la implementación fiscal o los contratos aprobados.

El reintento conserva paquete, variables y documento y solo admite una generación
fallida sin hash de archivo, tras revalidar la aprobación. Un PDF original que ya
tuvo hash nunca se regenera, aunque se pierda su archivo: exige recuperación del
almacén desde respaldo. Plantillas posteriores no modifican sus bytes.

El paquete se navega desde la solicitud y sus archivos exigen acceso a ella, además
de permisos documentales. No se incorpora al listado genérico del expediente para
evitar presentar contratos QA como documentos operativos o revelar paquetes a
lectores que no pueden consultar solicitudes. El enlace al expediente se conserva.

## Consecuencias y límites

- Nuevo almacenamiento aditivo; no se modifica el versionado financiero existente.
- El rollback con paquetes existentes se bloquea para no dejar documentos huérfanos.
  Para recuperar una instalación, restaurar base y archivos coherentemente.
- Los historiales siguen disponibles aunque una aprobación venza o la solicitud
  se devuelva; disponer del PDF no acredita vigencia ni autoriza usarlo.
- Preparar no concede lectura de fundamentos reservados de Evaluación/PLD.
- Pendiente P4b: recepción/validación de firma, política fiscal aplicada a condiciones
  y tabla, y distinción de contenido institucional aprobado. No promover un paquete
  QA a contrato válido mediante un simple cambio de etiqueta o bandera.
- P5 y P6 deberán revalidar sus propias puertas. P4a no habilita dinero real, SIC
  productivo, mora ni pagos retroactivos.

## Alternativas descartadas

Generar un contrato directamente desde el expediente omitiría la aprobación.
Regenerar desde datos vivos rompería la trazabilidad. Asumir IVA cero para usar el
simulador actual como tabla contractual contradiría las decisiones de producto.

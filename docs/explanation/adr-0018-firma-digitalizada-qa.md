# ADR 0018: recepción y revisión humana de firma digitalizada QA

- Estado: aceptado para P4b QA.
- Fecha: 2026-09-29.
- Precedentes: ADR 0010, 0014, 0015 y 0017.

## Decisión

La interfaz llama al paquete **Contrato y tabla de pagos**. Conserva el nombre
técnico y sus rutas para no romper referencias. Original generado, copia firmada
recibida y firma aceptada son hechos distintos. No hay firma electrónica ni
autenticación automática; el responsable compara contenido, páginas, identidad,
condiciones y firmas. Todo permanece expresamente QA sin validez contractual.

`SolicitudFirma` conserva cada copia PDF privada y su hash, fecha declarada de
firma, fecha real de recepción, actor y huella del original. Una revisión terminal
acepta o rechaza; el motivo de rechazo es obligatorio y cifrado. No sobrescribir
archivos ni permitir borrado. Tras rechazo se recibe otra copia. No aceptar el
PDF original sin cambios como si fuera una copia firmada. Esto detecta una
confusión, no acredita autenticidad de una firma.

`SolicitudFormalizacion` es un registro inmutable de la aceptación, ligado al
contrato, revisión, resolución y hashes originales/firmados. Su snapshot cifrado
referencia condiciones ya congeladas, sin recalcularlas. Aceptar transiciona a
`formalizada` (etiqueta «Formalizada · QA»); recibir o rechazar no lo hace.
La formalización no crea crédito, transferencia, saldo ni pago.

## Controles

- Puerta explícita `ORIGINACION_FIRMAS_QA_HABILITADAS=false` por defecto.
- Permisos independientes `receive signature solicitudes` y
  `review signature solicitudes`, además de lectura de solicitud, cliente y
  documentos. Descarga exige su permiso documental. Sin asignaciones implícitas.
- Mantener separación de aprobación individual/dual ya configurada. No imponer
  una nueva separación entre receptor/revisor de firma sin decisión de producto.
- Incluso Super Admin debe seleccionar la sucursal activa responsable.
- Transacciones y bloqueo solicitud → cliente → producto → firma; idempotencia
  por UUID del envío y revisión terminal repetida con mismos actor/datos.
- Recepción/aceptación revalidan aprobación, revisión, evidencias y vigencia;
  solo contratos formato 2 con proyección fiscal. Rechazo sigue permitido si
  caducó la evidencia, siempre en aprobación actual y con versión de fila vigente.
- Verificar hash del original y de la copia antes de aceptar. Archivo ausente o
  alterado requiere recuperación, nunca reconstrucción silenciosa.
- PDF con extensión, MIME y cabecera verificados, límite documental existente,
  nombre interno seguro y respuestas privadas/no-store con visor existente.
  Estos controles no equivalen a un análisis antivirus ni a validación jurídica.
- Fecha de firma entre generación del original y hoy en zona de la institución.
  Se distinguen fecha efectiva, recepción y revisión; no habilita desembolsos pasados.
- Devolver/cancelar antes del desembolso conserva formalización y firmas como
  historia. Una nueva aprobación exige nuevo contrato y nueva firma; P5 deberá
  comprobar que la formalización corresponde a la aprobación vigente.
- Timeline registra identificadores y actores, nunca archivos ni motivos en logs
  técnicos. Historial de firmas paginado y accesos sujetos a la solicitud.

## Límites

No promover paquetes P4a, firmar digitalmente, certificar identidad, habilitar
operación real ni registrar movimientos monetarios. Las reglas fiscales de mora,
pagos parciales y causación siguen en sus incrementos. La aceptación de QA no
sustituye aprobación institucional del contrato o revisión contable/jurídica.
P5 agrega desembolso único manual y P6 primer pago con sus propias puertas.

Migración aditiva sin backfill ni borrado; rollback bloqueado si hay firmas.
Restaurar base y almacén coherentes si se necesita recuperación.

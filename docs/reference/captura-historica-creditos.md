# Fechas efectivas y captura histórica de créditos

## Contrato QA

Se conservan las puertas existentes de aprobaciones, paquetes, firmas, desembolsos, pagos y anticipos. Vigencia pasada exige ORIGINACION_DESEMBOLSOS_QA_HABILITADOS y confirmación histórica al activar un borrador; activada_en conserva el instante real. Una versión congelada no se edita. Activarla retira la activa anterior ahora; no reconstruye un catálogo temporal ni modifica créditos anteriores.

La solicitud selecciona ID exacto de versión activa/disponible; su fecha efectiva debe respetar vigente_desde. Fecha pasada requiere habilitación QA. La simulación es informativa y no acredita autorización para originar. Aprobación, política y expediente se revalidan en el momento real. No antedatar firmas ni generación del PDF.

El desembolso conserva fecha efectiva igual a la tabla firmada, pasada o actual, nunca futura. Registrar historia exige confirmacion_historica; la referencia y payload conservan idempotencia. No ejecuta transferencia. Cronograma v1 se copia sin recalcular. Una fecha distinta requiere nueva revisión/aprobación/formalización, conservando evidencia previa.

Pagos históricos usan la previa y motor existentes. La consulta a hoy no escribe ni impide un pago anterior admisible. Se rechazan pagos anteriores al desembolso o a movimientos con fecha posterior; misma fecha puede admitir varias operaciones secuenciales. Anticipos/reversos mantienen sus contratos y permisos. Conservar el límite de parcialidades en gracia B+sustitución.

## Estado operativo de captura

credito_capturas_historicas identifica un crédito de forma única y conserva iniciador/equipo/sucursal originales, captura real y cierre/actor/equipo reales. No cambia estado financiero ni saldos. Ausencia significa no_aplica; registro sin cierre, pendiente; con cierre, completa. No se rellenan créditos previos.

La lectura mediante CapturaHistoricaService::resumen expone estado, iniciada_en y completada_en, sin condiciones ni referencias. Credito::conHistoriaCompleta permite excluir pendientes mediante consulta relacional; permiteAvisosFinancieros comprueba de nuevo BD, sin usar relaciones cacheadas. No concede permisos ni habilita canales: comunicaciones aplica sus demás condiciones y revalida al ejecutar. No utilizar la bitácora de auditoría como cola de mensajes.

POST /creditos/{credito}/captura-historica/completar requiere confirmacion_qa y confirmacion_historial_completo, permiso pay creditos, modo/puerta de pagos QA y sucursal activa responsable. El servicio bloquea crédito/captura, verifica evidencia y cierra una sola vez. Puede cerrar sin pagos o con mora; no exige último pago hoy ni saldo cero. Reintentos no duplican actividad. No ofrece reapertura, reembolso ni envío automático. Consultar no crea este recurso.

## Auditoría, migración y capacidad

Inicio: credito.disbursed con estado captura_historica_pendiente y related al crédito; cierre: credito.historical_capture_completed, sujeto crédito y related al control operativo. Fecha efectiva permanece en evidencia protegida, no se agrega a metadatos generales. Reintentos emiten una vez; fallo de auditoría revierte operación. No registrar importes, referencias, contenido documental ni motivos reservados en actividad.

Migración 2026_11_11_100000 crea únicamente control operativo; unique/FK por crédito y rollback protegido. Sin backfill, permisos nuevos, seeders operativos o variables nuevas. El estado se carga en lote en listados; no reconstruir toda la cartera ni ejecutar consultas por día. El avance financiero sigue en memoria desde la última evidencia. No acredita capacidad de 10 mil créditos ni reemplaza las tareas de proyecciones/medición.

Decisión: [ADR 0045](../explanation/adr-0045-fechas-efectivas-captura-historica.md). Estado: [Agent Note](../agent-notes/desembolsos-historicos.md). Casos financieros: [Bullet](bullet-casos-definicion.md).

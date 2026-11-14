# Fechas efectivas y captura histórica de créditos

## Contrato QA

### Responsabilidades en este recorrido

Se aplica el [roadmap del core](https://app.notion.com/p/40961db7db7f819a8af1d7c3cfec2df7) y ADR 0032. Esta revisión acotada no certifica que las áreas futuras estén completas.

| Área | Recorrido vigente y límite del cambio |
| --- | --- |
| Producto y LOS | ProductoVersionService congela condiciones; SolicitudService y SolicitudRequisitosService validan versión, fecha y requisitos. Evaluación, PLD y aprobación siguen ocurriendo en el momento real. FechaOriginacion concentra la regla temporal compartida. |
| Gestor documental | SolicitudPaqueteService y SolicitudFirmaService conservan versión, tabla, PDF y firma; desembolso verifica esa evidencia. Ninguna corrección silenciosa del contrato. |
| LMS/core | DesembolsoService registra evidencia; CalendarioCreditoSimple, PagoService y MotorAnticipos reutilizan motores/condiciones históricas. CapturaHistoricaService coordina un control operativo, sin cambiar fórmulas. |
| Cobranza/comunicaciones | Consume saldo y elegibilidad de historia completa; no calcula mora ni ejecuta transferencia. No hay emisor de avisos de atraso en esta base. La tarea de canales debe revalidar la elegibilidad al programar y ejecutar. |
| Riesgos y PLD/FT | Mantienen sus dictámenes, evidencias y permisos actuales; una fecha efectiva pasada no reconstruye ni elude las revisiones de hoy. |
| Contabilidad y cumplimiento | No se generan pólizas, conciliaciones ni reportes regulatorios nuevos. Auditoría técnica conserva actor/contexto reales y referencias protegidas; no sustituye esos módulos. |

Los controladores y Form Requests autorizan/validan; la UI consume capacidades del servidor. El motor no depende de Vue, documentos, canales ni equipo. Los equipos siguen siendo contextos de permisos de una financiera por instalación, no tenants.

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

## Recorrido UI y pruebas

[Guía reproducible QA](../how-to/qa-desembolsos-historicos.md): ambas políticas, pagos/anticipos cronológicos, oráculo independiente en centavos, reverso, conservación de PDF y cierre. La UI convierte la selección de DatePicker a fecha civil antes del envío; no serializa el día como instante UTC. El harness histórico crea BD nueva, usa reloj real y rechaza activar simultáneamente el reloj Bullet. El modo E2E_FRESH_DATABASE permite la misma protección en las regresiones aisladas; no es configuración operativa.

# ADR 0045: fechas efectivas y captura histórica QA

- Estado: implementación en curso; sin integración ni despliegue.
- Fecha: 2026-11-11.
- Fuente: [tarea autorizada](https://app.notion.com/p/41361db7db7f81f5ad52d7df919dee99).
- Sustituye únicamente la prohibición de fecha pasada del [ADR 0019](adr-0019-desembolso-manual-qa.md). Conserva ADR 0020, 0023 y 0032.

## Contexto y decisión

Reconstruir un caso sintético desde un desembolso pasado requiere admitir esa fecha en todo el recorrido. El usuario eligió permitir activar un borrador QA con vigencia pasada, conservando activación real. No equivale a afirmar que la configuración o una firma existían entonces.

Mantener monolito modular: Producto/LOS comprueban vigencia y autorización; el gestor documental conserva tabla/firma; aplicación registra operaciones con bloqueo, previa e idempotencia; los motores reciben condiciones y fechas explícitas. Comunicaciones y reportes consumen resultados y estado de captura, sin imponer canales ni proyecciones al cálculo. Los equipos son contextos de permisos de una financiera por instalación.

Fecha efectiva no es instante de registro. No modificar timestamps, reloj, documentos, snapshots ni versiones utilizadas. La solicitud identifica una versión activa concreta; la fecha no puede preceder su vigencia. Aprobación, política, expediente y permisos se comprueban al realizar la acción. Firma/generación mantienen fechas reales. Cambiar fecha firmada exige devolver, revisar y formalizar otra vez.

## Captura incompleta y límites

Crear control operativo separado del libro en la transacción del desembolso pasado. Completarse es una declaración explícita del operador con permiso de pago, no liquidación ni nuevo movimiento monetario. Sin cierre automático, reapertura o edición de historia. No reinterpretar registros anteriores sin este control.

Capturas pendientes no son elegibles para avisos financieros basados en historia completa. El consumidor debe revalidar al programar y ejecutar, además de sus permisos, consentimiento, saldo y vigencia propios. Cerrar no envía avisos acumulados. No hay emisores de atraso en la base revisada; la integración de canales se conserva en su tarea.

No crear otro motor, importar cartera, habilitar dinero real, alterar restricciones de gracia B+sustitución ni recalcular movimientos posteriores. Reversos compensatorios conservan su alcance actual.

## Consecuencias y verificación

Migración aditiva sin backfill ni reescritura de evidencia. El rollback rechaza borrar capturas existentes. Auditoría del inicio cubierta por desembolso; cierre con evento propio único y contexto real. Consultar/cotizar no emite actividad adicional. Pruebas de recorrido histórico, cálculo independiente, límites temporales, permisos, idempotencia, auditoría y rollback; concurrencia MariaDB y UI completan la evidencia de cada PR.

Contrato en [captura histórica](../reference/captura-historica-creditos.md), evidencia en [Agent Note](../agent-notes/desembolsos-historicos.md).

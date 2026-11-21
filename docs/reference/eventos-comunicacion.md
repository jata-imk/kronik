# Eventos candidatos de comunicación

La [nota de requisitos](comunicaciones-por-canales-envios-y-seguimiento.md) permanece como copia literal. Este contrato describe los eventos de negocio; el [controlador de canales](canales-comunicacion.md) es una capa separada.

## Eventos iniciales

El usuario eligió **pago recibido** y **próximo vencimiento**. La anticipación del recordatorio se configura por regla y canal; tres días es solo el valor inicial. `communication_events` conserva una ocurrencia de negocio con crédito, cliente, equipo estable y sucursal de origen; no guarda contacto, texto, canal ni estado de entrega. `event_key` es único: `payment:{pago_id}` o `due:{credito_id}:{fecha_vencimiento}`. Un evento cancelado conserva su historia y no se reactiva automáticamente.

`PagoService::registrar` prepara `payment_received` tras sincronizar la lectura financiera, dentro de la misma transacción. Reintentar el pago no crea otra ocurrencia. `PagoService::reversar` cancela el evento original, también dentro de la transacción. No se afirma que un comprobante esté disponible ni que se haya enviado.

`comunicaciones:preparar-vencimientos` revisa la lectura del día institucional con la frecuencia configurada. Prepara `upcoming_due` para cada anticipación distinta de las reglas activas del equipo; si aún no hay reglas, usa el valor inicial configurable para conservar candidatos sin habilitar envíos. Exige crédito activo, lectura de la versión actual del proyector, sin liquidación ni atraso, y obligación **programada** positiva en la fecha correspondiente. No calcula saldos propios ni toma un corte faltante como cero. Una lectura faltante o no disponible deja el candidato futuro sin cambios hasta verificar la fuente. La pasada cancela candidatos vencidos o cuya obligación ya no conste en una lectura vigente; pagos y reversos reconcilian el crédito en su transacción. El despacho comprueba de nuevo que cada canal esté exactamente en su día configurado.

Los créditos nuevos conservan `team_id` desde el desembolso. La migración lo recupera para históricos solamente cuando el evento auditado de desembolso tiene un equipo único; el resto no habilita salida externa. El evento no selecciona destinatario ni canal. El ejecutor vuelve a verificar lectura, consentimiento, contacto y conexión en el momento del despacho. La aceptación técnica del proveedor no se interpreta como entrega o lectura.

## Auditoría y operación

Cada creación/cancelación escribe `communication.event_prepared` o `communication.event_cancelled` con el evento como sujeto, crédito relacionado y equipo/sucursal de origen. El actor es el sistema (también si lo desencadena una operación de pago); el pago conserva por separado su actor en `credito.payment_recorded` o `credito.payment_reversed`. No se hereda una sesión ni se atribuye equipo incierto. Un fallo de auditoría revierte la transacción financiera.

Migración del evento: `2026_11_14_190000_create_communication_events_table.php`; migración de anticipación: `2026_11_20_100300_add_lead_days_to_communication_rules.php`. Comando manual: `php artisan comunicaciones:preparar-vencimientos`. La programación requiere que el scheduler del entorno esté ejecutándose; registrar el comando no acredita su ejecución en otro servidor.

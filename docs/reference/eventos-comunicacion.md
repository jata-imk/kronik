# Eventos candidatos de comunicación

La [nota de requisitos](comunicaciones-por-canales-envios-y-seguimiento.md) permanece como copia literal. Este contrato describe el primer tramo implementado, sin salida a proveedores.

## Eventos iniciales

El usuario eligió **pago recibido** y **próximo vencimiento**, con recordatorio **tres días antes**. `communication_events` conserva una ocurrencia de negocio con crédito, cliente y sucursal de origen; no guarda contacto, texto, canal, equipo supuesto ni estado de entrega. `event_key` es único: `payment:{pago_id}` o `due:{credito_id}:{fecha_vencimiento}`. Un evento cancelado conserva su historia y no se reactiva automáticamente.

`PagoService::registrar` prepara `payment_received` tras sincronizar la lectura financiera, dentro de la misma transacción. Reintentar el pago no crea otra ocurrencia. `PagoService::reversar` cancela el evento original, también dentro de la transacción. No se afirma que un comprobante esté disponible ni que se haya enviado.

`comunicaciones:preparar-vencimientos` revisa cada hora la lectura del día institucional. Solo prepara `upcoming_due` cuando el crédito está activo, la lectura corresponde a la versión actual del proyector, no está liquidada ni no disponible, no registra atraso y contiene una obligación **programada** positiva con vencimiento exactamente tres días después. No calcula saldos propios ni toma un corte faltante como cero. Una lectura faltante o no disponible deja el candidato futuro sin cambios hasta verificar la fuente. La pasada cancela candidatos con fecha vencida o cuya obligación ya no conste en una lectura vigente; pagos y reversos reconcilian el crédito en su transacción. El comando no crea cola, correo ni envío.

El crédito aún carece de un identificador estable de equipo/prestamista. Por ello el evento de negocio no asigna equipo ni selecciona destinatario o canal. Antes de habilitar cualquier envío deben resolverse esa propiedad del crédito, consentimiento vigente por cliente/prestamista/canal/finalidad, contacto, proveedor, reglas y plantillas. El ejecutor deberá verificar de nuevo la lectura y el consentimiento en el momento del despacho. La aceptación técnica del proveedor no se interpretará como entrega o lectura.

## Auditoría y operación

Cada creación/cancelación escribe `communication.event_prepared` o `communication.event_cancelled` con el evento como sujeto, crédito relacionado y sucursal de origen. El actor es el sistema (también si lo desencadena una operación de pago); el pago conserva por separado su actor en `credito.payment_recorded` o `credito.payment_reversed`. No se hereda una sesión ni se atribuye equipo incierto. Un fallo de auditoría revierte la transacción financiera.

Migración: `2026_11_14_190000_create_communication_events_table.php`. Comando manual: `php artisan comunicaciones:preparar-vencimientos`. La programación horaria requiere que el scheduler del entorno esté ejecutándose; registrar el comando no acredita su ejecución en otro servidor.

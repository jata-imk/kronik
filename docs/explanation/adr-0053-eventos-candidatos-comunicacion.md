# ADR 0053: eventos candidatos antes de seleccionar canal

Estado: aceptado para implementación local, 2026-11-14.

## Contexto

El requisito de comunicaciones exige distinguir evento de negocio, notificación, destinatario, canal y proveedor. El usuario eligió pago recibido y próximo vencimiento tres días antes. Las lecturas financieras vigentes ya ofrecen obligaciones programadas, mientras que los créditos no conservan un equipo de origen estable. Tampoco hay consentimiento ni proveedor aprobados para enviar mensajes.

## Decisión

Persistir primero ocurrencias de negocio idempotentes y cancelables, ligadas al crédito/cliente/sucursal. Preparar el recordatorio desde la lectura financiera del día y registrar los cambios mediante la auditoría existente. Mantener esta capa sin canal, contenido, contacto, equipo inferido, cola ni transporte. El envío será otra etapa que deberá resolver el prestamista y revalidar elegibilidad, consentimiento y configuración en el momento de ejecutar.

## Consecuencias

Los pagos QA y la tarea horaria pueden producir eventos candidatos verificables sin alcanzar a clientes. Un reverso o cambio de calendario puede cancelar el candidato; la evidencia cancelada sigue disponible. La distribución multicanal, el seguimiento de intentos y la operación productiva siguen pendientes. Véase el [contrato de eventos](../reference/eventos-comunicacion.md).

## Renumeración de integración — 27/11/2026

Originalmente ADR 0048 en la rama de Comunicaciones. Se renumera a 0053
para conservar el ADR 0048 independiente de main, sin cambiar esta decisión
ni su contexto histórico.

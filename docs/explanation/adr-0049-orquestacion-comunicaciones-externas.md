# ADR 0049: orquestación de comunicaciones externas

Estado: aceptado para implementación local, 2026-11-20.

## Contexto

La nota de producto requiere correo, SMS y WhatsApp, consentimiento por prestamista y finalidad, salida idempotente y seguimiento honesto. Los créditos anteriores no guardaban un equipo estable y los proveedores requieren elegibilidad y credenciales externas. Los avisos internos y eventos candidatos ya existen. La decisión de [ADR 0048](adr-0048-eventos-candidatos-comunicacion.md) conserva estos eventos separados de los transportes.

## Decisión

Persistir el equipo de origen en créditos nuevos; recuperar el histórico solo desde evidencia única de auditoría y excluir el ambiguo. Separar evento, consentimiento, canal, regla/version de texto y salida. Programar salidas con un comando periódico que lee únicamente eventos ya confirmados; revalidar inmediatamente antes del transporte. Desactivar todo envío por configuración de entorno nueva y por equipo/canal/regla. Usar un adaptador por canal: Laravel Mail/SMTP, Infobip SMS v3 y Meta Cloud API. No hay fallback automático entre canales.

La anticipación de vencimiento pertenece a cada regla y canal; el valor inicial del entorno es tres días, configurable, sin fijarlo en el cálculo financiero. La creación del candidato considera cada anticipación activa y el job solo puede enviar el día que indique la regla vigente. Zona horaria, ventana y frecuencia diaria se editan en la misma regla. Caducidad del pago, recuperación de cola y cadencia del programador se parametrizan en el entorno porque son límites operativos de la instancia.

Un estado `sending` persiste antes de contactar al proveedor. Una interrupción o timeout se marca `uncertain`; no se reenvía sin conciliación. Los IDs de proveedor y webhooks firmados acreditan estados posteriores. Los webhooks adelantados se almacenan para correlación, y las transiciones no retroceden de leído/entregado por eventos atrasados. Los cambios de conexión, regla, consentimiento y estado de salida usan el contrato de auditoría sin datos de contacto ni secretos.

## Consecuencias

El código permite configurar y probar en QA sin generar tráfico por defecto. SMTP no suministra por sí mismo evidencia de entrega o rebote; permanece `accepted` hasta integrar un proveedor de eventos. SMS y WhatsApp requieren aprobación real de caso de uso, cuenta, remitente, plantilla y webhooks; la interfaz no sustituye esos procesos. El único destinatario de prueba real autorizado hasta ahora es el correo personal dado por el usuario, pero el entorno local usa `MAIL_MAILER=log`, así que no se ha enviado un mensaje real. Telegram sigue opcional. Ver [contrato operativo](../reference/canales-comunicacion.md).

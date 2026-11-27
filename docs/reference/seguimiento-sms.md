# Seguimiento interno SMS LabsMobile (I2)

Estado al 25/11/2026: núcleo interno implementado en la rama de I2, sin recepción
pública. La [Agent Note](../agent-notes/comunicaciones-notificaciones.md) registra
PR, SHA y validaciones. El usuario confirmó que aún no ha creado cuenta ni comprado
saldo. No existe evidencia de autenticación de callbacks para su cuenta.

## Frontera de confianza

`LabsMobileAcknowledgement::fromPayload()` valida y normaliza datos; **no autentica**.
`LabsMobileAcknowledgementService::record()` es una entrada de dominio interna,
llamada solo por pruebas sintéticas. No hay controlador, comando de importación,
job de recepción, verificador provisional ni ruta LabsMobile. Las rutas SMS retiradas
responden 404. `delivery_receipts` y `read_receipts` siguen en false y el transporte
no incorpora `ackurl`. La correlación, HTTPS y un payload válido no autentican origen.

El orquestador solo invoca `reconcile()` tras finalizar su HTTP, sobre evidencia ya
persistida internamente. Conectar cualquier entrada externa requiere primero contrato
y verificación de autenticidad efectivos; no basta añadir un booleano de confianza.
No se implementa consulta de estado, HMAC o garantía idempotente no documentados.

## Normalización y estados

Se admiten identificadores propios hexadecimales de 20 caracteres, teléfono E.164
sin `+`, fecha estricta `YYYY-MM-DD HH:MM:SS` UTC y tipo SMS (o campo omitido).
RCS, READ, niveles desconocidos, causas libres y combinaciones contradictorias no
se convierten en evidencia. El teléfono solo se utiliza transitoriamente para
comparar el hash del destinatario original; no se persiste en acuses ni auditoría.

| Acuse | Resultado | Interpretación interna |
| --- | --- | --- |
| gateway | ok | Acuse intermedio; como máximo aceptado. |
| operator | ok | Operador responsable; como máximo aceptado. |
| handset | ok | Entregado al dispositivo; nunca leído. |
| error | ko | Fallido con código conocido o sin descripción. |

La [API oficial](https://www.labsmobile.com/es/api-sms/versiones-api/http-rest-post-json)
documenta GET hacia `ackurl`, identifica operador y dispositivo y reserva READ para
RCS. La autenticación Basic de las llamadas a su API no autentica esos GET de retorno.
No se halló contrato verificable de firma ni consulta individual autenticada en lo
examinado el 25/11/2026. El nivel gateway aparece en el formato público: se trata
solo como información intermedia, sin atribuirle garantía de ruta o de entrega.

## Persistencia, conciliación y auditoría

La migración aditiva `2026_11_25_180000` extiende `communication_webhook_events`
con `provider_correlation_id`, `ack_level`, `provider_status`, `provider_code`,
`occurred_at` y `applied_at`, todos nullable. Conserva `received_at` y las filas Meta,
sin backfill. No crea tablas: se mantiene la clasificación existente de reinicio QA.

Solo una salida SMS LabsMobile, ya intentada y con correlación propia, puede recibir
evidencia. El equipo debe coincidir con su evento original; el teléfono debe coincidir
con `recipient_hash` y la fecha no puede anteceder al intento. Los IDs desconocidos,
otros proveedores/canales y salidas preparadas, en cola o canceladas se descartan.
No se recupera equipo desde un parámetro externo o las preferencias actuales.

La clave única del evento normalizado deduplica proveedor, correlación, nivel,
resultado, código y hora UTC. Un lock de la salida serializa recepción y finalización.
Acuse, aplicación, transición y actividad se confirman en la misma transacción;
si falla auditoría, todo revierte. Los acuses desordenados quedan como evidencia,
sin degradar una entrega; handset puede resolver un fallo anterior. `applied_at`
evita volver a aplicar efectos. Repetir un nivel ya conocido no duplica actividad.

Los niveles intermedios no crean `accepted_at`: esa fecha corresponde exclusivamente
a aceptación HTTP comprobada. `delivered_at` usa la fecha UTC del proveedor.
La respuesta HTTP posterior conserva `provider_message_id` y `accepted_at` aunque
un acuse adelantado ya haya avanzado el estado. Un timeout conserva ese estado y
la correlación sin inventar el ID aceptado ni reenviar. Un rechazo HTTP definitivo
supera un acuse intermedio, pero no una entrega handset. `read_at` nunca se escribe.

Los avances efectivos reutilizan `CommunicationDeliveryStatusUpdated` y
`ActivityLogService`, con actor sistema, sujeto salida, equipo/sucursal originales,
`inheritActorContext: false` y metadatos `state`, `provider`, `result` (nivel).
No se registran teléfono, payload, credenciales ni texto. Duplicados y eventos que
no avanzan evidencia no crean actividad adicional. Ver [matriz](auditoria-eventos.md).

## Pendiente externo y consulta preparada

No hace falta comprar saldo para desarrollar o probar este núcleo. Antes de habilitar
recepción, el usuario debe obtener evidencia técnica de LabsMobile. Consulta propuesta,
no enviada a soporte:

> ¿Cómo podemos autenticar sus GET de estados hacia nuestro webhook? Necesitamos
> especificación de firma o credencial verificable, o IP/CIDR de salida oficiales
> con política de cambios y tratamiento de proxies. ¿Qué opciones soporta nuestra
> cuenta, cómo se configuran y cómo se prueban sin SMS reales? Confirmen campos,
> niveles por ruta mexicana, reintentos (el texto enumera cinco pero lista cuatro
> intervalos), recuperación de eventos perdidos y consulta autenticada individual,
> si existe. ¿Qué garantías ofrece subid ante timeout o repetición?

Crear una cuenta permite revisar su configuración; no acredita autenticidad por sí
solo. Cuenta/ruta, contratación, recepción externa y piloto continúan pendientes.
No se contactó al proveedor. Correo y Meta conservan sus contratos; editor e
Integraciones no forman parte de I2. El [plan](../explanation/plan-incrementos-sms.md)
mantiene el barrido acumulado contra `ecb9d4a` para I4.

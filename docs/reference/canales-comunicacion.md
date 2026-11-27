# Canales externos de comunicación

I1 sustituye directamente Infobip por LabsMobile en desarrollo/QA, conforme al
[ADR 0050](../explanation/adr-0050-proveedor-sms-labsmobile.md). No existe migración
histórica ni convivencia de proveedores. I2 implementa el [núcleo interno de seguimiento](seguimiento-sms.md)
con pruebas sintéticas; recepción autenticada externa pendiente. I3 incorpora el [catálogo global de Notificaciones](plantillas-notificaciones.md) para SMS y correo.
I4 corrige los hallazgos del barrido conjunto; su evidencia e integración constan
en la [Agent Note](../agent-notes/comunicaciones-notificaciones.md). El piloto externo
sigue pendiente. Esta implementación no habilita tráfico.

La [nota original de producto](comunicaciones-por-canales-envios-y-seguimiento.md) fija el alcance. Este contrato describe el código de la rama de comunicaciones. La salida externa permanece desactivada hasta configurar `COMMUNICATIONS_SEND_ENABLED=true` en un entorno autorizado, con proveedor, consentimiento y reglas aprobados para cada equipo. La configuración y las pruebas locales no acreditan entrega real ni despliegue.

## Propiedad, consentimiento y permisos

Los créditos nuevos conservan `team_id` del desembolso. La migración recupera créditos históricos solo cuando la auditoría `credito.disbursed` identifica un único equipo. Los créditos ambiguos mantienen `team_id=null` y no pueden generar envíos externos. `communication_events` copia ese contexto estable. El operador debe tener permisos `read comunicaciones`, `manage comunicaciones` o `send test comunicaciones` en el equipo activo según la acción. Las vistas filtran además por la sucursal activa. El equipo del crédito fija el contexto original del aviso. Una instalación representa una financiera; sus equipos son departamentos o contextos de autorización.

`communication_consents` guarda cliente, equipo, canal, finalidad (`operational` o `preventive`), fecha, origen, referencia a evidencia protegida y hash del contacto autorizado. Registrar consentimiento exige permiso de gestión y edición del cliente en su sucursal. La evidencia permanece en su repositorio protegido y no se copia a esta tabla; el operador registra una referencia verificable. La baja revoca autorizaciones y cancela salidas preparadas. Cambiar correo o teléfono invalida el hash. Nunca se utilizan referencias ni terceros.

La ficha de consulta del cliente muestra el estado de sus consentimientos solo a quien puede leer comunicaciones en el equipo activo; enlaza al formulario de gestión con ese cliente preseleccionado cuando también puede editarlo. La presencia de correo o teléfono en la ficha no significa que estén verificados. No se añade una marca de verificación que los datos actuales no pueden acreditar.

Ficha e historial de consentimientos reciben `effective_status`: `current`,
`contact_changed` o `revoked`. Backend compara el contacto actual normalizado con
el hash autorizado; un contacto ausente o cambiado muestra «Contacto cambiado;
requiere nueva autorización». La consulta no revoca ni recrea consentimientos,
no expone hashes/evidencia y no añade eventos de auditoría por calcular el estado.

## Matriz inicial

| Evento | Finalidad | Disparador | Destinatario | Canales configurables |
| --- | --- | --- | --- | --- |
| `payment_received` | `operational` | Pago QA confirmado y no reversado | Cliente titular del crédito | Correo, SMS, WhatsApp |
| `upcoming_due` | `preventive` | Obligación programada positiva, con anticipación configurada por canal, sin atraso y con lectura financiera vigente | Cliente titular del crédito | Correo, SMS, WhatsApp |

No existen reglas activas por defecto. El comando `comunicaciones:preparar-vencimientos` prepara candidatos horarios. `comunicaciones:despachar` corre cada cinco minutos, prepara una salida única por evento y canal, y encola un job después del commit. El job revalida todo antes de contactar al proveedor dentro de la ventana configurada. Si `COMMUNICATIONS_SEND_ENABLED=false`, no encola ni contacta a ningún proveedor. SMS exige además `COMMUNICATIONS_SMS_SEND_ENABLED=true`; ambas barreras están desactivadas por defecto y se comprueban de nuevo en el job. Ambos comandos requieren que el programador Laravel esté ejecutándose realmente en el entorno y el transporte `database` requiere un worker activo; `schedule:list` solo comprueba el registro de tareas.

Cada regla incluye anticipación en días para vencimientos, zona horaria, ventana diaria, límite por cliente compartido entre canales, selección de plantilla global y versión. El valor inicial de anticipación es tres días y el operador autorizado puede elegir de 1 a 30 días por canal; el máximo es configurable. El candidato se crea en cada día correspondiente y el job confirma de nuevo la regla vigente. Variables locales permitidas: `{nombre}` y `{fecha_vencimiento}`. Se usa texto sin enlaces a un portal de cliente. En SMS/correo, el texto se administra en el catálogo global y cada salida referencia su versión exacta. Sustituir o retirar redacción cancela pendientes sin recrearlos. WhatsApp conserva su texto local y contador de versión. Cambiar la anticipación también impide el envío fuera del nuevo día elegido. No hay reintento automático de una salida incierta. `communication_deliveries` conserva preparado, en proceso, aceptado por proveedor, entregado, leído, fallido, cancelado o incierto. Solo un recibo autenticado del proveedor eleva el estado a entregado o leído. Los webhooks Meta duplicados se deduplican y los adelantados se concilian al conocer el identificador del mensaje. SMS conserva aceptación o resultado fallido/incierto en su flujo externo; no recibe callbacks. I2 añade conciliación interna de evidencia sintética, con niveles separados y sin lectura SMS. Correo admite texto o HTML saneado según la versión seleccionada. Correo SMTP solo acredita aceptación del servidor; no incorpora rebotes ni lectura sin un proveedor de eventos de correo adicional.

La lista de reglas muestra anticipación y horario a quienes pueden consultar comunicaciones. Cambiar la anticipación deja una salida aún no intentada en estado preparado hasta el nuevo día configurado; una salida que ya llegó al proveedor no se duplica. El historial de auditoría conserva cada transición.

I4 coordina el inicio del intento con pagos/reversos mediante el bloqueo del
crédito antes de plantilla, regla, cliente y salida. Las lecturas financieras se
realizan después de adquirirlo, sin reutilizar un snapshot anterior a la espera.
El bloqueo del cliente y la lectura actual de los intentos serializan el límite
diario entre canales y créditos del mismo titular. Los límites del día de la regla
se expresan en la zona de almacenamiento de la aplicación, también en madrugada.
La reserva del intento y su auditoría siguen en la misma transacción; no se
mantiene esa transacción abierta durante el HTTP/SMTP.

## Conexiones

- **Correo:** usa el transporte Laravel `MAIL_*` del entorno y un remitente por equipo. No introducir credenciales SMTP en la UI. Probar únicamente con el destinatario autorizado en `COMMUNICATIONS_TEST_RECIPIENT`; el endpoint rechaza transportes `log` y `array`. Confirmar dominio, SPF, DKIM, DMARC y política del proveedor antes de activar. El botón de prueba informa aceptación técnica, no recepción.
- **SMS:** adaptador HTTP LabsMobile, configuración global de instalación mediante `config()` y variables de entorno. La UI solo muestra presencia de conexión/remitente y permite decisiones operativas por equipo; rechaza secretos y remitente técnico enviados por formulario. Ver [variables](variables-entorno.md). Usa Basic Auth, `POST /json/send`, un destinatario E.164 sin `+`, texto renderizado y `subid` aleatorio propio de 20 caracteres. No añade el antiguo `1` al prefijo mexicano 52. No envía callback ni usa SDK adicional. Solo `code=0` junto con el `subid` concordante acredita aceptación; un HTTP 200 sin esos datos no basta. Timeout, 408/429, 5xx, redirección o respuesta malformada son inciertos; un rechazo explícito es fallido. Sin reintento HTTP automático. [Contrato API](https://www.labsmobile.com/es/api-sms/versiones-api/http-rest-post-json).
- **WhatsApp:** adaptador Cloud API de Meta. Requiere token, versión Graph, WABA, número, secreto de app y token de verificación, todos cifrados. Cada envío comprueba que la [plantilla](https://developers.facebook.com/docs/graph-api/reference/whats-app-business-account/message_templates/) real esté `APPROVED`, tenga el idioma correcto y que el cuerpo sea igual al texto local con parámetros posicionales. El texto local no publica plantillas en Meta. El webhook verifica [`X-Hub-Signature-256`](https://developers.facebook.com/docs/graph-api/webhooks/getting-started/). La elegibilidad del negocio, número y plantillas aún requiere confirmación externa antes de usar un remitente real.
- **Telegram:** opcional y desactivado; no se creó adaptación ni vinculación de `chat_id` porque no hay usuarios que lo hayan solicitado.

Las credenciales Meta conservan su almacenamiento cifrado por equipo y su formulario actual. Las credenciales SMS y su remitente proceden solo del entorno, nunca de la BD ni del formulario. Se omiten secretos de respuestas, auditoría y old input de validación. Deshabilitar un canal detiene las salidas preparadas en la siguiente comprobación. Cambiar `APP_KEY` sin migrar cifrados rompe las conexiones.

La prueba de conexión SMS consulta `GET /json/balance` con Basic Auth, incluso con
las barreras de envío cerradas. Requiere `code=0` y saldo numérico no negativo:
cero acredita conexión, sin demostrar capacidad de pago. WhatsApp conserva la
consulta de plantillas del WABA. Ambas requieren `send test comunicaciones`, no
envían mensajes y auditan actor, resultado y contexto. `checked_at` es la fecha
de la última comprobación exitosa, no una conexión permanentemente vigente; un
fallo SMS borra esa fecha. Cambios de entorno exigen una nueva comprobación.

### Límites y correlación SMS

El backend calcula segmentos sobre el texto definitivo, sin truncar ni quitar
acentos. GSM usa 160 unidades individuales y 153 por parte concatenada;
caracteres extendidos consumen dos. Unicode usa 70/67 unidades UTF-16 y conserva
pares sustitutos completos. Más de dos segmentos, texto vacío o teléfono
inválido cancelan la salida antes del intento (`sms_content_invalid`). Las
capacidades coinciden con las tablas de coste de
[concatenados](https://www.labsmobile.com/es/envio-sms-masivo/sms-concatenados) y
[Unicode](https://www.labsmobile.com/es/envio-sms-masivo/sms-unicode); la tabla
comparativa comercial menciona 157, pero la tabla de coste fija 306 para dos
partes GSM. Se usa el límite conservador de 153 por parte.

`LABSMOBILE_REQUESTS_PER_SECOND` vale 5, acotado a 1–10. Un lock compartido por
cuenta cubre prevalidación, transición e HTTP, con intervalo mínimo después de
cada llamada. Saldo y envíos comparten cupo entre equipos. El almacén debe
soportar locks y ser compartido por todos los workers; `array` se admite solo en
pruebas. Si está ocupado, agotado o no disponible, no se contacta al proveedor y
la salida en cola vuelve a preparada (`sms_rate_limited`) para otro ciclo.
Nunca se aplica esta recuperación a un intento ya iniciado o incierto.

Las salidas nuevas guardan `provider` y `provider_correlation_id` antes del HTTP;
`provider_message_id` conserva el identificador aceptado. La migración aditiva
`2026_11_25_170000` no rellena filas anteriores. Una fila SMS sin proveedor y
correlación propios nunca se reinterpreta como LabsMobile. La correlación no
acredita idempotencia contractual del proveedor.

La [limpieza SMS de prueba](../how-to/limpiar-sms-prueba.md) resuelve datos,
configuración y jobs anteriores sin purgar correo, Meta o colas compartidas.
El [inventario I0](inventario-sms.md) es opcional.

## Estimación antes de contratar

El volumen mensual estimado por prestamista es `pagos confirmados + vencimientos elegibles`, multiplicado por los canales activados, sujeto al límite diario conjunto. Como escenario **hipotético** de dimensionamiento, 1,000 créditos con un pago y un recordatorio cada uno representarían hasta 2,000 avisos por canal al mes, antes de bajas, contactos inválidos, coincidencia de fechas y límites. No es una proyección de cartera real: faltan volúmenes y tasas de consentimiento de cada prestamista.

El costo de SMTP depende del contrato del remitente. [Amazon SES](https://aws.amazon.com/ses/pricing/) es una alternativa con cobro por volumen, pero esta implementación usa el transporte SMTP configurado y no presupone SES. LabsMobile factura SMS por segmentos según destino y cuenta; obtener precio mexicano y confirmación escrita del uso financiero antes de contratar. [Meta](https://whatsappbusiness.com/products/platform-pricing/) cobra mensajes entregados según mercado y categoría; confirmar la categoría y tarifa aplicables a las dos plantillas aprobadas y cualquier cargo de proveedor intermediario. Registrar el presupuesto y aprobación en la decisión comercial del prestamista; el repositorio no conoce esas tarifas de cuenta.

## Despliegue y límites

Migraciones: `2026_11_20_100000`, `100100`, `100200` y `100300`. `100200` registra módulo, permisos y menú; no concede permisos automáticamente a roles existentes. `100300` conserva tres días para reglas existentes hasta que el operador los cambie. Asignar `read/manage/send test comunicaciones` a los roles autorizados para el equipo correspondiente. Revisar `MAIL_*`, `QUEUE_CONNECTION`, scheduler, HTTPS público y las credenciales individuales de cada prestamista. El usuario administra exclusivamente la VPS; estos pasos deben ejecutarlos sus responsables y verificarse mediante salidas compartidas.

El endpoint `/api/webhooks/comunicaciones/whatsapp/{team}` conserva GET de
verificación y POST de estados firmados Meta. El endpoint SMS anterior está
retirado. I2 tampoco publica un endpoint LabsMobile: autenticidad de cuenta sigue pendiente,
y el núcleo interno no habilita aceptación de estados externos. No se atienden
respuestas conversacionales ni campañas.

Referencias: [ADR de arquitectura](../explanation/adr-0049-orquestacion-comunicaciones-externas.md), [eventos de negocio](eventos-comunicacion.md) y [guía de prueba](../how-to/probar-comunicaciones.md).

La migración aditiva I2 `2026_11_25_180000` amplía los acuses existentes, sin
backfill ni tablas nuevas. Ver el [contrato de seguimiento](seguimiento-sms.md).

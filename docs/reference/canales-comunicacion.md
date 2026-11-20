# Canales externos de comunicación

La [nota original de producto](comunicaciones-por-canales-envios-y-seguimiento.md) fija el alcance. Este contrato describe el código de la rama de comunicaciones. La salida externa permanece desactivada hasta configurar `COMMUNICATIONS_SEND_ENABLED=true` en un entorno autorizado, con proveedor, consentimiento y reglas aprobados para cada equipo. La configuración y las pruebas locales no acreditan entrega real ni despliegue.

## Propiedad, consentimiento y permisos

Los créditos nuevos conservan `team_id` del desembolso. La migración recupera créditos históricos solo cuando la auditoría `credito.disbursed` identifica un único equipo. Los créditos ambiguos mantienen `team_id=null` y no pueden generar envíos externos. `communication_events` copia ese contexto estable. El operador debe tener permisos `read comunicaciones`, `manage comunicaciones` o `send test comunicaciones` en el equipo activo según la acción. Las vistas filtran además por la sucursal activa. El equipo del crédito, no una preferencia actual del usuario, determina el prestamista del aviso.

`communication_consents` guarda cliente, equipo, canal, finalidad (`operational` o `preventive`), fecha, origen, referencia a evidencia protegida y hash del contacto autorizado. Registrar consentimiento exige permiso de gestión y edición del cliente en su sucursal. La evidencia permanece en su repositorio protegido y no se copia a esta tabla; el operador registra una referencia verificable. La baja revoca autorizaciones y cancela salidas preparadas. Cambiar correo o teléfono invalida el hash. Nunca se utilizan referencias ni terceros.

La ficha de consulta del cliente muestra el estado de sus consentimientos solo a quien puede leer comunicaciones en el equipo activo; enlaza al formulario de gestión con ese cliente preseleccionado cuando también puede editarlo. La presencia de correo o teléfono en la ficha no significa que estén verificados. No se añade una marca de verificación que los datos actuales no pueden acreditar.

## Matriz inicial

| Evento | Finalidad | Disparador | Destinatario | Canales configurables |
| --- | --- | --- | --- | --- |
| `payment_received` | `operational` | Pago QA confirmado y no reversado | Cliente titular del crédito | Correo, SMS, WhatsApp |
| `upcoming_due` | `preventive` | Obligación programada positiva, exactamente tres días antes, sin atraso y con lectura financiera vigente | Cliente titular del crédito | Correo, SMS, WhatsApp |

No existen reglas activas por defecto. El comando `comunicaciones:preparar-vencimientos` prepara candidatos horarios. `comunicaciones:despachar` corre cada cinco minutos, prepara una salida única por evento y canal, y encola un job después del commit. El job revalida todo antes de contactar al proveedor dentro de la ventana configurada. Si `COMMUNICATIONS_SEND_ENABLED=false`, no encola ni contacta a ningún proveedor. Ambos comandos requieren que el programador Laravel esté ejecutándose realmente en el entorno y el transporte `database` requiere un worker activo; `schedule:list` solo comprueba el registro de tareas.

Cada regla incluye zona horaria, ventana diaria, límite por cliente compartido entre canales, texto y versión. Variables locales permitidas: `{nombre}` y `{fecha_vencimiento}`. Se usa texto sin enlaces a un portal de cliente. La edición incrementa `template_version`; una salida preparada con una versión anterior se cancela antes de salir. No hay reintento automático de una salida incierta. `communication_deliveries` conserva preparado, en proceso, aceptado por proveedor, entregado, leído, fallido, cancelado o incierto. Solo un recibo autenticado del proveedor eleva el estado a entregado o leído. Los webhooks duplicados se deduplican y los adelantados se concilian al conocer el identificador del mensaje. Correo SMTP solo acredita aceptación del servidor; no incorpora rebotes ni lectura sin un proveedor de eventos de correo adicional.

## Conexiones

- **Correo:** usa el transporte Laravel `MAIL_*` del entorno y un remitente por equipo. No introducir credenciales SMTP en la UI. Probar únicamente con el destinatario autorizado en `COMMUNICATIONS_TEST_RECIPIENT`; el endpoint rechaza transportes `log` y `array`. Confirmar dominio, SPF, DKIM, DMARC y política del proveedor antes de activar. El botón de prueba informa aceptación técnica, no recepción.
- **SMS:** adaptador Infobip SMS API v3, base URL de cuenta validada y API key cifrada. Solo activar con constancia de que la ruta/proveedor aceptan avisos financieros mexicanos; las [directrices de Twilio para México](https://www.twilio.com/en-us/guidelines/mx/sms) prohíben contenido de dinero/préstamos, por lo que Twilio no se usa. Infobip emplea [API key con prefijo `App`](https://www.infobip.com/docs/developer-resources/authentication-and-access/api-authentication) y [reportes de entrega](https://www.infobip.com/docs/sms/message-delivery). La cuenta debe configurar perfil de webhook con HMAC SHA-256 para el callback de cada equipo.
- **WhatsApp:** adaptador Cloud API de Meta. Requiere token, versión Graph, WABA, número, secreto de app y token de verificación, todos cifrados. Cada envío comprueba que la [plantilla](https://developers.facebook.com/docs/graph-api/reference/whats-app-business-account/message_templates/) real esté `APPROVED`, tenga el idioma correcto y que el cuerpo sea igual al texto local con parámetros posicionales. El texto local no publica plantillas en Meta. El webhook verifica [`X-Hub-Signature-256`](https://developers.facebook.com/docs/graph-api/webhooks/getting-started/). La elegibilidad del negocio, número y plantillas aún requiere confirmación externa antes de usar un remitente real.
- **Telegram:** opcional y desactivado; no se creó adaptación ni vinculación de `chat_id` porque no hay usuarios que lo hayan solicitado.

Los secretos se guardan cifrados con la clave de aplicación, se omiten de respuestas y auditoría y se reemplazan solo si el operador suministra uno nuevo. Deshabilitar un canal detiene las salidas preparadas en la siguiente comprobación. Cambiar `APP_KEY` sin migrar cifrados rompe las conexiones.

La prueba de conexión de SMS consulta `GET /sms/3/logs?limit=1` de [Infobip](https://www.infobip.com/docs/developer-resources/api-fundamentals/api-requests-and-responses) y la de WhatsApp consulta las plantillas del WABA en [Meta](https://developers.facebook.com/docs/graph-api/reference/whats-app-business-account/message_templates/). Requieren permiso `send test comunicaciones`, no envían mensajes y registran el resultado auditable. Una consulta válida actualiza `checked_at`; cambiar remitente o credenciales borra esa fecha. La consulta no verifica autorización para el contenido financiero, plantilla concreta ni entrega.

## Estimación antes de contratar

El volumen mensual estimado por prestamista es `pagos confirmados + vencimientos elegibles`, multiplicado por los canales activados, sujeto al límite diario conjunto. Como escenario **hipotético** de dimensionamiento, 1,000 créditos con un pago y un recordatorio cada uno representarían hasta 2,000 avisos por canal al mes, antes de bajas, contactos inválidos, coincidencia de fechas y límites. No es una proyección de cartera real: faltan volúmenes y tasas de consentimiento de cada prestamista.

El costo de SMTP depende del contrato del remitente. [Amazon SES](https://aws.amazon.com/ses/pricing/) es una alternativa con cobro por volumen, pero esta implementación usa el transporte SMTP configurado y no presupone SES. [Infobip](https://www.infobip.com/docs/get-started/billing-and-usage) cotiza SMS según destino, red, remitente y cuenta; obtener precio mexicano y confirmación escrita del uso financiero antes de contratar. [Meta](https://whatsappbusiness.com/products/platform-pricing/) cobra mensajes entregados según mercado y categoría; confirmar la categoría y tarifa aplicables a las dos plantillas aprobadas y cualquier cargo de proveedor intermediario. Registrar el presupuesto y aprobación en la decisión comercial del prestamista; el repositorio no conoce esas tarifas de cuenta.

## Despliegue y límites

Migraciones: `2026_11_20_100000`, `100100` y `100200`. La última registra módulo, permisos y menú; no concede permisos automáticamente a roles existentes. Asignar `read/manage/send test comunicaciones` a los roles autorizados para el equipo correspondiente. Revisar `MAIL_*`, `QUEUE_CONNECTION`, scheduler, HTTPS público y las credenciales individuales de cada prestamista. El usuario administra exclusivamente la VPS; estos pasos deben ejecutarlos sus responsables y verificarse mediante salidas compartidas.

Los endpoints firmados son `/api/webhooks/comunicaciones/whatsapp/{team}` y `/api/webhooks/comunicaciones/sms/{team}`. El primero acepta GET de verificación de Meta y POST de estados. El segundo acepta POST de Infobip con HMAC SHA-256 en `X-Hub-Signature`. Configurar el perfil de seguridad del proveedor para ese callback; una URL sin perfil firmado no habilita seguimiento. No se atienden respuestas conversacionales ni campañas.

Referencias: [ADR de arquitectura](../explanation/adr-0049-orquestacion-comunicaciones-externas.md), [eventos de negocio](eventos-comunicacion.md) y [guía de prueba](../how-to/probar-comunicaciones.md).

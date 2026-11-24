# Probar comunicaciones y notificaciones

La [nota original](../reference/comunicaciones-por-canales-envios-y-seguimiento.md) contiene el alcance. El [contrato de canales](../reference/canales-comunicacion.md) explica el comportamiento actual. Las pruebas automatizadas usan SQLite aislado, correo fake y HTTP fake; no contactan proveedores ni acreditados.

## Regresión local

En este equipo, usar PHP 8.4 con SQLite habilitado:

```powershell
& 'C:\laragon\bin\php\php-8.4.27-1-Win32-vs17-x64\php.exe' -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest tests/Feature/CommunicationDeliveryTest.php tests/Feature/CommunicationManagementTest.php tests/Feature/CommunicationWebhookTest.php tests/Feature/CommunicationReadinessTest.php tests/Feature/CreditoPagosTest.php tests/Feature/OperatorNotificationsTest.php tests/Feature/SolicitudesTest.php
npm run test:unit -- resources/js/Components/Notifications/OperatorNotificationBell.test.js
npm run build
```

`CommunicationReadinessTest` puede recibir `COMMUNICATION_QA_EMAIL` solo como variable temporal del proceso; si falta, usa `destinatario@example.test`. No colocar el correo personal autorizado en archivos versionados.

Casos principales: evento de pago único y reverso; recordatorio con anticipación variable por canal (tres días iniciales); lectura invalidada; propietario estable; consentimiento por equipo/canal/finalidad y cambio de contacto; activación de entorno, canal y regla; ventana, frecuencia y versión de texto; prueba técnica bloqueada con correo `log`; firma y duplicados de webhooks; evento adelantado; estado aceptado distinto de entregado; aislamiento del identificador externo entre SMS y WhatsApp; plantilla Meta con texto adicional o variable vacía rechazada antes del POST; permisos y secreto cifrado.

Para ejecutar el transporte WhatsApp y sus webhooks simulados directamente:

```powershell
& 'C:\laragon\bin\php\php-8.4.27-1-Win32-vs17-x64\php.exe' -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest tests/Feature/CommunicationTransportTest.php tests/Feature/CommunicationWebhookTest.php --compact
```

## Revisión E2E de la pantalla

Capturas de la revisión con datos sintéticos: [escritorio](../assets/comunicaciones-desktop.png) y [móvil](../assets/comunicaciones-mobile.png).

1. Arrancar una **base QA aislada**, ejecutar las cuatro migraciones de comunicaciones y asignar permisos `read`, `manage` y `send test comunicaciones` al rol de prueba del equipo. No usar `migrate:fresh` sobre la base de trabajo.
2. Iniciar Laravel y Vite (`composer dev`) y entrar como operador autorizado. Abrir **Operación → Comunicaciones**. Revisar en escritorio y móvil las pestañas Canales, Reglas y plantillas, Consentimientos e Historial. La advertencia de entorno desactivado debe ser visible.
3. Configurar correo con remitente de QA y estado `testing`; confirmar que no permite activarlo hasta registrar elegibilidad `approved` y una referencia. Configurar una regla de pago con `{nombre}`, horario y límite 1. Configurar una regla de vencimiento a cinco días, guardar y comprobar que al reabrirla conserva el valor. La vista previa debe sustituir las variables. Introducir `{importe}` o anticipación 0: debe mostrar un error en español.
4. Registrar en un cliente sintético consentimiento de correo para `operational` con referencia a evidencia protegida. Confirmar que la UI no devuelve correo ni evidencia. Revocar y confirmar que la autorización deja de estar vigente.
5. Generar un pago QA por el flujo de crédito existente. Ejecutar `php artisan comunicaciones:despachar` con `COMMUNICATIONS_SEND_ENABLED=false`: debe preparar a lo sumo una salida y registrar **cero envíos en cola o proveedores**. Repetir el comando para comprobar idempotencia.
6. Filtrar Historial por canal, evento, cliente, crédito, fecha y estado. Abrir un intento y comprobar fecha, versión, motivo y estados. Cambiar de equipo o sucursal y comprobar aislamiento. Un usuario sin permiso recibe 403.
7. Probar campana interna: asignar una solicitud a otro operador, abrir la campana y marcarla leída; el cambio de contexto no debe mostrar datos ajenos.

## Prueba real controlada de correo

El entorno local observado usa `MAIL_MAILER=log` y **no entrega correo**. Para una prueba real, el responsable del entorno debe configurar SMTP real y dominio autorizado, poner `COMMUNICATIONS_TEST_RECIPIENT` con el correo personal que el usuario autorizó, habilitar `COMMUNICATIONS_SEND_ENABLED=true` solo en QA, y comprobar conexión y política del proveedor. En la pantalla, activar el canal de correo del equipo y usar **Enviar prueba técnica** con esa misma dirección. El texto no contiene datos de clientes. Registrar la aceptación SMTP y comprobar el buzón; si el resultado es incierto, revisar el proveedor antes de repetir. La aceptación SMTP no prueba entrega ni lectura.

No activar reglas de acreditados en un entorno con datos reales hasta verificar consentimientos, remitente y políticas. SMS requiere constancia de elegibilidad financiera mexicana, remitente, cuenta Infobip y webhook HMAC. WhatsApp requiere elegibilidad de la actividad, WABA/número, plantilla aprobada en Meta y webhook firmado. Las cuentas, credenciales, aprobaciones y recepción real no están disponibles en este repositorio.

## Preparar WhatsApp Cloud API por equipo

1. En Meta, el responsable del prestamista crea la app y WABA, obtiene un número de prueba o uno dedicado y confirma que el uso financiero previsto está permitido para su cuenta. Obtiene un token con acceso a la WABA y al número, el secreto de la app y un token de verificación elegido para el webhook. Registrar la versión Graph vigente que use esa app; no copiar tokens a documentos o commits.
2. En **Operación → Comunicaciones → Canales**, configurar WhatsApp con `sender` igual al ID numérico del teléfono de Meta y secreto JSON con claves `token`, `api_version`, `waba_id`, `phone_number_id`, `app_secret` y `verify_token`. Confirmar elegibilidad y referencia del prestamista antes de activar el canal. **Comprobar conexión** consulta las plantillas sin enviar un mensaje; `checked_at` acredita solo esa consulta técnica.
3. En Meta, crear y obtener aprobación de las plantillas de pago recibido y próximo vencimiento en el idioma elegido. En **Reglas y plantillas**, registrar para cada evento el nombre y locale exactos. El cuerpo debe coincidir carácter por carácter con el único componente `BODY` de Meta, sustituyendo `{nombre}` y `{fecha_vencimiento}` por `{{1}}`, `{{2}}`, etcétera, según el orden de aparición. No agregar encabezado, pie ni botones en Meta para estas reglas. Ajustar anticipación, horario y límite diario en cada regla.
4. Registrar consentimiento WhatsApp del cliente para el propósito aplicable y verificar el teléfono con código de país. En Meta, configurar la URL HTTPS `/api/webhooks/comunicaciones/whatsapp/{team}`: GET verifica `hub.verify_token` y POST exige `X-Hub-Signature-256` calculada con `app_secret`. Confirmar que el callback usa el ID de teléfono configurado; seguir las salidas en **Historial**.
5. Para un piloto con datos ficticios y número receptor autorizado específicamente para WhatsApp, habilitar `COMMUNICATIONS_SEND_ENABLED` solo en QA, activar la regla y ejecutar scheduler y worker de QA. Registrar por separado respuesta de aceptación (`wamid`), webhook de entrega y lectura. Un HTTP 200 del POST no demuestra entrega. Si falla la consulta de plantilla o el contenido difiere, no se intenta el POST. Si el resultado del POST es incierto, revisar Meta antes de repetir para evitar duplicados.

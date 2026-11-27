# Probar comunicaciones y notificaciones

La [nota original](../reference/comunicaciones-por-canales-envios-y-seguimiento.md) contiene el alcance. El [contrato de canales](../reference/canales-comunicacion.md) explica el comportamiento actual. Las pruebas automatizadas usan SQLite aislado, correo fake y HTTP fake; no contactan proveedores ni acreditados.

## Regresión local

Usar PHP compatible con Laravel y SQLite habilitado (I1 se valida con PHP 8.3.29).
Confirmar que la ejecución usa `APP_ENV=testing`, SQLite `:memory:`, correo array,
colas fake y HTTP fake; no reutilizar la BD local para pruebas.

```powershell
php artisan test --filter='Communication|LabsMobile|SmsText|SmsTestDataCleanup|SmsMigrationInventory|CreditoPagos|OperatorNotifications'
npm run test:unit -- resources/js/Pages/Communications/Index.test.js resources/js/Components/Notifications/OperatorNotificationBell.test.js
npm run build
$env:E2E_FRESH_DATABASE='true'
npm run test:e2e -- communications.spec.js
```

El harness E2E fuerza ambas barreras cerradas y credenciales LabsMobile vacías.
Las suites SMS bloquean HTTP no simulado. Ejecutar un solo proceso PHP de pruebas
por checkout: los fixtures documentales reutilizan `Storage::fake('local')` y
otro proceso puede limpiar sus archivos durante una prueba. Hacer la validación
focal del revisor y el pre-push de forma secuencial. También prueban doble barrera, formato
JSON/Basic Auth, saldo cero, respuesta ambigua/timeout, proveedor/correlación
previos al HTTP, máximo dos segmentos tras sustitución, exclusión compartida de
cuenta y limpieza selectiva con rollback. No ejecutan limpieza operativa.

`CommunicationReadinessTest` puede recibir `COMMUNICATION_QA_EMAIL` solo como variable temporal del proceso; si falta, usa `destinatario@example.test`. No colocar el correo personal autorizado en archivos versionados.

Casos principales: evento de pago único y reverso; recordatorio con anticipación variable por canal (tres días iniciales); lectura invalidada; propietario estable; consentimiento por equipo/canal/finalidad y cambio de contacto; activación de entorno, canal y regla; ventana, frecuencia y versión de texto; prueba técnica bloqueada con correo `log`; firma y duplicados de webhooks Meta; retiro del endpoint SMS; evento adelantado; estado aceptado distinto de entregado; permisos y secreto cifrado.

## Revisión E2E de la pantalla

Capturas de la revisión con datos sintéticos: [escritorio](../assets/comunicaciones-desktop.png) y [móvil](../assets/comunicaciones-mobile.png).

1. Arrancar una **base QA aislada**, ejecutar las migraciones de comunicaciones, incluida la aditiva I1 y asignar permisos `read`, `manage` y `send test comunicaciones` al rol de prueba del equipo. No usar `migrate:fresh` sobre la base de trabajo.
2. Iniciar Laravel y Vite (`composer dev`) y entrar como operador autorizado. Abrir **Operación → Comunicaciones**. Revisar en escritorio y móvil las pestañas Canales, Reglas y plantillas, Consentimientos e Historial. La advertencia de entorno desactivado debe ser visible.
3. Configurar correo con remitente de QA y estado `testing`; confirmar que no permite activarlo hasta registrar elegibilidad `approved` y una referencia. Configurar una regla de pago con `{nombre}`, horario y límite 1. Configurar una regla de vencimiento a cinco días, guardar y comprobar que al reabrirla conserva el valor. La vista previa debe sustituir las variables. Introducir `{importe}` o anticipación 0: debe mostrar un error en español.
4. Registrar en un cliente sintético consentimiento de correo para `operational` con referencia a evidencia protegida. Confirmar que la UI no devuelve correo ni evidencia. Revocar y confirmar que la autorización deja de estar vigente.
5. Generar un pago QA por el flujo de crédito existente. Ejecutar `php artisan comunicaciones:despachar` con `COMMUNICATIONS_SEND_ENABLED=false`: debe preparar a lo sumo una salida y registrar **cero envíos en cola o proveedores**. Repetir el comando para comprobar idempotencia.
6. Filtrar Historial por canal, evento, cliente, crédito, fecha y estado. Abrir un intento y comprobar fecha, versión, motivo y estados. Cambiar de equipo o sucursal y comprobar aislamiento. Un usuario sin permiso recibe 403.
7. Probar campana interna: asignar una solicitud a otro operador, abrir la campana y marcarla leída; el cambio de contexto no debe mostrar datos ajenos.

## Prueba real controlada de correo

El entorno local observado usa `MAIL_MAILER=log` y **no entrega correo**. Para una prueba real, el responsable del entorno debe configurar SMTP real y dominio autorizado, poner `COMMUNICATIONS_TEST_RECIPIENT` con el correo personal que el usuario autorizó, habilitar `COMMUNICATIONS_SEND_ENABLED=true` solo en QA, y comprobar conexión y política del proveedor. En la pantalla, activar el canal de correo del equipo y usar **Enviar prueba técnica** con esa misma dirección. El texto no contiene datos de clientes. Registrar la aceptación SMTP y comprobar el buzón; si el resultado es incierto, revisar el proveedor antes de repetir. La aceptación SMTP no prueba entrega ni lectura.

No activar reglas de acreditados en un entorno con datos reales hasta verificar consentimientos, remitente y políticas. SMS usa LabsMobile por entorno, con barrera independiente desactivada. I2 incorpora solo el [núcleo interno de seguimiento](../reference/seguimiento-sms.md); no existe callback SMS público. Mantener pendiente el piloto autorizado, elegibilidad, remitente y contrato de autenticidad. Para retirar datos anteriores, usar la [limpieza selectiva SMS de prueba](limpiar-sms-prueba.md). WhatsApp requiere elegibilidad de la actividad, WABA/número, plantilla aprobada en Meta y webhook firmado. Las cuentas, credenciales, aprobaciones y recepción real no están disponibles en este repositorio.

## Regresión interna de I2

`LabsMobileAcknowledgementTest` (Unit y Feature) valida niveles, fechas UTC,
normalización estricta, correlación original, duplicados/desorden, HTTP adelantado,
rollback de auditoría y reversión de migración. Solo las pruebas llaman `record()`;
un payload sintácticamente válido no acredita autenticidad. Se simulan acuses
durante HTTP fake y se verifica que aceptación/timeout posteriores conservan
identificadores y estado sin reenvío. GET/POST públicos siguen respondiendo 404.
No usar cuenta, credenciales ni SMS reales para este recorrido.

La reversión de esquema I2 se prueba en otra conexión SQLite `:memory:` y
restaura el Schema builder al terminar. No ejecutar DDL sobre la transacción de
`RefreshDatabase`: MariaDB la confirmaría implícitamente.

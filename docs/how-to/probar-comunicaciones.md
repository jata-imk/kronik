# Probar comunicaciones y notificaciones

Este set valida el incremento local descrito en [eventos candidatos](../reference/eventos-comunicacion.md) y [notificaciones internas](../reference/notificaciones-internas.md). La [nota original](../reference/comunicaciones-por-canales-envios-y-seguimiento.md) contiene el alcance completo. Las pruebas automatizadas usan SQLite aislado y dobles de correo; no entregan mensajes.

## Preparación

1. Usar PHP compatible con `vendor`, dependencias instaladas y base de pruebas aislada. En este equipo se ejecuta PHP 8.4 con `pdo_sqlite` y `sqlite3` habilitados para el proceso de Pest.
2. La dirección personal autorizada puede pasar solo como variable del **proceso de prueba** `COMMUNICATION_QA_EMAIL`; si falta, se usa `destinatario@example.test`. No escribirla en archivos versionados ni en `.env`. La prueba la coloca temporalmente en el cliente sintético y comprueba que no aparece en el evento ni en la auditoría.
3. Ejecutar:

   ```powershell
   $env:COMMUNICATION_QA_EMAIL = '<correo_autorizado>'
   & 'C:\laragon\bin\php\php-8.4.27-1-Win32-vs17-x64\php.exe' -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest tests/Feature/CommunicationReadinessTest.php tests/Feature/CreditoPagosTest.php tests/Feature/OperatorNotificationsTest.php tests/Feature/SolicitudesTest.php
   Remove-Item Env:COMMUNICATION_QA_EMAIL
   npm run test:unit -- resources/js/Components/Notifications/OperatorNotificationBell.test.js
   ```

## Casos y criterio de aceptación

| Caso | Evidencia automatizada | Resultado esperado |
| --- | --- | --- |
| C01. Pago QA confirmado, reintento y reverso | `CreditoPagosTest` | Una ocurrencia por pago; el reintento no duplica; el reverso cancela y conserva el histórico. |
| C02. Vencimiento a cuatro, tres y dos días | `CommunicationReadinessTest` | Solo se prepara al llegar a tres días; la pasada repetida no duplica. |
| C03. Lectura financiera ausente o invalidada | `CommunicationReadinessTest`, `CreditoPagosTest` | No se prepara un vencimiento sin lectura vigente; un candidato previo no se cancela por falta de datos. |
| C04. Pago, calendario y fecha vencida | `CreditoPagosTest` | Se cancela el candidato obsoleto; no se transforma en cobranza. |
| C05. Fallo de auditoría/proyección | `CreditoPagosTest`, `TableroFinancieroTest` | Pago, movimientos, lectura y candidato retroceden juntos. |
| C06. Privacidad y ausencia de transporte | `CommunicationReadinessTest` | Contacto ausente de evento/auditoría; `Mail::fake()` registra cero envíos y cero mensajes en cola. |
| C07. Bandeja interna | `OperatorNotificationsTest`, `SolicitudesTest`, prueba Vue de campana | Aviso deduplicado, lectura una vez y aislamiento por usuario/equipo/sucursal; campana accesible. |

## Revisión manual local

1. En una cuenta operativa de prueba, asignar una solicitud a otro operador de la misma sucursal y abrir su campana. Debe aparecer un aviso nuevo y el historial debe mostrarlo.
2. Marcarlo como leído, recargar y confirmar que no vuelve a no leído. Cambiar de equipo o sucursal y comprobar que desaparece del contexto ajeno.
3. Probar teclado, pantalla estrecha y estados vacío/error de la campana.
4. En el entorno local, ejecutar `php artisan comunicaciones:preparar-vencimientos` después de sincronizar una lectura de QA. El comando informa candidatos preparados; no indica entrega de correo.

## Prueba de recepción real: pendiente

El entorno local revisado usa `MAIL_MAILER=log`, `MAIL_FROM_ADDRESS=hello@example.com` y no tiene usuario SMTP. Ese transporte escribe en un registro local y **no entrega** a la dirección autorizada. La capa de comunicaciones aún carece de selección de prestamista/equipo, consentimiento, plantilla, proveedor y ejecutor de envíos. Cuando esos elementos estén implementados y configurados en QA, ejecutar un único mensaje de prueba con texto claramente sintético a la dirección autorizada, registrar identificador e información del proveedor y pedir al destinatario confirmación de recepción. La aceptación SMTP por sí sola no prueba entrega ni lectura. Los [correos de acceso](probar-correos-acceso.md) tienen otro flujo y no sustituyen esta prueba.

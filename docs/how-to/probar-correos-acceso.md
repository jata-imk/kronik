# Probar correos de acceso y seguridad

## Correos disponibles

| Acción | Correo | Enlace |
| --- | --- | --- |
| Administración → Usuarios → crear o reenviar invitación a una cuenta pendiente | Te invitaron a `{APP_NAME}` | Crear contraseña y activar cuenta |
| ¿Olvidaste tu contraseña?, con una cuenta activa | Restablece tu contraseña en `{APP_NAME}` | Cambiar contraseña |
| Cambiar el correo del perfil o reenviar su verificación | Confirma tu correo en `{APP_NAME}` | Verificación firmada; requiere iniciar sesión |

Las tres variantes comparten diseño, nombre de aplicación, identificación de la cuenta, vigencia, instrucciones de seguridad y enlace alternativo. Cada envío incluye HTML y texto plano (`multipart/alternative`). Si una cuenta **pendiente** solicita recuperación, recibe la variante de invitación: sigue necesitando activar su cuenta.

La invitación utiliza el broker de recuperación existente. Al guardar la contraseña, la cuenta pendiente se activa y su correo se marca como verificado. No se envía una segunda verificación en ese paso. Un reenvío genera otro token e invalida el anterior; si se solicita inmediatamente, el broker puede rechazarlo por su límite de frecuencia. La vigencia se obtiene de `auth.passwords.{broker}.expire` (60 minutos con la configuración actual). La verificación utiliza `auth.verification.expire`, con valor predeterminado de 60 minutos, y mantiene la firma temporal de Laravel.

## 1. Revisar localmente sin credenciales SMTP

No hace falta copiar credenciales de QA ni modificar `.env` para ver las plantillas:

```bash
php artisan mail:preview
```

Solo funciona con `APP_ENV=local` o `testing` y el disco `local` de tipo local. Genera, en la raíz privada de ese disco (`storage/app/private/mail-previews` por defecto):

- `invitacion.html`, `.txt` y `.eml`.
- `recuperacion.html`, `.txt` y `.eml`.
- `verificacion.html`, `.txt` y `.eml`.

Los archivos usan una persona ficticia y enlaces a `preview.example.invalid`, sin tokens utilizables ni firmas válidas. No crea usuarios, no escribe en la base de datos y no envía mensajes. Repetirlo sobrescribe únicamente esas muestras. El nombre y remitente reflejan la configuración local; el comando no modifica `.env`.

Abre los HTML en el navegador y los EML con un cliente que admita ese formato. No agregues una ruta pública a estos archivos. Para una revisión temporal mediante Playwright, se puede servir **solo la carpeta de muestras**, en la interfaz local:

```bash
php -S 127.0.0.1:8041 -t storage/app/private/mail-previews
```

Visita `http://127.0.0.1:8041/invitacion.html` y las otras variantes. Detén el servidor al terminar. No sirvas la raíz completa de almacenamiento privado.

Para capturar envíos de flujos reales localmente, conserva `MAIL_MAILER=log` o utiliza un receptor SMTP local como Mailpit. `log` no entrega mensajes: guarda contenido y enlaces reales en los registros; no los publiques ni los adjuntes a incidencias. Las pruebas automatizadas usan el transporte `array`, sin red.

## 2. Configurar QA

Mantén las credenciales reales únicamente en el entorno autorizado. Ejemplo sin contraseña:

```dotenv
APP_NAME="Kronik QA"
APP_URL=https://kronik.josetejero.com
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.zeptomail.com
MAIL_PORT=465
MAIL_USERNAME=emailapikey
MAIL_FROM_ADDRESS="acceso@tu-dominio-verificado.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Conserva el `MAIL_PASSWORD` del agente SMTP y el remitente de tu dominio verificado. No uses la contraseña de acceso a Zoho como sustituto de la credencial SMTP del agente. Con el puerto 587 corresponde TLS mediante STARTTLS (`MAIL_SCHEME=smtp`). Revisa también cualquier `MAIL_URL` preexistente: puede aportar otra configuración de conexión.

Después de desplegar o cambiar configuración:

```bash
php artisan optimize:clear
php artisan optimize
```

Estos correos mantienen el envío síncrono actual: no dependen de que un worker procese una cola. No necesitan migraciones, seeders ni nuevas variables obligatorias. La plantilla utiliza `APP_NAME`; `MAIL_FROM_NAME` controla el nombre del remitente y puede ser distinto. Los recursos del correo no dependen de Vite ni de imágenes externas.

Un envío desde localhost necesita acceso de red a SMTP, credenciales válidas y un remitente autorizado, igual que cualquier otro servidor. La disponibilidad concreta depende de las restricciones de la cuenta, de IP y de la red. Las pruebas locales no demuestran que el proveedor acepte un envío ni que el destinatario lo reciba.

## 3. Validar entrega y acciones en QA

Usa un buzón propio o de una persona que haya autorizado la prueba. **No pruebes recuperación cambiando la contraseña del único superadministrador.**

1. Invita una cuenta de prueba desde Administración → Usuarios y asigna equipo, roles y sucursal. Comprueba el asunto de invitación, destinatario, remitente y nombre de aplicación.
2. Abre el correo y elige **Crear mi contraseña**. Comprueba que la cuenta pendiente se activa, que puede iniciar sesión y que el mismo token ya no permite otro cambio.
3. Con otra cuenta pendiente, prueba **Reenviar invitación** después del límite de frecuencia. Comprueba que llega la misma variante y usa el enlace más reciente.
4. Para una cuenta ya activa, usa **¿La olvidaste?**. Debe llegar recuperación, sin texto de invitación. Completa el cambio y comprueba acceso con la nueva contraseña.
5. Cambia el correo de esa cuenta desde su perfil a otro buzón de prueba autorizado. Comprueba que la verificación llega a la dirección nueva y que puede completarse con sesión iniciada. Prueba también el reenvío desde la pantalla de verificación.
6. Revisa entrada, spam y actividad de entrega/rebote en Zoho. Un envío aceptado por SMTP no garantiza recepción en bandeja de entrada. Si ya existían invitaciones emitidas antes del despliegue, los mensajes anteriores no se rediseñan: hay que reenviarlos.

No cambies relojes ni expiraciones en el QA compartido para estas comprobaciones: los casos vencidos y alterados tienen regresión automatizada.

## Compatibilidad y revisión visual

La estructura usa tablas de presentación, estilos esenciales en línea, atributos de ancho/color, Arial/Helvetica, ancho fluido de hasta 600 px, contenedor condicional de 600 px para MSO y botón VML para Outlook clásico (enlace HTML normal para los demás clientes). No depende de flexbox, grid, JavaScript, fuentes web, SVG ni imágenes de fondo. Los bordes redondeados son decorativos y pueden verse rectos en clientes antiguos.

Revisar en escritorio y móvil:

- Texto legible y sin desplazamiento horizontal, también a 320 px.
- Botón visible y accesible; su enlace y el alternativo deben coincidir.
- Nombre, correo, acentos y vigencia correctos.
- Mensaje completo con imágenes bloqueadas y en modo de texto plano.
- URL larga de verificación sin desbordar la tarjeta.

**Playwright comprueba Chromium, no el motor Word de Outlook clásico.** La compatibilidad con una versión concreta de Outlook requiere abrir las muestras EML si esa versión admite importarlas y, después, un envío recibido por ese cliente. Registrar versión exacta (por ejemplo, Outlook clásico 2016/2019/Microsoft 365), sistema y captura. Las muestras EML no validan los cambios de HTML que pueda introducir el proveedor. Outlook web y el nuevo Outlook tampoco sustituyen esta prueba.

Antes de dar por cerrada la compatibilidad solicitada, revisar al menos Outlook clásico y un cliente moderno (Gmail/Outlook web), incluyendo modo oscuro e imágenes bloqueadas. Si no hay acceso al cliente antiguo, mantener esa validación pendiente; también puede hacerse mediante un servicio de capturas como Litmus o Email on Acid con autorización de su costo.

Muestras revisadas con Playwright/Chromium (no certifican Outlook clásico):

- [Invitación en escritorio](../assets/correos/mail-invitacion-desktop.png).
- [Recuperación en móvil](../assets/correos/mail-recuperacion-mobile.png).

## Evidencia confirmada en QA

El 18 de octubre de 2026, el usuario confirmó envío desde QA, revisión de logs,
recepción por los destinatarios y creación de usuario mediante invitación.
También confirmó que el correo se ve correctamente en **Outlook clásico**.
Son resultados reportados por el operador, posteriores al despliegue de la
[PR #40](https://github.com/jata-imk/kronik/pull/40).

No se proporcionaron versión/build de Outlook, sistema operativo ni captura de
esa revisión. La confirmación se conserva para el correo observado; no se
extiende automáticamente a todas las variantes, versiones o modos visuales.
La recuperación y verificación cuentan con regresión automatizada; sus pasos
manuales anteriores siguen disponibles para comprobar cada escenario cuando
corresponda. La [nota de entrega](../agent-notes/correos-acceso.md) conserva el
estado y la evidencia.

## Regresión automatizada

```bash
php artisan test --compact tests/Feature/AccountMailTest.php tests/Feature/Console/PreviewAccountMailTest.php tests/Feature/PasswordResetTest.php tests/Feature/EmailVerificationTest.php tests/Feature/UserLifecycleTest.php tests/Feature/ProfileInformationTest.php
```

La regresión incluye transporte real `array` y MIME HTML/texto, creación y reenvío desde el controlador, activación, correo con `+`, tokens vencidos/de un solo uso, firma de verificación alterada o vencida, cambio de dirección, escape de datos en HTML y muestras que no envían ni crean usuarios.

## Fuentes

Consultadas el 18 de octubre de 2026:

- [Zoho CPaaS: configuración SMTP](https://www.zoho.com/cpaas/help/smtp-home.html).
- [Microsoft: problemas de renderizado de correo, incluido Outlook clásico](https://learn.microsoft.com/en-us/dynamics365/customer-insights/journeys/email-troubleshoot-rendering).
- [Microsoft: referencia del elemento VML RoundRect](https://learn.microsoft.com/en-us/windows/win32/vml/msdn-online-vml-roundrect-element).

Implementación: `AccountMailFactory`, callbacks oficiales `ResetPassword::toMailUsing` / `VerifyEmail::toMailUsing` en `FortifyServiceProvider`, vistas compartidas en `resources/views/emails/account/` y comando `mail:preview`. No se sustituye la generación de tokens del broker ni la firma de verificación. La plantilla histórica de invitación Jetstream a equipos permanece fuera de este flujo.

# Preparar y probar Comunicaciones en la VPS

Esta guía prepara una validación posterior a I4. El usuario administra la VPS y
ejecuta los comandos; la revisión del repositorio no acredita despliegue,
scheduler/worker activos, entrega real ni elegibilidad del proveedor.

## Comprobar primero la rama instalada

Desde el directorio de Kronik, sin mostrar `.env` ni credenciales:

```bash
git status --short --branch
git branch --show-current
git rev-parse HEAD
```

El destino de I0–I4 es `feat/comunicaciones-notificaciones`, la rama original de
Bryan. La integración de main `217acef` se prepara en
`integracion/main-comunicaciones-2026-11-27`; consultar en la
[Agent Note](../agent-notes/comunicaciones-notificaciones.md) su estado real,
SHA, PR y CI antes de considerar una actualización. Si la VPS usa `main` u otra
rama, compartir rama/SHA para preparar un cambio autorizado con esa evidencia.
La integración del repositorio no autoriza cambiar la rama del servidor.
La rama `feat/comunicaciones-whatsapp` contiene otro trabajo de Bryan y tampoco
forma parte de esta consolidación.

Solo si el checkout autorizado de la VPS ya corresponde al destino, está limpio
y se han revisado los respaldos y el plan de actualización:

```bash
git pull --ff-only origin feat/comunicaciones-notificaciones
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate:status
php artisan migrate --pretend
```

Revisar la salida antes de aplicar `php artisan migrate --force`. Las adiciones
SMS I1/I2 y Notificaciones I3 no hacen backfill ni convierten textos desechables.
El registro de módulos/menús no concede los permisos nuevos a roles existentes.
Asignar explícitamente los permisos autorizados por equipo. No ejecutar
`migrate:fresh`, seeders generales ni limpieza de datos/jobs como parte del despliegue.

Permisos del módulo: `read comunicaciones`, `manage comunicaciones` y
`send test comunicaciones`. El catálogo global usa acciones independientes
`read`, `create`, `update`, `version`, `activate` y `retire` sobre
`plantillas-notificaciones`; no exige Super Admin. Registrar o revocar
consentimiento requiere además autorización para actualizar al cliente de la sucursal.

## Variables y credenciales

Mantener inicialmente:

```dotenv
COMMUNICATIONS_SEND_ENABLED=false
COMMUNICATIONS_SMS_SEND_ENABLED=false
COMMUNICATIONS_TEST_RECIPIENT=correo-personal-autorizado@example.test
```

Sustituir el destinatario sintético por el correo que el usuario autorice, solo
en el entorno. El catálogo completo está en [variables de entorno](../reference/variables-entorno.md).

### Correo: reutilizar el SMTP existente, incluido ZeptoMail

Comunicaciones utiliza el mismo mailer Laravel que los correos de acceso.
**Si el SMTP de la VPS ya está configurado para ZeptoMail, reutiliza exactamente
esas credenciales**; no crea otra cuenta ni otro almacenamiento de secretos.

| Variable existente | Qué conservar/comprobar |
| --- | --- |
| `MAIL_MAILER` | `smtp` para entrega SMTP; `log`/`array` no entregan. |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME` | Los valores que ya funcionan con el proveedor. No sustituirlos por los de desarrollo. |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | Las credenciales actuales del SMTP, sin copiarlas a Git o a la UI. |
| `MAIL_URL` | Si existe, revisar su precedencia en la configuración instalada antes de cambiar variables individuales. |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Remitente general de los correos existentes. Comunicaciones usa además el correo remitente configurado en su canal; debe estar autorizado por el mismo proveedor. |

No hace falta una variable `ZEPTOMAIL_*` nueva. No cambiar la configuración
existente de sesiones o colas a partir de un ejemplo. Conservar también la zona
horaria de la instalación: I4 cuenta el día de la regla en la zona de los
timestamps almacenados, sin convertir históricos.

### SMS: configuración preparada, piloto pendiente

```dotenv
COMMUNICATIONS_SMS_SEND_ENABLED=false
LABSMOBILE_BASE_URL=https://api.labsmobile.com/json
LABSMOBILE_USERNAME=
LABSMOBILE_TOKEN=
LABSMOBILE_SENDER=
LABSMOBILE_REQUESTS_PER_SECOND=5
LABSMOBILE_RATE_CACHE_STORE=database
```

Usuario/token/remitente deben venir de la cuenta y ruta autorizadas, no de valores
inventados. El almacén del limitador debe existir y compartir locks entre todos
los workers; `array` solo sirve en pruebas. Con ambas barreras cerradas puede
comprobarse la conexión consultando saldo, sin enviar SMS; saldo cero no demuestra
capacidad de envío ni elegibilidad.

**No habilitar SMS para el piloto de esta entrega.** Falta confirmar el contrato
de autenticidad y completar la recepción externa correspondiente. I2 solo aporta
el [núcleo interno de seguimiento](../reference/seguimiento-sms.md). No configurar
`ackurl`, un webhook permisivo o una consulta de estado supuesta. Cuenta, remitente,
ruta mexicana/OMV, tarifas, presupuesto y números autorizados también requieren
evidencia. El [procedimiento de piloto](../explanation/integracion-sms-labsmobile.md#piloto-y-criterios-propuestos)
define escenarios y mediciones, sin autorizar su ejecución.

### WhatsApp: conservar la configuración Meta por equipo

Esta rama no incorpora el piloto adicional de `feat/comunicaciones-whatsapp`.
El contrato existente guarda cifrados en el canal `token`, `api_version`,
`phone_number_id`, `waba_id`, `app_secret` y `verify_token`. No se añadieron
variables `WHATSAPP_*` ni se trasladaron esos secretos al entorno en I4.
El ID del remitente debe coincidir con el de la configuración; WABA/número,
elegibilidad y plantilla/idioma/texto aprobados requieren comprobación externa.
El endpoint Meta mantiene su verificación y firma. Una plantilla local no
publica ni aprueba una plantilla en Meta.

## Configuración efectiva, cola y programador

Después de cualquier cambio aprobado en `.env`:

```bash
php artisan config:cache
php artisan queue:restart
php artisan schedule:list
```

`queue:restart` solicita recarga a workers ya administrados; no instala ni inicia
un servicio. Confirmar el driver/servicio real y revisar su salida mediante el
mecanismo del servidor. El job de envío tiene un intento y timeout de 60 segundos;
el `retry_after` debe exceder el timeout y deben existir las tablas/infraestructura
del driver configurado. Los comandos registrados en `schedule:list` no prueban
que cron esté ejecutándolos. Pedir/revisar la evidencia del programador y del
worker antes de probar salidas automáticas. No purgar colas compartidas.

## Prueba técnica inicial de correo

1. Revisar todos los equipos/canales/reglas antes de abrir la barrera global;
   dejar deshabilitadas las reglas automáticas ajenas a la prueba y mantener
   cerrada la barrera SMS. No usar datos de acreditados reales.
2. Con permisos `read/manage/send test comunicaciones`, configurar el canal de
   correo del equipo, remitente autorizado y elegibilidad aprobada con su referencia.
3. Configurar `COMMUNICATIONS_TEST_RECIPIENT` con el correo personal autorizado.
   Para esta prueba posterior, activar temporalmente `COMMUNICATIONS_SEND_ENABLED=true`,
   regenerar caché de configuración y recargar los workers existentes.
4. Usar **Enviar prueba técnica** con ese mismo destinatario. El endpoint solo
   admite correo y no requiere generar un pago ni contiene datos de clientes.
5. Registrar aceptación técnica, hora y observación del buzón. Aceptación SMTP no
   equivale a entrega ni lectura. Ante resultado incierto, revisar el proveedor
   antes de repetir; no interpretar la falta de confirmación como permiso de reenvío.
6. Volver a `COMMUNICATIONS_SEND_ENABLED=false`, regenerar configuración y recargar
   workers al terminar, salvo autorización posterior de otro escenario concreto.

Para probar el flujo automático, preparar después clientes/contactos sintéticos
autorizados, consentimiento efectivo, plantilla global activa y regla compatible.
Seguir [QA de Comunicaciones](probar-comunicaciones.md) y
[edición de Notificaciones](editar-notificaciones.md). Un cambio de contacto exige
nueva autorización; la consulta distingue ese estado de «Vigente» y «Revocado».
Registrar rama/SHA, entorno y resultados: no atribuir a la VPS las pruebas locales
o el CI de I4.

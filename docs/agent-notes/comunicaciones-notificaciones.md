# Comunicaciones y notificaciones

## Referencias

- [Requisito de Notion](https://app.notion.com/p/Kronik-Comunicaciones-por-canales-env-os-y-seguimiento-3fe61db7db7f81e4af50c4f0f3ab7e41) y [copia literal conservada](../reference/comunicaciones-por-canales-envios-y-seguimiento.md).
- Rama `feat/comunicaciones-notificaciones`; commits `793e684` (base), `1027e89` (móvil), `fb12c7f` (canales y seguimiento), `36da998` (evidencia), `04543e3` (parametrización) y `909bb24` (capturas y publicación). Publicada en GitHub al 2026-11-20. La [PR #91](https://github.com/jata-imk/kronik/pull/91) recibió `/review medium` y fue cerrada sin integrar por indicación del usuario: la entrega debe permanecer en esta rama de desarrollo, sin llevar cambios a `main`.
- [Contrato de eventos](../reference/eventos-comunicacion.md), [canales](../reference/canales-comunicacion.md) y [ADR 0049](../explanation/adr-0049-orquestacion-comunicaciones-externas.md).

## Estado — 2026-11-20

Implementado localmente en la rama: bandeja interna contextual; eventos candidatos para pago QA y próximo vencimiento con anticipación configurable por regla/canal (tres días iniciales); equipo estable de nuevos créditos y recuperación conservadora de históricos; consentimiento por equipo/canal/finalidad/contacto con acceso desde la ficha del cliente; reglas, plantillas y ventanas; adaptadores SMTP, Infobip y Meta; comprobaciones técnicas de conexión sin envío; salida en cola después del commit; revalidación previa; historial, filtros y webhooks firmados con deduplicación y conciliación. La vigencia del pago, las esperas operativas y el calendario del scheduler se parametrizan en el entorno. SMS y WhatsApp no están activados en ningún proveedor. Telegram permanece opcional y desactivado.

El entorno local sigue con `MAIL_MAILER=log`. La barrera `COMMUNICATIONS_SEND_ENABLED=false` es el valor predeterminado y no se envió correo al destinatario personal autorizado ni a acreditados. No se han configurado credenciales ni elegibilidad de prestamista/proveedor. La cuenta, identidad, remitente, permisos externos, plantilla Meta y ruta SMS mexicana requieren validación externa antes de tráfico real. No se ha desplegado en VPS ni confirmado cron/worker del servidor. El usuario administra la VPS; no se accedió a ella.

## Evidencia

- Pest conjunto de comunicaciones, pagos, solicitudes y campana: **102 pruebas, 1,629 aserciones**, SQLite aislado con PHP 8.4, 2026-11-20. Incluye permisos, secretos, consentimientos, contacto cambiado, idempotencia, cola, rechazo/timeout, firmas, duplicados, desorden y conciliación de webhooks.
- Prueba unitaria Vue de campana: 1 aprobada. Build Vite aprobado. `vendor/bin/pint --dirty` y `git diff --check` aprobados.
- E2E con Edge y SQLite desechable: 2 recorridos aprobados, escritorio y móvil, regla/vista previa/historial y anchura de las cuatro secciones. Capturas sintéticas conservadas en `docs/assets/comunicaciones-desktop.png` y `docs/assets/comunicaciones-mobile.png`. El primer lanzador `tests/E2E/run.js` falló por una aserción interna de `fetch` de Node 24; la ejecución manual con servidor visible y la misma base aislada sí pasó. `public/hot` fue restaurado.
- Regresión de crédito anticipado, Bullet y tablero: **26 pruebas, 1,234 aserciones** aprobadas con PHP 8.4 y SQLite aislado.
- Tras integrar la ficha del cliente y el límite diario, **13 pruebas focalizadas, 238 aserciones** aprobadas; incluye webhooks, entregas, gestión, permisos y revocación. La comprobación técnica de APIs añadió otra prueba de gestión (**5 aprobadas, 79 aserciones**). Build Vite repetido con éxito. `route:list` y `schedule:list` registran las rutas y los dos comandos; esto no acredita ejecución del programador en VPS.
- Parametrización posterior: **14 pruebas focalizadas, 257 aserciones** de preparación, gestión y entregas; otras **20 pruebas, 312 aserciones** de pagos, webhooks y transportes; tras ajustar valores iniciales, **11 pruebas, 196 aserciones** de preparación y entregas. Build Vite aprobado. `schedule:list` reflejó intervalos temporales de 15 y 10 minutos configurados por entorno.
- Cierre de parametrización: **37 pruebas, 615 aserciones** de comunicaciones y pagos con SQLite aislado y PHP 8.4. Incluye dos canales con anticipaciones distintas para un solo vencimiento, reprogramación de salida ya en cola, valores predeterminados variables y validación de rango. Build Vite, Pint y enlaces locales de la documentación aprobados. No se enviaron mensajes reales.

## Traspaso anterior — 2026-11-20

El 2026-11-20, los commits pendientes se publicaron mediante la API Git Data de GitHub, conservando sus SHA y sin forzar la rama; el remoto quedó en `909bb24`. La PR #91 se cerró sin integrar y se canceló el merge local de `main`; la rama de desarrollo es la entrega vigente. El transporte Git HTTPS directo sigue sin conexión y `ssh.github.com:443` rechazó la llave disponible. Para QA real, configurar SMTP con dominio y destinatario de prueba autorizado; para SMS/WhatsApp, obtener aprobación escrita de uso, cuentas, remitentes, plantillas y webhook HTTPS antes de activar. Registrar recepción real y operación del programador/worker solo cuando haya evidencia. Actualizar Notion al confirmar integración/QA.

## Retoma de SMS — 2026-11-24

Estado: preparación del checkout completada; LabsMobile elegido y planeación SMS
en progreso con José.
La tarea de Notion consultada conserva la evaluación de SMS a cargo de José y
WhatsApp/Facebook a cargo de Brian. José pidió comparar proveedores y después
eligió LabsMobile por costo. La presencia del adaptador Infobip es evidencia
del código anterior, no una integración LabsMobile ni habilitación real.

- `main` y `origin/main`: `217acef`; `fetch origin` y `pull --ff-only origin main`
  completados por HTTPS. El bloqueo de transporte del traspaso anterior no se
  reprodujo. Los ocho archivos documentales locales revisados conservaron sus
  hashes SHA-256 y el estado Git original permaneció igual.
- Worktree: `storage/framework/testing/comunicaciones-worktree`, rama local
  `feat/comunicaciones-notificaciones`, seguimiento de su homónima en `origin`,
  base funcional `ecb9d4a`. Al prepararlo coincidía con el remoto. La investigación
  y el traspaso se publicaron después en el commit documental `6b11bf6`.
- La comparación `main...origin/feat/comunicaciones-notificaciones` muestra
  89 commits exclusivos de `main` y siete de comunicaciones. No se fusionaron
  las ramas; la sincronización se revisará antes de implementar para conservar
  los contratos financieros vigentes y el trabajo compartido de comunicaciones.
- Se inspeccionaron el adaptador SMS, la orquestación, la configuración, el
  seguimiento de webhooks y las pruebas existentes. Los resultados del 20/11
  son evidencia histórica; no se ejecutaron nuevamente suites funcionales.
- No se prepararon dependencias, `.env` ni BD de ejecución en este worktree.
  No hubo migraciones, cambios de configuración, envíos ni acceso a VPS.
  Esta retoma documental no añade acciones auditables ni requiere nuevos eventos.

### Punto de partida anterior a la elección

1. Confirmar proveedor y caso inicial dentro de los avisos operativos/preventivos.
   Verificar documentación vigente y condiciones de la cuenta: ruta mexicana,
   uso financiero, remitente, costos por segmento y reportes de entrega.
2. Contrastar el adaptador existente con la API elegida: autenticación, formato
   telefónico, texto/codificación, respuesta de aceptación y webhook autenticado.
   Identificar ajustes antes de configurar credenciales o habilitar tráfico.
3. Preparar pruebas aisladas de aceptación/rechazo, timeout incierto, duplicados,
   estados fuera de orden, consentimiento/contacto, contexto de equipo/sucursal
   y auditoría sin secretos. Reutilizar la orquestación y los contratos actuales.
4. Definir un piloto con destinatario y entorno autorizados, límite de gasto y
   evidencia separada de aceptación y entrega. La autorización de un correo de
   prueba anterior no autoriza un número SMS. Registrar los resultados efectivos.

### Siguiente paso

José confirmó LabsMobile por costo el 2026-11-24. Se registró
[ADR 0050](../explanation/adr-0050-proveedor-sms-labsmobile.md), que sustituye
únicamente la elección de Infobip del ADR anterior. La
[investigación de LabsMobile](../explanation/integracion-sms-labsmobile.md)
documenta API, estados, callbacks, OMV, caducidad, remitente, métricas, soporte y
piloto. El código sigue en `ecb9d4a`, sin implementación LabsMobile.

Hallazgos materiales: la ficha mexicana describe acuses de red, no prueba al
dispositivo; anuncia 100% de cobertura sin listar los tres OMV consultados;
la caducidad habitual citada es 72 horas, sin TTL de API identificado; la firma
del callback y la consulta autenticada por mensaje siguen sin contrato público
encontrado. La publicidad menciona 99.9% de entrega; el SLA de términos es 99.4%
de disponibilidad mensual, otra métrica. Los ejemplos mexicanos con 521 requieren
contraste antes de implementarlos. No se contactó a proveedores ni se abrieron
cuentas; la investigación no ejecutó pruebas autenticadas ni envíos.

Siguiente paso: iniciar la sesión de planeación desde el traspaso siguiente;
incluir sustitución de Infobip y evaluación del editor. Las preguntas para
soporte/cuenta y la definición del piloto siguen identificadas en la investigación.
La elección está aceptada; contratación, implementación, despliegue y validación
siguen pendientes. Documentación, índices, referencia de canales y Notion se
actualizan; no hay permisos, migraciones ni acciones auditables nuevas.

La validación anterior cubrió ocho archivos y 96 enlaces; el traspaso amplía la
revisión a los documentos nuevos y sus índices. No se repitieron suites
funcionales: no hubo cambios de código.

## Traspaso a la nueva sesión — 2026-11-24

José comenzará la planeación en una nueva sesión. Esta entrega conserva solo
investigación, selección de LabsMobile y evaluación preliminar, sin implementación
ni activación. Commit de documentación:
[`6b11bf6`: conservar investigación SMS y plan de retoma](https://github.com/jata-imk/kronik/commit/6b11bf6abfd24de3fea0a32a01d9fad80d6b2d0d).
Push a `origin/feat/comunicaciones-notificaciones` confirmado el 2026-11-24 por
petición de José; el remoto avanzó desde `ecb9d4a` sin forzar. Sin PR, integración
en `main` ni despliegue de este traspaso.

Punto de entrada de la siguiente sesión:

1. Usar el worktree `storage/framework/testing/comunicaciones-worktree` y la rama
   `feat/comunicaciones-notificaciones`. Preservar cambios ajenos y revisar la
   divergencia con `main` antes de proponer compatibilidad; no fusionar durante
   la planeación ni reabrir la elección de LabsMobile.
2. Leer ADR 0050, la [investigación y sustitución de Infobip](../explanation/integracion-sms-labsmobile.md)
   y la [evaluación del editor](../explanation/editor-plantillas-notificaciones.md),
   junto con el alcance vigente de Notion.
3. Proponer un incremento de reemplazo completo de Infobip: transporte,
   configuración, UI, conexión, webhook/conciliación, pruebas, credenciales,
   jobs pendientes y procedencia histórica. Hay referencias activas en varias
   capas y las salidas no guardan un proveedor propio; no borrar evidencia ni
   mantener un proveedor de respaldo implícito.
4. Evaluar la propuesta de editor visual con categoría Notificaciones: compartir
   inserción/búsqueda de variables, texto plano, vista previa y segmentación
   después de sustituir variables. Decidir alcance global/equipo, permisos,
   versiones y compatibilidad entre sintaxis. No habilitar todo el catálogo
   documental por agregar un enum ni crear PDF para un SMS.
5. Presentar plan de incrementos, dependencias, criterios de aceptación, pruebas
   y preguntas externas pendientes. Mantener auditoría, lecturas financieras y
   aislamiento. Continuar la planeación; la implementación será trabajo posterior.

El editor actual usa HTML/Quill; su panel está en la página de plantillas y sus
registros son globales. Las comunicaciones se configuran por equipo y canal.
Reutilizar UI es viable, pero no se decidió todavía compartir persistencia ni
agregar la categoría al enum documental. No se modificó ni eliminó Infobip.

Validación del traspaso: diez documentos UTF-8 sin BOM, LF y salto final,
128 enlaces locales con destino existente y `git diff --check` aprobado.
El checkout principal conserva los hashes de los ocho documentos locales
preexistentes revisados. No corresponden nuevas pruebas funcionales a este commit.

La publicación usó `SKIP_PRE_PUSH=1` solo en el proceso de push, tras verificar
que el avance contiene exclusivamente documentación; se restauró el entorno al
terminar. Es la exención del hook del repositorio, coherente con la entrega
documental; no se ejecutaron suites funcionales ni se preparó una BD.

## Integración WhatsApp — 2026-11-24

Brian confirmó que José desarrolla SMS y que esta sesión corresponde a WhatsApp.
Se creó `feat/comunicaciones-whatsapp` desde `547d9e5` para aislar el trabajo;
no se modificó el adaptador Infobip ni la elección LabsMobile. La integración
Cloud API existente se reforzó para consultar en Meta la plantilla aprobada por
nombre e idioma y para impedir POST si hay componentes invisibles en la vista
previa, variables vacías o destino inválido. El destino se entrega a Meta como
dígitos internacionales; el contacto normalizado de consentimiento se conserva.
La verificación GET del webhook devuelve el desafío incluso cuando PHP normaliza
`hub.challenge` a `hub_challenge`. Los recibos Meta se asocian únicamente a
salidas WhatsApp del equipo, aunque SMS tenga el mismo identificador externo.

Se mantienen los eventos de auditoría existentes: inicio, aceptación, fallo y
transición por webhook. No hay una acción auditable nueva; se actualizaron la
matriz y las pruebas del fallo previo al POST y del aislamiento por canal.
Revisar [canales](../reference/canales-comunicacion.md) y la
[guía de prueba](../how-to/probar-comunicaciones.md) para configuración.

Evidencia local: 25 pruebas y 386 aserciones del conjunto de comunicaciones
(Delivery, Management, Webhook, Readiness y Transport) con PHP 8.4/SQLite aislado;
prueba GET del desafío de Meta y rechazo de token incorrecto; Pint y
`git diff --check` aprobados. Proveedores HTTP simulados, sin credenciales reales.

Estado: implementado localmente, pendiente de validación autenticada con una
cuenta Meta, número receptor WhatsApp autorizado, plantilla aprobada y webhook
HTTPS. No se enviaron mensajes reales ni se accedió a la VPS. No se usó el
correo autorizado como autorización de un número telefónico. Este incremento no
agrega migraciones, seeders ni variables de entorno.

## Solicitud de prueba real WhatsApp — 2026-11-24

El usuario autorizó una prueba con un número propio como remitente y otro número
concreto como receptor. Los números personales no se versionan aquí. Se comprobó
el entorno local sin modificar datos: `.env` usa MySQL y no define credenciales
Meta ni `COMMUNICATIONS_SEND_ENABLED`; al arrancar temporalmente MySQL, la base
`kronik_softcredito9` no contenía `communication_channels`. MySQL se detuvo de
forma ordenada después de la consulta. La API Graph de Meta responde desde esta
máquina, pero no hay token, ID de teléfono WABA ni plantilla aprobada para enviar.
No se ejecutaron migraciones, no se llamó al endpoint de mensajes y no hay
aceptación ni entrega que acreditar. El navegador virtual disponible tampoco
tenía una sesión de Meta; la app personal de WhatsApp no prueba Cloud API.

Siguiente paso: confirmar que el número remitente está registrado en una WABA
accesible, configurar token/ID/plantilla en un entorno QA aislado y registrar
aceptación `wamid`, entrega y lectura por webhook para el receptor autorizado.

## Preparación de piloto WhatsApp — 2026-11-24

Se preparó una base SQLite QA nueva y aislada en
`storage/framework/testing/kronik-e2e-dc868276-2874-4453-8629-6d01f45d4922.sqlite`.
Se verificó que el archivo estaba vacío antes de ejecutar `migrate --seed` con
PHP 8.4 y `E2eSeeder`, `APP_ENV=e2e`, correo `array` y envíos externos desactivados.
Contiene ocho usuarios sintéticos, tres permisos de comunicaciones y ninguna
credencial Meta. El MySQL operativo no recibió migraciones ni seeders. El PHP
predeterminado 8.2 falló antes de migrar; la repetición con 8.4 completó el
esquema. `public/hot` se conservó y restauró tras cada recorrido.

Se añadió una prueba técnica WhatsApp con flag y destinatario E.164 permitidos
por entorno, permiso específico y límite de dos peticiones por hora. Usa la
plantilla aprobada del equipo y variables ficticias, sin activar envíos
automáticos; expone la referencia Meta tras aceptación y audita el resultado
sin guardar número ni secreto. La prueba no crea una salida de negocio ni
certifica entrega. Backend focalizado: 15 pruebas, 188 aserciones; build Vite
aprobado. El recorrido E2E inicial de escritorio y móvil pasó (2 pruebas)
contra la base QA antes de agregar el control de envío WhatsApp. El recorrido
actualizado pasó de nuevo (2 pruebas, escritorio y móvil); verificó que la
prueba está visible pero bloqueada sin habilitación de entorno. El servidor
QA se detuvo y `public/hot` se restauró.

Se agregó `tests/E2E/run-whatsapp-qa.ps1` para reabrir esa base aislada en
`127.0.0.1`, con envíos automáticos desactivados y prueba WhatsApp habilitable
solo al pasar un destinatario E.164. Se comprobó que rechaza un Vite inactivo
y que, con Vite activo, sirve la pantalla de acceso con HTTP 200. El lanzador
no altera `public/hot` ni abre un endpoint público para Meta.

La regresión conjunta de Delivery, Management, Webhook, Readiness y Transport
pasó con PHP 8.4 y SQLite: 27 pruebas, 406 aserciones. `php artisan test`
invocó el PHP predeterminado sin SQLite en sus procesos hijos y falló antes de
las aserciones; se usó `vendor/bin/pest` con la extensión SQLite explícita.

Meta Business mostró inicio de sesión en el navegador integrado; no hubo
cuenta autenticada ni acceso a WABA, ID de teléfono, token, plantilla aprobada
o webhook HTTPS público. No se llamó a `/messages` ni se enviaron mensajes.

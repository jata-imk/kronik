# Comunicaciones y notificaciones

## Retoma vigente — SMS I1, 25/11/2026

Implementación en `feat/sms-i1-labsmobile`, creada desde `9d0473d` en el worktree
acordado. PR hacia `feat/comunicaciones-notificaciones`, dependiente de I0 #93
mientras siga abierta. No se fusionó main ni se integró una PR. Base funcional
para el pase final de I4: `ecb9d4a`.

Al arrancar, Git remoto confirma `9d0473d`; la API de #93 todavía devuelve
`213a4b6` como HEAD, PR abierta y sin hilos pendientes. Revisión medium acreditada
sobre `3095384`; CI verde para ese código y `213a4b6`, sin comprobaciones visibles
para `9d0473d`. Main sigue en `217acef`, ancestro `bfa0b87`, divergencia 89/15.
Se mantienen los cruces ya documentados para una integración futura.

I1 implementa HTTP LabsMobile, configuración por entorno mediante `config()`,
UI de presencia sin escritura de secretos/remitente, comprobación de saldo sin
SMS, doble barrera, dos segmentos tras sustitución, limitación atómica por cuenta
y proveedor/correlación antes del envío. Retira adaptador, endpoint y conciliación
Infobip; Meta conserva seguimiento. La nueva migración es aditiva y no hace
backfill. La limpieza QA selectiva invalida salidas anteriores y elimina solo jobs
identificados, con rollback y auditoría; conserva correo, Meta y colas compartidas.
Las filas canceladas neutralizan trabajos no identificados. Una nueva ejecución
no desactiva reglas de un canal LabsMobile configurado después.

Código PHP `f824640`; recorrido E2E y capturas `30a1689` (la captura de escritorio
se ajusta después para iniciar arriba). Suite backend completa: 1,069 aprobadas,
dos omitidas optativas, 48,623 aserciones; frontend completo: 67 archivos / 177
pruebas aprobadas. Build Vite y Pint aprobados. E2E Chromium en SQLite nueva:
dos recorridos aprobados, escritorio y móvil, con barreras cerradas y credenciales
vacías. El fallo inicial de selector E2E se resolvió tras revisar traza, captura,
video y consola; la repetición pasó. UTF-8/LF y 205 enlaces locales de 57 archivos
textuales del rango comprobados; 11 documentos con índice y tema.

Código final `cacf89f`; revisión medium completa contra `547d9e5`, Codex CLI
0.172.0, terminada sin regresiones accionables. Su validación secuencial aprobó
70 pruebas backend y cuatro frontend. Incluye la regresión de escritura de caché
rechazada sin excepción. No quedan hallazgos de código pendientes.

La primera revisión se interrumpió por cuota. La segunda no encontró hallazgos,
pero su ejecución simultánea con el pre-push causó fallos documentales: ambos
procesos limpiaban el mismo `Storage::fake('local')`. El primer push se detuvo
por un fallo del ciclo Bullet con ese mismo archivo. No se descartaron esos
resultados como aprobados; revisión final y pre-push se ejecutan ahora de forma
secuencial, según la guía QA. Publicación/CI todavía en curso; registrar PR y
resultado antes de cerrar.
No se modificó `.env`, se ejecutó limpieza operativa,
se habilitó tráfico, se accedió a VPS ni se desplegó. Las pruebas usan datos
sintéticos y BD aisladas. Seguimiento I2, editor I3, Integraciones y piloto siguen
pendientes. Entregar prompt I2 al cerrar y detenerse.

## Antecedente — SMS I0, 25/11/2026

**Corrección de alcance confirmada por José el 25/11/2026:** Kronik solo está en
desarrollo y QA; nunca ha estado en producción, no existen clientes ni datos
históricos que conservar y los datos de prueba son desechables. La suposición de
necesitar una migración histórica fue incorrecta. Se elimina ese incremento
independiente: el siguiente I1 implementa directamente LabsMobile y retira
Infobip, tratando datos/jobs de prueba mediante limpieza explícita cuando haga
falta. No desarrollar backfill, compatibilidad de callbacks ni conciliación de
históricos Infobip. El inventario I0 queda como herramienta opcional de QA.
La corrección es documental: no se borraron datos ni se ejecutó I1.

José autorizó la implementación y después pidió **una sesión y una PR por
incremento** para no arrastrar contexto. Cada cierre debe conservar esta Agent
Note, evidencia en Notion y prompt de continuación. `/review medium` y hallazgos
resueltos en cada PR; al cerrar SMS, pase acumulado de todas las PR contra
`ecb9d4a`, sin sustituirlo por las revisiones individuales.

- Rama de I0: `feat/sms-i0-inventario`, creada desde `547d9e5` dentro del worktree
  `storage/framework/testing/comunicaciones-worktree`. Base de la PR:
  `feat/comunicaciones-notificaciones`. No se fusionó main ni se forzó historia.
- [PR #93](https://github.com/jata-imk/kronik/pull/93), abierta y no integrada.
  Código y pruebas finales `3095384a5be2b94e2f29be35b4ea826ebbbbc764`; arranque `0fe03d5`.
  El commit documental posterior registra este cierre sin cambiar código.
- Alcance I0: comando `comunicaciones:inventariar-sms`, servicio agregado de solo
  lectura, contrato y matriz de compatibilidad. El inventario conserva Infobip y
  no cambia conexiones, jobs, reglas, proyecciones ni auditoría de negocio.
- Compatibilidad de QA: se clasifican las siete tablas de comunicaciones en el
  reinicio existente, respetando sus barreras y claves foráneas, y se corrigen
  fixtures de menús que chocaban con módulos/vínculos ya registrados. El ciclo
  completo de reinicio se probó solo en SQLite aislado; no se reinició ningún
  entorno operativo. No cambia el flujo normal de pagos ni de comunicaciones.
- El [plan canónico por incrementos](../explanation/plan-incrementos-sms.md)
  registra las decisiones finales y cruces con main; prevalece sobre alternativas
  abiertas del traspaso anterior. Una instalación es una financiera; los equipos
  son departamentos. Credenciales por entorno, plantillas de Notificaciones
  globales con permisos por acción, sin exclusividad de Super Admin, dos segmentos
  tras sustitución y cancelación de pendientes al cambiar/retirar redacción.
- [Tarea de Integraciones creada en Notion](https://app.notion.com/p/42361db7db7f81e6b747c5ea0241cbb4),
  pendiente para después. No se implementa su catálogo ni se modifican CDC o Meta
  en I0. Brian conserva WhatsApp/Facebook y su trabajo compartido.
- Dependencias instaladas desde lockfiles sin cambios versionados; `.env` local
  sintético con SQLite `:memory:`, correo `array` y envíos desactivados. No se
  copiaron secretos ni se preparó una BD operativa. PHP 8.3.29 / Node 22.22.3.
- Prueba nueva del inventario: 14 aprobadas, 116 aserciones. Conjunto final de
  inventario, menús y reinicio QA: 53 aprobadas, 504 aserciones. Pint aprobado y
  build Vite aprobado. El pre-push completo sobre `3095384` aprobó **1,016 pruebas,
  48,249 aserciones**, con dos omitidas optativas (renderer Chromium real y
  benchmark de cartera). Ejecutó también build sin saltar el hook. UTF-8/LF,
  salto final y 144 enlaces locales de 15 archivos comprobados.
- Primera revisión medium sobre `0fe03d5`: señaló la identificación del almacén
  database activo con varias conexiones. La completitud ya consideraba todos
  los almacenes; se corrigió además `active_inspectable`, se agregó `active` por
  almacén y se probaron ambas selecciones de cola. La revisión del rango de esta
  PR se repitió después de los cambios de código y fixtures.
- Segunda `/review medium`: Codex CLI 0.172.0 con esfuerzo medium, base
  `547d9e5` y código `b6899f5`, terminó sin defectos accionables. Se revisó todo
  el diff de la PR, incluidos los ajustes de QA. El señalamiento anterior quedó
  resuelto; no hay hallazgos de código pendientes. El pase acumulado de SMS contra
  `ecb9d4a` sigue siendo requisito del cierre final, no está acreditado por esta revisión.
- Ajuste final de portabilidad de pruebas `3095384`: el esquema ausente se
  simula en otra conexión SQLite, sin renombrar tablas de la aplicación ni DDL
  en su transacción. Las 14 pruebas/116 aserciones pasaron de nuevo. Tercera
  revisión medium del diff completo de la PR sobre ese SHA: sin defectos
  accionables. Después pasó también el pre-push completo y build citados arriba.
- Publicación Git confirmada a `origin/feat/sms-i0-inventario`; Notion registra
  PR y evidencia. CI remoto y cualquier integración deben comprobarse por su SHA
  al retomar; no se afirma despliegue ni validación contra proveedores.
- No hay migraciones, seeders ni variables nuevas de este incremento. El comando
  CLI de consulta no necesita evento auditable nuevo; justificación y pruebas
  enlazadas desde la matriz. No hay UI nueva ni E2E adicional requerido para I0.

### Siguiente sesión

Retomar **I1, sustitución directa de Infobip por LabsMobile**, después de verificar
el estado y SHA de la
[PR I0 #93](https://github.com/jata-imk/kronik/pull/93). Conservar el código revisado de I0 aunque aún no esté integrado y no asumir
que publicar una rama equivale a integración. No empezar I2–I4 en esa sesión.
Resolver primero cualquier hallazgo pendiente de I0 y elegir la base de la nueva
PR desde el estado real; nunca mezclar main ni sobrescribir comunicaciones.

Prompt de continuación:

> Continuemos SMS de Kronik, exclusivamente el incremento I1 (sustitución directa por LabsMobile).
> Trabaja en C:\Users\jose.tejero\Documents\Proyectos\kronik\storage\framework\testing\comunicaciones-worktree.
> Lee AGENTS.md, docs/agent-notes/comunicaciones-notificaciones.md,
> docs/explanation/plan-incrementos-sms.md, docs/reference/inventario-sms.md y el
> alcance vigente de Notion 3fe61db7db7f81e4af50c4f0f3ab7e41.
> I0 está en la PR #93 https://github.com/jata-imk/kronik/pull/93,
> rama feat/sms-i0-inventario, código y pruebas revisados 3095384. Verifica revisiones, CI y estado de
> integración antes de elegir la rama de I1; preserva todo el trabajo compartido.
> Base funcional del barrido final: ecb9d4a. No fusiones main ni habilites tráfico.
> Kronik solo existe en dev/QA: nunca estuvo en producción, no hay clientes ni
> históricos que conservar y sus datos de prueba son desechables. No implementes
> migración histórica ni compatibilidad con Infobip. El inventario I0 es opcional.
> Implementa solo I1: transporte LabsMobile, configuración por entorno, UI sin
> escritura de secretos, comprobación de conexión, barrera SMS, límites y retiro
> activo de Infobip, con pruebas aisladas y auditoría. Resuelve datos y jobs de
> prueba mediante limpieza explícita cuando corresponda, sin purgar colas ajenas
> ni modificar correo/Meta. Guarda proveedor/correlación de los nuevos intentos.
> Credenciales por entorno; plantillas globales con permisos por acción; equipos
> son departamentos, no financieras. No implementes el catálogo de Integraciones,
> que tiene su tarea independiente, ni seguimiento/editor de incrementos posteriores.
> Publica la PR del incremento, ejecuta /review medium y resuelve hallazgos.
> Actualiza documentación, Agent Note y Notion; entrega prompt para I2.
> Una sesión por incremento. Al terminar I4 exige pase del rango completo y todas
> las PR contra ecb9d4a antes de cerrar la funcionalidad.

Si algún incremento añade tablas, clasificarlas también en el reinicio QA y mantener su
regresión en esa misma PR. I0 descubrió y corrigió ese faltante de la base anterior.

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
   jobs pendientes. La petición inicial de procedencia histórica queda sustituida
   por la confirmación del 25/11/2026: solo existen dev/QA y datos desechables.
   Hay referencias activas en varias capas; no mantener un proveedor de respaldo
   implícito ni desarrollar compatibilidad histórica innecesaria.
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

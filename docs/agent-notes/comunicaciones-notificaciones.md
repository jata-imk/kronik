# Comunicaciones y notificaciones

## Referencias

- [Tarea en Notion](https://app.notion.com/p/3fe61db7db7f81e4af50c4f0f3ab7e41), estado Doing.
- Destino autorizado: `feat/comunicaciones-notificaciones`, rama original de Bryan.
- I4: [PR #97](https://github.com/jata-imk/kronik/pull/97), rama `feat/comunicaciones-i4-cierre` desde I3 `82c7756`.
- PR: [#91 original](https://github.com/jata-imk/kronik/pull/91), [#93 I0](https://github.com/jata-imk/kronik/pull/93), [#94 I1](https://github.com/jata-imk/kronik/pull/94), [#95 I2](https://github.com/jata-imk/kronik/pull/95), [#96 I3](https://github.com/jata-imk/kronik/pull/96).
- [Plan de incrementos](../explanation/plan-incrementos-sms.md), [ADR 0049](../explanation/adr-0049-orquestacion-comunicaciones-externas.md), [ADR 0050](../explanation/adr-0050-proveedor-sms-labsmobile.md), [ADR 0051](../explanation/adr-0051-notificaciones-globales-y-previas-html.md).
- [Guía de VPS y variables](../how-to/preparar-comunicaciones-vps.md), [QA](../how-to/probar-comunicaciones.md), [seguimiento SMS](../reference/seguimiento-sms.md), [auditoría](../reference/auditoria-eventos.md).

## Estado — 27/11/2026

### Integración de main en curso — 27/11/2026

Rama `integracion/main-comunicaciones-2026-11-27` desde el traspaso local
`9d938590664d92a87aaa2fbe0c9a11445af896e5`, conservado sobre el destino remoto
`f0485cf1539a42800304da2068507d7450772856`. Main incorporado como origen:
`217aceff88e938b9037b8a11990916beb9fe4f19`; WhatsApp excluida y sin cambios,
`871936207889c0d429cd1d0fe9c0d546f2094997`. Refs revalidados mediante fetch.
El CI #318 aprueba únicamente `f0485cf`, no el resultado combinado.

Resolución conjunta: fechas civiles/reversos con eventos y proyecciones atómicos;
catálogo único de main ampliado con Comunicaciones/Notificaciones; guardas de
captura aplicadas a los formularios nuevos y conservadas en el editor documental;
calendario, permisos, auditoría, rutas, validación española y reinicio QA reunidos.
CI conserva ambos recorridos y los cinco harness MariaDB. ADR originales de
Comunicaciones 0047/0048 renumerados a 0052/0053, con contexto histórico y enlaces;
0047/0048 de main conservan su identidad. Sin nuevas API, migraciones o variables.

Código corregido: `09b3d53383ccc065bdac9eda56fc5cee1194ef68`, después del merge
`183cae5d0b571e85dc6458454b0c3edd48f6f1d0` con padres `9d93859` y `217acef`.
La integración aún no acredita publicación, despliegue ni QA externo. La
iniciativa sigue Doing y ambas barreras permanecen cerradas.

Primera revisión medium de `183cae5`, contra ambos padres y acumulado
`bfa0b87..183cae5`: dos P2. Se corrigen la clasificación de destinos del menú
contextual con lectura independiente y el desafío Meta normalizado por PHP,
con regresiones de renderizado/permisos y verificación sin efectos.
**Revisión medium final de `09b3d53`: sin defectos accionables pendientes**,
contra ambos padres y el acumulado `bfa0b87..09b3d53`, incluida la base
`ecb9d4a` y #91/#93–#97. Informes locales ignorados:
`storage/framework/testing/main-review-{final-,}result.txt`.
La primera publicación se interrumpió
durante el hook al detectar el cruce del menú, sin resultado global ni push;
no se considera una pasada aprobada.

La inspección visual de las capturas detectó después un solapamiento de campos
en escritorio con el sidebar de main. Se ajustan anchos mínimos y distribución
del panel de variables, con aserciones geométricas E2E a 1024/1280/1536 px.
La segunda publicación también se interrumpió durante el hook, sin push ni
resultado global. La corrección visual tiene frontend completo (225/82), build
y E2E geométrico (una aprobada, tres anchos) aprobados, con captura de escritorio
renovada y revisada. La repetición móvil de `3fa940f` también aprobó y su captura
se renovó e inspeccionó. La revisión adicional del CLI quedó sin resultado por
cuota; José autorizó delegar la revisión medium estática del resultado completo.
Esa revisión de `3fa940f`, contra ambos padres y el acumulado, terminó con un P2:
las aserciones de menús de main suponían una tabla vacía, aunque las migraciones
de Comunicaciones registran elementos. Sin otros defectos accionables, incluido
el ajuste visual. La revisión de `09b3d53` acredita el código anterior a ese ajuste.

La primera suite completa terminada produjo 1,208 aprobadas, dos omisiones
optativas, once fallos y 50,213 aserciones (579.70 s), sin push. Los once fallos
corresponden al P2: se corrigen los tres puntos de aserción comparando con el
conteo inicial y el cambio esperado, conservando la comprobación de ausencia de
escrituras/actividad. No se vacían tablas ni se eliminan pruebas. Focal de menús:
25 aprobadas / 194 aserciones. Revisión de la corrección y suite completa
corregida pendientes de terminar.

Evidencia local del código corregido:

- Focal de integración/Meta: 10 pruebas / 106 aserciones aprobadas.
- Frontend completo: 225 pruebas / 82 archivos y build aprobados; identidad
  del frontend comprobada entre `183cae5` probado y `09b3d53`.
- MariaDB 11 aislada `kronik_test`, puerto local 33317: instalación acumulada y
  los cinco harness (folios, auditoría, Bullet, Comunicaciones y calendario)
  aprobados. HTTP/correo falsos y configuración sintética de proceso.
- PDF real: 9,704 bytes. Cuatro guardas Node E2E aprobadas.
- E2E conjuntos de `09b3d53`: ocho aprobados en una pasada aislada (6.1 min), incluidos
  históricos, PDF multipágina, texto/HTML, móvil/teclado y descarte del borrador.
  Capturas del resultado combinado revisadas: [escritorio](../assets/notificaciones-main-desktop.png)
  y [móvil](../assets/notificaciones-main-mobile.png).
- Pint aprobado; UTF-8/LF, diff, 515 enlaces e índices/temas comprobados.
- Suite backend completa, E2E de navegación/calendario, PR y CI por SHA pendientes de terminar;
  no se atribuye a este código el CI #318 ni una pasada interrumpida.

El resto de esta nota conserva la evidencia histórica de I0–I4 y del traspaso.

**Cierre técnico I4 entregado y consolidado en la rama original de Bryan.** José autorizó consolidar #93–#96 e I4 en la
rama original, conservando ramas auxiliares. Sustituye el «sin merge» del
traspaso solo para ese destino. No incluye `main` ni la rama separada de Bryan
`feat/comunicaciones-whatsapp`. Sin despliegue ni validación externa.

Código final I4: `3f62b15f328afc8ae7b86fff13a41ea3d4612b80`, después de `5fe2d88`.
HEAD I4 publicado `ce6eabe306796ccfa2a85ca8b4f4038d476cec63`;
[CI #317 aprobado](https://github.com/jata-imk/kronik/actions/runs/46018325274),
todos los pasos, incluidos los siete E2E de Notificaciones/HTML/históricos.
[Revisión terminada](https://github.com/jata-imk/kronik/pull/97#issuecomment-7221167616)
y [resultado CI previo a integrar](https://github.com/jata-imk/kronik/pull/97#issuecomment-7221402717).
Pre-push completo repetido: **1,168 aprobadas, dos omitidas optativas y 49,778
aserciones**, duración 541.21 s; build Vite aprobado. Primera ejecución
interrumpida sin resultado global, no computada como aprobación.
El cierre posterior solo modifica documentos, con identidad funcional respecto
a `3f62b15` comprobada. Su publicación usa la excepción documental del hook
tras verificar UTF-8/LF, enlaces, índices y diff; conserva la pasada completa
anterior y requiere CI propio del HEAD final de la PR.

## Barrido conjunto y revisiones

Se revisó todo `ecb9d4a..3f62b15` y el antecedente original
`bfa0b87..ecb9d4a`, incluyendo #91 y las interacciones I0–I3. #91 continúa
cerrada sin integrar en main; su revisión inicial solicitada no acreditaba
resultado, por lo que se incluyó expresamente en el barrido I4.

| Incremento | Código revisado | HEAD previo a I4 / CI por SHA |
| --- | --- | --- |
| I0 / #93 | `3095384` | API PR `213a4b6`, [CI #309 aprobado](https://github.com/jata-imk/kronik/actions/runs/45646122948); ref Git real `9d0473d`, corrección documental sin CI propio |
| I1 / #94 | `cacf89f` | `85d2db6`, [CI #311 aprobado](https://github.com/jata-imk/kronik/actions/runs/45696230037) |
| I2 / #95 | `c7a31bd` | `2c865b1`, [CI #314 aprobado](https://github.com/jata-imk/kronik/actions/runs/45709799131) |
| I3 / #96 | `6a48765` | `82c7756`, [CI #316 aprobado](https://github.com/jata-imk/kronik/actions/runs/45895167209) |

Las revisiones medium terminadas constan en las PR; los cierres posteriores
solo cambian documentación, con identidad funcional comprobada. Sin hilos
pendientes al retomar. La discrepancia API/ref de I0 fue histórica: después de
consolidar, GitHub muestra `9d0473d` y #93 integrada. Ese HEAD es ancestro de
I1–I4 y su corrección documental queda conservada.

Revisión medium acumulada inicial de `82c7756`: cuatro hallazgos. Revisión de
`5fe2d88`: seis correcciones confirmadas y un hallazgo adicional de bloqueos.
**Revisión final de `3f62b15`, Codex CLI 0.172.0, medium: sin otros defectos
accionables, todos los hallazgos resueltos.** Revisión estática, sin ejecutar
PHP/pruebas concurrentes. Los informes ignorados están en
`storage/framework/testing/i4-review-{inicial,final,release}-result.txt`.

## Correcciones I4

- Serializar el cupo diario por cliente entre créditos/canales y contar el día
  en la zona de los timestamps almacenados, incluida la madrugada no UTC.
- Abrir la lectura consistente después de bloquear crédito/cliente para observar
  reversos confirmados antes de reservar el intento. Orden común crédito →
  plantilla → regla → cliente → salida; preparación también bloquea crédito
  primero y revalida el evento, evitando inversión por la FK.
- Derivar `effective_status` del consentimiento con el contacto actual: vigente,
  contacto cambiado o revocado, sin exponer hash/evidencia ni mutar al consultar.
- Serializar las lecturas de campana, deduplicar clics y descartar respuestas o
  trabajos tardíos tras cambiar contexto; el resumen no recupera no leídos.
- Revertir la FK I3 con nombre MariaDB y columnas SQLite; reinstalar I1–I3 sin
  perder correo/Meta ni duplicar permisos.

Son siete defectos corregidos, con regresiones. No se crean acciones auditables
nuevas: se mantienen eventos, actor/iniciador, contexto original y privacidad.
El [contrato de canales](../reference/canales-comunicacion.md) y la matriz
explican las transacciones y la ausencia justificada de eventos adicionales.
CI incluye ahora el harness de concurrencia de comunicaciones y el push al destino.

## Evidencia local del código final

- Focal backend: 25 pruebas / 545 aserciones; casos posteriores incluidos en la suite completa.
- Frontend completo: 188 pruebas / 71 archivos; build Vite aprobado.
- Harness MariaDB 11 aislada: cupo compartido entre créditos/canales, reverso
  confirmado antes del intento y cruce prepare/send sin deadlock, aprobados.
  HTTP/Mail falsos, BD `kronik_test`, sin tráfico de proveedor.
- Esquema: ciclo SQLite aislado de reversión/reinstalación I1–I3 aprobado,
  preservando filas correo/Meta y sin duplicar módulos/permisos.
- E2E: primera pasada conjunta, seis aprobados y uno fallido en política por
  espera anterior al POST/redirección. Se conservaron traza, captura y vídeo;
  se añadió espera de respuesta y mocks/consola compartidos sin omitir pasos.
  Repetición aislada de política: una aprobada. No se presenta la pasada fallida
  como aprobación global. CI #317 repitió y aprobó los siete sobre `ce6eabe`.
- PDF real: 9,704 bytes. Guardas Node E2E: cuatro aprobadas. Pint, diff --check,
  UTF-8/LF, enlaces locales, índices y cobertura temática comprobados.
- Pruebas PHP secuenciales por checkout; revisión estática en paralelo sin PHP.
  No se modificó `.env`, ni se ejecutaron migraciones o limpieza operativas.

## Integración en la rama original

Los merges conservan cada HEAD y su autoría, sin rebase/squash ni borrado de ramas:

| PR | HEAD incorporado | Merge en original |
| --- | --- | --- |
| #93 / I0 | `9d0473d` real, incluye `213a4b6` | `02a5c669e8dcef4d03c316ced87b69e78b766087` |
| #94 / I1 | `85d2db6` | `be75a625d89931e7b68362baa9275367535f5e54` |
| #95 / I2 | `2c865b1` | `d0f6ac3a78b7a03229e5e90ebd7104b16ab275b9` |
| #96 / I3 | `82c7756` | `7a89da34fd7a1a01cb6dc850dce74ba82a2a57b0` |
| #97 / I4 | `ce6eabe` | `13f33272f52c6ea6f5089773e3da31f844f43af7` |

La API rechazó #93 con 409 tanto para su HEAD publicado como para el ref real,
con mensajes de rama desactualizada/modificada. Se volvió a comprobar que el
destino seguía `547d9e5` y se consolidaron secuencialmente los cinco HEAD mediante
Git. El árbol completo de `13f3327` es **idéntico** al de I4 aprobado `ce6eabe`
(`0a8b3bebdd40207f09d2b51aec9bb24ad216fbfe`), incluida la corrección documental
I0. No hay resolución de conflictos ni modificaciones funcionales al integrar.

Este cierre documental solo actualiza la nota. La publicación conserva el código
`3f62b15` probado/revisado y usa la excepción del hook después de comprobar esa
identidad y los enlaces. La confirmación de refs/PR y el CI por SHA del HEAD final
del destino se registran en la tarea Notion y el comentario de cierre de #97,
sin atribuir a ese SHA el CI #317 de otra revisión. El propio historial de esta
nota identifica su commit documental; no requiere escribir su SHA dentro de sí.

## Límites de integración y despliegue

Antes de consolidar: destino `547d9e5`, main `217acef`, ancestro común `bfa0b87`;
main/I3 divergían 89/32 commits. Main conserva cambios independientes de pagos,
navegación, permisos, auditoría, rutas, editor, CI e índices. Hay colisión de
numeración ADR 0047/0048. No se acredita ejecución combinada con main. El
[plan](../explanation/plan-incrementos-sms.md#compatibilidad-observada-en-i0)
registra los cruces que una integración posterior debe conservar.

La rama WhatsApp separada sigue `8719362`; su preparación de piloto no forma
parte de I4. Si la VPS usa main, evaluar integración antes de cambiar ramas.
El correo reutiliza el mailer Laravel existente: si la VPS usa SMTP ZeptoMail,
usa sus mismas credenciales `MAIL_*`, con remitente autorizado. Esto describe
el código; no confirma los secretos ni la ejecución de la VPS.

## Pendientes reales y siguiente paso

I4 está cerrado técnicamente. José confirmó preparar una nueva sesión para
**integrar main en la rama original de Comunicaciones**, conservando ambos
trabajos; no ejecutar esa integración durante este traspaso. Después se podrá
preparar la validación VPS según la guía, con rama/SHA reales del servidor.

Después del cierre técnico quedan recepción SMS externa autenticada,
cuenta/ruta mexicana/remitente/tarifas, presupuesto y piloto con números aprobados.
I2 solo acredita núcleo interno sintético; no hay endpoint SMS, ackurl, firma o
consulta inventados. No hay cuenta/saldo LabsMobile confirmados por José.
Ambas barreras permanecen cerradas; sin envíos reales, compras, Integraciones,
limpieza operativa, despliegue, SSH ni acceso a VPS. La iniciativa permanece
Doing y no se cierra por integrar PR. El usuario ejecutará los comandos de la
guía de VPS y compartirá sus resultados para registrar el QA posterior.

## Traspaso — integrar main en Comunicaciones, 27/11/2026

Esta sesión solo prepara el traspaso. No se ha ejecutado merge de main, modificado
código funcional ni iniciado despliegue. La integración siguiente está pendiente;
el CI de I4 no acredita todavía una ejecución combinada con main.

Estado comprobado al preparar el traspaso:

- Remoto original: `f0485cf1539a42800304da2068507d7450772856`,
  [CI #318 aprobado](https://github.com/jata-imk/kronik/actions/runs/46020691744).
- Main: `217aceff88e938b9037b8a11990916beb9fe4f19`. Ancestro común `bfa0b87`;
  89 commits exclusivos de main y 41 de Comunicaciones, antes del commit
  exclusivamente documental de este traspaso. No son ramas en una sola línea.
- WhatsApp separada: `871936207889c0d429cd1d0fe9c0d546f2094997`, excluida.
- #93–#97 integradas en original; #91 cerrada sin integrar en main. La evidencia
  de revisión/CI consolidada está en el
  [comentario final de #97](https://github.com/jata-imk/kronik/pull/97#issuecomment-7221737172).
- El commit local de este traspaso solo cambia esta nota y no se publica en esta
  sesión. Preservarlo al crear la rama de integración; no atribuirle CI propio.
  Revalidar HEAD locales/remotos, PR, revisiones e integración al retomar.

### Siguiente sesión: integrar main en la rama original de Bryan

> Continuemos Kronik con la integración de main en Comunicaciones.
>
> Worktree: C:\Users\jose.tejero\Documents\Proyectos\kronik\storage\framework\testing\comunicaciones-worktree
>
> Lee AGENTS.md y ejecuta este traspaso de
> docs/agent-notes/comunicaciones-notificaciones.md. Consulta docs/index.md,
> la tarea Notion 3fe61db7db7f81e4af50c4f0f3ab7e41, el plan de incrementos,
> contratos de canales/seguimiento SMS, Notificaciones, consulta/históricos,
> auditoría, tablero financiero y renderizado documental.
>
> Objetivo autorizado: incorporar los cambios vigentes de origin/main en
> feat/comunicaciones-notificaciones, la rama original de Bryan, conservando
> íntegramente el trabajo de main y Comunicaciones I0–I4. No incorporar
> feat/comunicaciones-whatsapp ni otras ramas de trabajo. No integrar
> Comunicaciones en main, reabrir #91, reescribir historia ni borrar ramas.
>
> Primero verifica estado limpio, refs reales y cualquier cambio posterior;
> actualiza refs remotos sin sobrescribir trabajo local. Preserva el commit
> documental de este traspaso aunque esté por delante del remoto. Referencia
> funcional de Comunicaciones: f0485cf, CI #318 aprobado; main al traspasar:
> 217acef. Si han avanzado, inspecciona y registra el alcance vigente. No uses
> el CI anterior para acreditar la combinación pendiente.
>
> Crea una rama temporal de integración desde la original con el traspaso y
> fusiona origin/main mediante merge, sin rebase/squash ni force push. Revisa
> la divergencia completa y las interacciones, no solo conflictos textuales.
> Conserva ambas implementaciones al resolver cada cruce, sin sustituir
> archivos completos por una rama ni eliminar pruebas para hacerlos pasar.
>
> Revisar especialmente PagoService y fechas civiles/reversos, transacciones y
> ProyeccionCreditoService, eventos de comunicación y contexto original,
> cupos/bloqueos I4, navegación/catálogos/seeders/permisos por equipo, rutas y
> mensajes españoles, enum/matriz de auditoría, editor documental/previas HTML
> y PDF explícito, históricos/versiones/consulta inmutable, migraciones y
> rollback/reinicio QA, CI e índices. Resolver la colisión de numeración ADR
> 0047/0048 conservando las decisiones históricas y todos los enlaces.
>
> Validar contra ambos padres y la base funcional ecb9d4a: demostrar que se
> mantienen los cambios de main y toda la iniciativa #91/#93–#97. Ejecutar
> regresiones específicas de los cruces, suite backend completa, frontend/build,
> harness MariaDB de concurrencia, PDF real y E2E aplicables en BD/HTTP aislados.
> Un único proceso PHP de pruebas por checkout, también durante review.
> Revisar trazas/capturas/vídeo/consola ante fallos; no presentar pasadas fallidas
> o CI de otro SHA como aprobaciones del resultado final.
>
> Publicar una PR de integración hacia feat/comunicaciones-notificaciones,
> completar /review medium de todo el resultado, resolver hallazgos y comprobar
> CI del HEAD final. Integrarla en la rama original después de esas validaciones,
> conservar todas las ramas y verificar también el CI del destino consolidado.
> No mover main. Si aparecen decisiones de producto no resueltas, documentarlas
> y preguntar solo por ellas, continuando el trabajo independiente posible.
>
> Mantener ambas barreras cerradas, credenciales de correo existentes y flujo
> Meta; no añadir recepción SMS/ackurl/firmas provisionales ni declarar completo
> I2 externo. Sin envíos reales, compras, Integraciones, limpieza operativa,
> cambios de .env, despliegue, SSH o acceso a VPS. No habilitar SIC productivo
> ni operaciones con dinero real. El usuario conserva la administración de VPS.
>
> Actualizar documentación/índices, matriz de auditoría, esta Agent Note, guía
> de VPS y Notion. La guía deberá reflejar la integración efectivamente validada;
> no conservar como pendiente un cruce ya resuelto ni prometer despliegue.
> Separar integrado en original, integrado en main, desplegado y validado
> externamente. La iniciativa sigue Doing mientras subsistan los pendientes
> externos de SMS/cuenta/ruta/piloto. Entregar SHA/PR/CI finales y pendientes.

# Core crediticio extensible

## Retoma documental de PR #65 — 13/11/2026

El usuario autorizó integrar las PR abiertas en orden de publicación. PR #65
se actualiza contra main `67bba2c`, conservando las decisiones posteriores y la
implementación Bullet ya integrada en #73. El cuestionario del 01/11 es histórico:
no se reabren anticipo, mora ni reducción de plazo como decisiones bloqueantes.
Se conserva la evidencia de integración de #64 en `ee94025`; los estados de
despliegue/QA posteriores no se sustituyen por el antiguo «sin despliegue».
Actualización exclusivamente documental, sin acciones auditables adicionales.
Integrada mediante PR #65 en `4f03503`, con review medium de `f387dae` sin
hallazgos y [CI aprobada](https://github.com/jata-imk/kronik/actions/runs/43559039072).

## Cierre técnico y retoma — 07/11/2026

Retoma autorizada desde `54d27f4`, rama `feat/bullet-base`, worktree
`output/bullet-base/worktree`, [PR #73](https://github.com/jata-imk/kronik/pull/73).
La pausa queda levantada. #74 está consolidada en #73; base funcional del
barrido conjunto: main `c30c941`. Los cuatro archivos locales de auditoría de
main conservan sus hashes originales y ese checkout permanece intacto.

[CI de `54d27f4` aprobada](https://github.com/jata-imk/kronik/actions/runs/42429965010),
comprobada al retomar: frontend/build, backend con 46151 aserciones, concurrencia
de folios/auditoría y ocho competencias financieras por política en MariaDB,
renderer PDF real, tres E2E Bullet/Productos y regresión dual. CI conserva
advertencias de backend; los conteos locales de aprobadas/omitidas corresponden
a su entorno, no se atribuyen al runner Linux. El código no cambió en la retoma.

Review medium y barrido acumulado finales de `54d27f4` contra `c30c941`, mediante
`codex review --base c30c941 -c model_reasoning_effort=medium`:
sin regresiones accionables. El revisor verificó 156 pruebas backend y nueve
frontend aprobadas; selecciones superpuestas con la suite. La concurrencia y
E2E se acreditan por la CI citada, no por esa selección. Log local:
`storage/framework/testing/bullet-ciclo-review-retoma-final.log`.

La documentación conserva los contratos, auditoría, capturas y guía operativa;
este cierre solo actualiza estado y evidencia. Review medium documental sin
hallazgos; 268 enlaces locales en 22 documentos sin roturas, con índices y
cobertura temática verificados. Integración de la entrega en
[PR #73](https://github.com/jata-imk/kronik/pull/73): su estado, SHA y fecha se
registran en GitHub y en el [traspaso de Notion](https://app.notion.com/p/40861db7db7f81b2b039d32eabe2daf3).
Integrar requiere CI aprobada del head documental final y revisión resuelta.
No confundir esa integración con despliegue ni aceptación QA compartida.

El siguiente paso del usuario tras la integración es desplegar y comprobar
ambas políticas con el [guion QA](../how-to/qa-bullet.md). Registrar commit,
entorno/navegador, fecha, políticas, folios sintéticos y resultado en esta nota
y Notion. Sin despliegue ni aceptación compartida reportados; Bullet integral
permanece abierto. Definiciones moratorias conservadas; diaria, capitalización
y reducción de plazo Bullet siguen fuera de alcance, al igual que SIC
productivo y dinero real.


## Habilitación y validación integral Bullet — 07/11/2026

Entrega en `output/bullet-base/worktree`, rama `feat/bullet-base`,
[PR #73](https://github.com/jata-imk/kronik/pull/73), retomada desde `1a495aa`.
Código de habilitación `558b06a`; ajustes del harness hasta `fc1fe03`.
#74 está consolidada en #73. Base funcional del barrido completo: main `c30c941`.
Los cuatro archivos locales de auditoría de main conservan sus hashes originales;
no pertenecen a esta entrega ni se actualiza ese checkout.

Motor 2/Act/360 habilita ambas políticas aprobadas. Simulación/CAT aplican
exigibilidad antes de serializar; solicitud, paquete, firma y desembolso conservan
condiciones y hashes. Resumen, ayudas y PDF distinguen devengado pendiente,
aún no exigible y vencido. Anticipo conserva fecha e interés previo; liquidación
y reversos mantienen historia. Disparador, tres bases moratorias, gracia y
ambos/sustitución conservados. ADR 0042 sustituye el bloqueo provisional.
Sin migraciones adicionales a las dos columnas nullable de ADR 0039, permisos,
seeders operativos ni nuevas banderas de aplicación.

Evidencia local hasta `fc1fe03`: 856 backend aprobadas, una omitida preexistente,
46151 aserciones; 148 frontend/56 archivos y build aprobados. El hook serial
repitió backend/build sin omitir controles. E2E Chromium de ambas políticas,
Productos Bullet y regresión dual aprobados en SQLite dedicada, con PDF real y
sin proveedores. Reloj exclusivo del harness aislado y CSRF activo; QA compartido
conserva fechas reales. Capturas de escritorio/móvil en la guía.

Review medium del incremento desde `1a495aa` sin defectos accionables; el helper
de Productos se centralizó para permitir selecciones por archivo. Barrido/review
medium acumulado contra `c30c941`, head `b14a2a7`, sin regresiones accionables:
156 pruebas Bullet/2349 aserciones verificadas por el revisor, superpuestas con
la suite. Ajustes posteriores `807b5c1` y `fc1fe03` pasaron review medium propio
sin hallazgos. Compartir discos simulados entre dos ejecuciones Pest causó
colisiones; las selecciones pasaron al repetirse por separado. Pint, diff-check,
UTF-8/LF y navegación temática/enlaces revisados.

[CI de `807b5c1`](https://github.com/jata-imk/kronik/actions/runs/42410676522)
aprueba ocho competencias financieras por política en MariaDB con procesos,
conexiones y barreras independientes: desembolsos, idempotencia, referencias,
previas competidoras, reversos y rollback de pagos/calendarios/actividad.
La suite SQLite no sustituye esa evidencia. El harness conserva instante y
zona IANA del reloj, con nacimiento en julio de 2002 para detectar diferencias
históricas de horario de verano en los casts/huellas. Verifica desembolso sobre
Solicitud y pagos/reversos sobre Crédito, con actor/equipo/sucursal y privacidad.
No se cambiaron emisores ni reglas financieras. Consulta, cálculo y previa no
agregan eventos; matriz y contratos actualizados.

Diagnósticos E2E: devolución de recursos estáticos del router, sesiones fechadas
en la base aislada y espera del resultado de cada dictamen. El oráculo final con
ambos es 380.48 - 100 + 5.92 = 286.40; se precisó la aserción sin cambiar el
motor. CI `807b5c1` se detuvo con HTTP 500 al preparar el paquete; traza, captura,
vídeo y consola revisados. `fc1fe03` añade comprobación directa del renderer y
selecciona el Chrome del runner con su sandbox. PDF sintético por renderer real
aprobado localmente (8586 bytes), sin datos de dominio ni escrituras a la base.
El ciclo no reintenta sobre versiones ya activadas: repetir exige base nueva.

Verificaciones Linux por commit en la [CI de `fc1fe03`](https://github.com/jata-imk/kronik/actions/runs/42429637787)
y checks del head documental de [PR #73](https://github.com/jata-imk/kronik/pull/73).
Integrar exige CI completa aprobada; estado y SHA de integración se conservan en
esa PR y en el [registro de implementación de Notion](https://app.notion.com/p/40861db7db7f81b2b039d32eabe2daf3).
No hay despliegue ni aceptación compartida reportados. El usuario realiza el
despliegue y la comprobación con el [guion QA](../how-to/qa-bullet.md).
Siguiente paso tras el merge: registrar commit desplegado, entorno, navegador,
políticas, folios sintéticos y resultados del usuario en Notion y esta nota.
Bullet integral sigue abierto hasta esa aceptación. Diaria, capitalización y
reducción de plazo Bullet siguen fuera de alcance; tampoco se habilitan SIC
productivo ni dinero real.

Las secciones inferiores conservan evidencia histórica; su bloqueo operativo
queda sustituido por esta habilitación, sin atribuirles pruebas nuevas.


## Anticipo y liquidación/reverso Bullet internos — 06/11/2026

Retoma autorizada desde `6c64a3e`, worktree `output/bullet-base/worktree`, rama
`feat/bullet-base`, [PR #73](https://github.com/jata-imk/kronik/pull/73). Agent Note
y definición/implementación de Notion leídas. El usuario confirma limitar este
incremento a validación interna; activación, simulación y originación permanecen
bloqueadas. No se reabren disparador, bases moratorias, gracia ni sustitución.
Los cuatro archivos locales de auditoría de main se preservan por hash.

Implementado internamente: anticipo que cubre exigibles y cargos aplicables sin
cobrar el devengado no exigible, conservación de claves/numeradores/vencimientos
y contadores, capital inicial y gracia. El cronograma versionado proyecta nuevo
saldo desde el día siguiente, con redondeo por obligación. Liquidación exacta sin
interés futuro; reversos compensatorios y reapertura mediante el servicio actual.
Sin nuevas migraciones, variables, seeders, permisos, endpoints, dependencias ni UI.

ADR 0041 complementa ADR 0023/0040. Contrato, casos, matriz, índices y tema del
core actualizados. Las pruebas de persistencia usan evidencia sintética solo
en BD aislada, sin habilitar originación ni acreditar concurrencia MariaDB.
Se conservan los eventos de pago/reverso; cálculo y consulta no añaden actividad.

Validación final: `php artisan test --compact`, 847 aprobadas, una omitida y
45547 aserciones. Build y E2E `solicitudes-dual.spec.js` aprobados; el E2E usa
SQLite dedicada y cubre PDF, desembolso, pago/reverso, anticipo, liquidación y
reapertura de métodos existentes. Sin BD operativa ni proveedores. Pint de cuatro
archivos, diff-check y 155 enlaces locales en 11 documentos aprobados, UTF-8/LF.
Advertencias existentes de recursos, Browserslist y chunks en el build.

Review medium contra la base `6c64a3e` mediante `codex review --uncommitted -c
model_reasoning_effort=medium`: sin hallazgos accionables; verificó 101 unitarias
y 12 de integración, 1426 aserciones (superpuestas con la suite, no sumar).
Prueban ambas políticas, tres bases, gracia/sustitución, anticipos repetidos,
exigibles, impuestos/comisiones, bisiestos, centavos, liquidación vencida,
reversos, reintento, previa obsoleta, permisos y auditoría sin datos reservados.
Publicación con hook completo; resultado de CI en los checks de PR #73.

Diagnósticos resueltos: relación inexistente en un test corregida para consultar
movimientos del crédito; mora por obligación precisada a 15.28 por redondeo
individual de cargos (6.23 + 9.05), sin cambiar el motor moratorio. La primera
ejecución E2E quedó en PDF por límite de 30 segundos del servidor: Chrome tampoco
iniciaba en la prueba aislada dentro del sandbox. Traza, capturas, vídeo y consola
revisados; repetición fuera del sandbox aprobada, sin cambios al código ni al
test. Review también requirió red fuera del sandbox. Base previa: 91/441 aprobadas.

Siguiente entrega: habilitación y validación del ciclo completo, concurrencia
financiera MariaDB y barrido acumulado antes de integrar. Sin integración en main,
despliegue ni cierre integral. Las secciones inferiores conservan evidencia histórica;
sus pendientes de anticipo/liquidación quedan sustituidos por este incremento.

## Obligaciones y mora Bullet — 05/11/2026

Continuación en `output/bullet-base/worktree`, rama `feat/bullet-base` y
[PR #73](https://github.com/jata-imk/kronik/pull/73). Base de este incremento
`ccea858`; Git limpio/sincronizado y [CI de entrada aprobada](https://github.com/jata-imk/kronik/actions/runs/42161579303).
Notion leído: precisión inicial del 05/11 prevalece sobre preguntas históricas.
No se reabren disparador ni tres bases moratorias. Los cuatro archivos locales
de auditoría en main conservan sus cambios; no se reabre #72.

Implementado internamente: obligación periódica/final de interés, atraso de
interés con capital futuro, tres bases sin duplicar las globales, gracia A/B y
ambos/sustitución. Se preservan numeradores y base diaria anterior/posterior al
pago; la sustitución retroactiva retira ordinario provisional del cargo y las
bases diferidas correctos, incluso si la gracia cruza otro vencimiento.
El recorrido histórico permanece separado por método exacto. ADR 0040 y contrato
del core documentan la evidencia nueva del estado, sin tablas nuevas.

`BulletPagosTest` usa evidencia sintética inyectada solo en BD de pruebas para
verificar persistencia cifrada, previa/consulta sin escritura, reintento y reverso
compensatorio con actor/sujeto/equipo/sucursal. Conserva los emisores reales de
pago y reverso, sin eventos adicionales por cálculo ni metadatos sensibles.
Matriz actualizada. No acredita originación Bullet ni concurrencia financiera.

Validación final: `php artisan test --compact`, 794 aprobadas, una omitida y
44565 aserciones; build aprobado. E2E `solicitudes-dual.spec.js` aprobado en
SQLite dedicada: contrato/PDF, desembolso, pago/reverso, anticipo, liquidación y
reapertura de los métodos existentes. No se usó BD operativa ni proveedores.
Selección de regresión previa 213/1932 y selección final 60/444 (superpuestas,
no sumar). Pint de cuatro archivos y diff-check aprobados; siete documentos y
87 enlaces locales sin roturas, UTF-8/LF. Advertencias de chunks/recursos del
build ya existentes; sin cambios de dependencias ni frontend.

Una selección inicial omitió el archivo que define `productoPayload`, helper
existente: repetición incluyendo Productos aprobada. El fixture sintético de
integración necesitaba formalización después de revisar firma; corregido antes
de las tres pruebas aprobadas. La primera revisión
medium encontró P2: el residuo exclusivo de impuesto podía activar mora global
y sustitución. Corregido el disparador a capital/interés pendientes; seis casos
de centavos prueban las tres bases con ambos/sustitución. Selección final de
60 pruebas/444 aserciones aprobada. Review medium final mediante `codex review
--uncommitted -c model_reasoning_effort=medium -c sandbox_mode=danger-full-access`,
base `ccea858`: sin hallazgos accionables; verificó las 57 unitarias y tres de
integración (selección superpuesta). Código y CI vigentes en PR #73; publicación
con hook pre-push completo, sin omitir controles. No corresponde aún el barrido
de cierre integral de Bullet.

Sin nuevas migraciones, seeders, variables, permisos o UI en este incremento.
La migración de condiciones ya incluida en la PR conserva su propio alcance.
Activación, simulación y originación siguen bloqueadas; `MotorAnticipos` rechaza
Bullet expresamente. Siguiente incremento: anticipo que conserve cargo,
numerador, contabilización y vencimiento del devengado no exigible; liquidación,
reverso y posterior habilitación probada. Diaria/capitalización siguen separadas.
Sin integración en main, despliegue ni cierre integral de Bullet.

## Rama y PR únicas — 05/11/2026

El usuario pide publicar los incrementos e integrarlos en main, o mantenerlos
concentrados en una misma rama y PR. Para continuar Bullet se adopta una única
rama `feat/bullet-base` y [PR #73](https://github.com/jata-imk/kronik/pull/73),
contra main. Los siguientes incrementos se publicarán en esta PR; no abrir una
cadena adicional por cada incremento. La integración en main se realizará con
pruebas/CI, review medium y barrido acumulado aprobados, sin pedir de nuevo la
autorización de publicación/integración ya expresada por el usuario.

[PR #74](https://github.com/jata-imk/kronik/pull/74) integrada en esa rama mediante
`ea68771`, conservando historial. El árbol del merge es idéntico al head aprobado
`324944d` (`5cbd616f35bd6bb3d800db7cf34f142bfac92fd7`): se mantienen las pruebas
y reviews ya registradas, sin presentarlas como ejecuciones nuevas. La CI de
`324944d` fue comprobada aprobada antes de integrar. El estado vigente de la CI
de la rama unificada se consulta en PR #73. El worktree de trabajo sigue en
`output/bullet-base/worktree`, ahora sobre `feat/bullet-base`.

No se ha integrado Bullet en main ni desplegado. Los cambios locales de auditoría
de main permanecen intactos. Las secciones inferiores conservan el historial de
las entregas; su organización anterior en dos PR queda sustituida por esta.

El incremento de obligaciones/mora se registra arriba. Después sigue anticipo que conserve
el devengado no exigible, liquidación/reverso y habilitación probada del ciclo.
Diaria y capitalización siguen como ampliaciones separadas. No reabrir definiciones
funcionales aceptadas ni cerrar Bullet por esta consolidación.

## Condiciones Bullet — 05/11/2026

Continuación autorizada desde PR #73, head `6922fb2`, CI aprobada:
[run 42149395120](https://github.com/jata-imk/kronik/actions/runs/42149395120).
Git remoto comprobado; PR #73 abierta y sin integrar. Rama `feat/bullet-condiciones`
sobre ese head, en el mismo worktree `output/bullet-base/worktree`. Publicado en [PR #74](https://github.com/jata-imk/kronik/pull/74), borrador contra
`feat/bullet-base`; código `c8a916f`, sin integrar ni desplegar. El resultado
vigente de CI y su head se consultan en la PR. Los cambios
locales de auditoría en main permanecen intactos; no se reabre #72 ni la definición
ya aceptada de las tres bases/disparador de mora.

Implementado: condiciones por versión, copia, catálogo de configuración separado
del operativo y campos en el editor existente. Bullet exige ambas selecciones
explícitas (política y base moratoria), solo ordinario sobre saldo insoluto y
prepago `conservar_fecha`. El editor muestra bases efectivas y limpia/avisa ante
cambios incompatibles. En esta etapa no mezcla Bullet con métodos anteriores.
Consulta conserva valores originales, sin aplicar defaults ni mutaciones.

Migración aditiva nullable `2026_11_05_220000_add_bullet_conditions_to_product_rules`,
sin backfill. No aplicada a BD operativa. Sin seeders, variables o permisos nuevos.
Activación/programación, simulación y uso permanecen bloqueados en backend y UI
hasta integrar obligaciones, anticipos y mora. No se habilita diaria/capitalización.
ADR 0039 y referencias/matriz revisados: los eventos existentes de Productos
cubren cambios; resolución y consulta no añaden actividad.

Validación de publicación del código `c8a916f`: hook pre-push sin omitir controles,
734 backend aprobadas, una omitida y 44121 aserciones, más build aprobado.
Selección final de 31 pruebas/338 aserciones aprobada. Frontend final: 145 pruebas en 55 archivos.
Build y cinco E2E de Productos, incluido Bullet/escritorio/móvil, aprobados en
SQLite dedicada; no se usó BD operativa ni proveedores externos. Pint,
`git diff --check` y 141 enlaces locales en 11 documentos sin roturas.

Review medium final mediante `codex review --uncommitted -c
model_reasoning_effort=medium -c sandbox_mode=danger-full-access`, base `6922fb2`:
sin hallazgos accionables; revisor verificó 31/338 y 70/535 backend (selecciones
superpuestas), más 11 pruebas frontend. Primera revisión sin hallazgos, con
frontend limitado por permisos del proceso hijo; repetición final con acceso
local completada. Este incremento no cierra Bullet, por lo que el barrido de
cierre acumulado de la funcionalidad sigue pendiente.

E2E detectó etiquetas accesibles ausentes y un acceso al simulador desde el
encabezado; corregidos con regresión. Dos pruebas existentes ahora seleccionan
su producto sintético por nombre accesible al coexistir el nuevo borrador Bullet.
Se revisaron diagnósticos de los fallos; últimas cinco aprobadas. Hubo una
interrupción del entorno y corridas incompletas, que no cuentan como evidencia
aprobada. Capturas conservadas en `output/bullet-base/capturas-condiciones/`.
No equivale a integración, despliegue ni cierre de Bullet. Siguiente incremento: obligaciones y mora por
remanente impagado, preservación de devengado por anticipo, ciclo financiero y
posterior habilitación probada; luego validación transversal y barrido acumulado.

## Bullet base — incremento independiente, 05/11/2026

Precisión posterior durante implementación: José confirma obligación vencida
impagada como disparador, incluidas obligaciones de interés con capital no vencido,
y las tres bases moratorias (saldo insoluto, capital inicial, remanente de obligación
vencida). La consulta funcional de activación queda atendida; falta implementación
y compatibilidad probada. No atribuir validación jurídica al agente ni habilitar
bases ordinarias nuevas por inferencia. Esto actualiza los pendientes históricos
de mora descritos abajo y en documentos anteriores.

Rama `feat/bullet-base`, worktree `output/bullet-base/worktree`, base
`c30c941`. Se preservan los cambios locales de auditoría en main; #72 está
completada, desplegada y validada en QA por el usuario. No reabrir esa entrega.

Publicado en [PR #73](https://github.com/jata-imk/kronik/pull/73), código
`3d5a74e`. [CI del código aprobada](https://github.com/jata-imk/kronik/actions/runs/42147415145).
Review medium del commit publicado sin hallazgos; 41 pruebas/398 aserciones
dirigidas verificadas por el revisor. Sin integración ni despliegue.

Notion vigente del 05/11 sustituye preguntas históricas: anticipo parcial conserva
vencimiento e interés devengado no exigible; el saldo menor genera interés desde
el día siguiente. Caso 12000/120/3120: saldo 8880 y 120 pendientes. Reducción de
plazo fuera de Bullet base; diaria y capitalización separadas. Las tres bases y el disparador de mora están aclarados; quedan implementación
y compatibilidad operativa probada, sin reabrir consultas.

Implementación local inicial: estrategia Bullet, guardas por estrategia y
proyección pura de intereses periódicos/al vencimiento con numeradores.
No se publica Bullet en el catálogo operativo. Sin migraciones, UI ni cambios
a pagos: el resto del plan sigue pendiente. No equivale a Bullet integral.

Siguientes incrementos: datos/capacidades/editor, obligaciones y anticipo con
preservación de cargo/numerador/contabilización; habilitación tras cerrar mora;
E2E, concurrencia y barrido acumulado. PR #65 documental sigue abierta:
reconciliar con esta base vigente antes de integrarla; no adoptar su cuestionario
histórico ni duplicar su cierre de catálogo. Pruebas y revisión de este incremento
se registran al finalizar; no reutilizar conteos históricos como ejecución nueva.

Validación local del incremento: 715 backend aprobadas, una omitida y 43834
aserciones, 140 frontend/54 archivos, build y cuatro E2E de Productos aprobados.
La suite inicial falló por falta de manifest Vite en el worktree nuevo; se generó
el build y se repitió completa. Sin BD operativa ni proveedores externos.
Review medium contra c30c941 sin hallazgos accionables; revisor verificó
41 pruebas/398 aserciones de amortización e integridad. Sin integración/despliegue.

## Referencias

- Notion: [iniciativa y roadmap](https://app.notion.com/p/40961db7db7f819a8af1d7c3cfec2df7).
- Rama: `feat/core-crediticio-extensible`, base main `9106a6e`.
- Incremento de convenciones: `feat/core-convencion-dias`, base `c815f62` (PR #59
  abierta al retomar el 31/10/2026); [PR #60](https://github.com/jata-imk/kronik/pull/60),
  código `70de5c3`. Ambas entregas ya integradas en `main`.
- PR: [#59](https://github.com/jata-imk/kronik/pull/59).
- Código del incremento: `78de880`, base funcional/revisada `9106a6e`.
- ADR: 0032; relacionados 0006, 0012, 0020 y 0023.

## Objetivo

Consolidar integridad y responsabilidades reutilizables para Bullet, Revolvente y
Nómina. Alcance/calendario en Notion; contratos en la referencia del core.

## Estado actual — 04/11/2026

La tarea de [integridad matemática y compatibilidad histórica](https://app.notion.com/p/40961db7db7f815b919dc414dd1cb9a2)
queda cerrada por confirmación del usuario: revisión satisfactoria y cartera de
uno o dos clientes sin crédito activo o de prueba, sin tratamiento correctivo
requerido. Esta confirmación atiende el pendiente histórico de PR #59/#60; no es
una inspección nueva de BD realizada por el agente ni una corrección de saldos.
La arquitectura amplia, Bullet y las capacidades futuras permanecen independientes.

## Antecedentes de implementación

- 31/10/2026: incremento autorizado de catálogo backend/Vue implementado en
  `feat/catalogo-capacidades-credito`, base `d002037`. Catálogo de código,
  compatibilidad explícita por motor y opciones desde snapshot; Vue consume
  etiquetas/ayudas en Productos y Solicitudes. ADR 0035. Publicado en
  [PR #64](https://github.com/jata-imk/kronik/pull/64), código `ea1a0cd`.
  Pruebas y review medium/barrido aprobados; sin integración ni despliegue.

- 31/10/2026: incremento de amortización reutilizable completado e integrado
  mediante [PR #62](https://github.com/jata-imk/kronik/pull/62) en `15bdc8d` por
  autorización del usuario. Árbol idéntico a `f48d739`; se conserva la evidencia
  de pruebas y reviews del código `c5b8422`. Sin despliegue. La tarea amplia de
  arquitectura permanece abierta; siguiente incremento recomendado: catálogo
  de capacidades backend/Vue, en una sesión posterior.

- 31/10/2026: usuario autorizó integrar ambas PR. #59 integrada en `872d464`,
  #60 retargeteada a `main` e integrada en `942541a`. Se usaron commits de merge
  para conservar la dependencia. Árbol de `872d464` idéntico a `c815f62` y árbol
  de `942541a` idéntico a `30b222f`: integración sin modificaciones al código
  previamente revisado y probado. Revisiones medium individuales y barrido
  conjunto conservan validez para esos contenidos.
- CI de main aprobada para [#59](https://github.com/jata-imk/kronik/actions/runs/41257329407)
  y [#60](https://github.com/jata-imk/kronik/actions/runs/41257476250), incluidos
  backend/frontend y verificación de folios sobre MariaDB aislada. No acredita
  medición de cartera ni concurrencia de las capacidades financieras futuras.
  Sin despliegue ni QA operativo posterior reportados.

- 31/10/2026: usuario autorizó preparar el punto de extensión de convenciones de
  días. Implementados contrato de conteo por fechas/base fija, selección
  tipada sobre `reglas.convencion_interes` existente y cálculo compartido por
  periodo. Act/360 sigue siendo la única convención habilitada; otras capacidades
  del roadmap permanecen en espera. Ver [ADR 0033](../explanation/adr-0033-convenciones-dias-base-fija.md).

## Validación del incremento de convenciones — 31/10/2026

- Base de la entrega: `c815f62` (PR #59). Convenciones/motor compatibles de forma
  explícita; la UI conserva opciones y no se reescribe evidencia histórica.
- Código publicado: `70de5c3`, cierre documental `30b222f`. PR #60 se abrió contra
  la rama de #59 y se integró después contra `main` sin cambios de contenido.
- Regresión completa: `php artisan test --compact`, 583 aprobadas, una omitida y
  41963 aserciones. El hook pre-push repitió la suite y compiló el frontend,
  ambos aprobados sin omitir controles. Advertencias de recursos/chunks del build
  ya existentes; sin cambios a recursos ni dependencias en este incremento.
- 79 pruebas dirigidas/649 aserciones aprobadas; incluye cálculo independiente,
  calendarios, gracia, ambos motores, anticipos/liquidación, validación española,
  convenciones inválidas, actor/contexto y ausencia de actividad duplicada.
- E2E `solicitudes-dual.spec.js`: 1 aprobado, recorrido integral con PDF,
  desembolso, pago/reverso, anticipo, liquidación y reapertura. SQLite aislada
  recreada por el harness; sin base operativa ni proveedores externos.
- Pint de 16 archivos y `git diff --check` aprobados. Ocho documentos y 67 enlaces
  locales revisados sin roturas; ADR registrado en índice de explicación y tema.
- `codex review --base c815f6210b24b746e6d99c8e02af2ddadffc7525 -c
  model_reasoning_effort=medium`: sin regresiones accionables; 79 pruebas/649
  aserciones verificadas por el revisor, con código final de compatibilidad.
  Se repitió con acceso de red tras bloqueo de socket en sandbox.
- Barrido `codex review --base 9106a6efbbff2c1d25851d0d6392e0cac6349701 -c
  model_reasoning_effort=medium`: sin regresiones accionables; 79 pruebas/649
  aserciones aprobadas por el revisor. Código `70de5c3`, conjunto de PR #59 y #60;
  no acredita cierre de la tarea completa de arquitectura ni de Bullet/Revolvente.
- [CI del código publicado](https://github.com/jata-imk/kronik/actions/runs/41256290113).
  Resultado y checks del cierre documental se consultan en PR #60 y Notion.
- Sin migraciones, seeders nuevos, variables ni eventos auditables
  adicionales. Revisar [matriz](../reference/auditoria-eventos.md).

## Antecedentes — 29/10/2026

- 29/10/2026: roadmap autorizado; iniciativa y cuatro tareas técnicas creadas en
  Notion. Ocho tareas existentes coordinadas/reprogramadas, sin borrar historial.
- Documentación de arquitectura/contraste creada. Primer incremento de motor 2
  implementado localmente: numeradores Act/360, guardas de amortización y selección
  por evidencia. Nuevas activaciones congelan versión; históricos conservan motor 1.
- No acredita Bullet, Revolvente, Nómina, proyecciones ni capacidad implementados.
- Fechas objetivo y predecesoras guardadas en Notion; se conserva revisión de
  auditoría del 31/10 y PLD/SIC/corte mensual del 16/01/2027.
- Al cierre inicial del 29/10 quedaban integración y despliegue pendientes; la
  integración fue autorizada y completada el 31/10. QA automatizada ejecutada,
  sin alterar el VPS. El usuario pidió terminar solo este incremento; las demás
  tareas quedan pendientes, sin avanzar Bullet/Revolvente/Nómina.

## Antecedentes de decisiones del 29/10, superados por el estado vigente

- Casos funcionales Bullet: calendario, parcialidades/anticipos, mora de intereses,
  liquidación y periodos incompletos, antes de implementación dependiente.
- Respuestas del usuario el 29/10: incluir reducción de plazo para Bullet, con
  mecanismo de nuevo vencimiento todavía por definir; base/tratamiento de mora
  expresamente diferidos a revisión funcional. No habilitar Bullet con supuestos.
- Reglas específicas Revolvente y reconocimiento entre retención/recepción Nómina.

## Evidencia del incremento inicial — PR #59

- Investigación sobre `9106a6e`:
  [contraste fechado](../explanation/contraste-auditoria-core-crediticio-2026-10.md).
- Regresión final: `php -d memory_limit=512M vendor/bin/pest --compact`, 555
  aprobadas/41828 aserciones y una omitida. La suite agotó 128 MB en un intento;
  `phpunit.xml` fija 512 MB solo para tests. No es medición de capacidad del core.
- Selección de integridad/versionado/auditoría: 27/397 aprobadas; se superpone con
  la suite, no sumar como pruebas únicas.
- E2E `solicitudes-dual.spec.js`: 1 aprobado, contrato/PDF/firma/desembolso,
  pago/reverso, anticipo y liquidación/reapertura. Primer intento rechazado por
  permisos de perfil temporal Chrome en sandbox; repetición ampliada aprobada con
  SQLite `storage/framework/testing/kronik-e2e.sqlite`, sin base operativa.
- Pint de nueve archivos y `git diff --check` aprobados. Revisión final de 208
  enlaces locales en 19 documentos sin enlaces rotos.
- No acciones auditables nuevas por los calculadores internos; se mantienen
  emisores de aplicación. Ver matriz y pruebas de comportamiento existentes.
- Sin migraciones ni seeders nuevos. Único ajuste de configuración: memoria del
  proceso de pruebas; no cambia configuración operativa ni habilita flags QA.
- `codex review --uncommitted -c model_reasoning_effort=medium` (Codex CLI 0.165.0,
  gpt-6.1-sol): sin regresiones accionables. El revisor ejecutó 42 pruebas/689
  aserciones de integridad, versiones, pagos y anticipos, aprobadas. Revisión
  previa al commit.
- `codex review --base origin/main -c model_reasoning_effort=medium`, base
  `9106a6e` y código `78de880`: sin regresiones accionables; 27 pruebas/397
  aserciones dirigidas aprobadas por el revisor. Barrido actual de PR #59, única
  PR de este incremento; no acredita cierre del conjunto futuro de la iniciativa.
- Hook pre-push aprobado sin omitir controles: 555 pruebas/41828 aserciones, una
  omitida, y build frontend. Advertencias de recursos/chunks del build no impiden
  la compilación; no se cambiaron dependencias ni recursos en este incremento.
- [GitHub Actions de validación del código](https://github.com/jata-imk/kronik/actions/runs/41007708535).
  Resultado y checks vigentes se consultan en PR #59 y en Notion; los commits de
  cierre posteriores a `78de880` solo actualizan evidencia documental.

## Siguiente paso

El catálogo backend/Vue de [PR #64](https://github.com/jata-imk/kronik/pull/64) ya
está integrado en `ee94025`, según evidencia registrada en Notion. El diagnóstico
histórico queda atendido por la confirmación del usuario del 04/11/2026; no se
reabre. Otros contratos/exigibilidad y capacidades futuras conservan sus tareas.
Cerrar definición funcional Bullet con los contadores antes de implementar reglas.
Act/365 Fixed y 30/360 requieren definición, compatibilidad por motor y pruebas de
segmentación/contexto contractual antes de habilitarlas. No se atribuye una nueva
versión desplegada del core a la confirmación histórica.

## Cierre

El cierre histórico siguiente corresponde a incrementos anteriores. El catálogo
backend/Vue iniciado el 31/10/2026 mantiene su propia evidencia al final de la nota.

Incrementos integrados en `main` mediante PR #59 y #60, con revisiones medium
individuales, barrido conjunto contra `9106a6e` y CI de main aprobada. Sin
despliegue. La extensión autorizada el 31/10 queda implementada; las tareas técnicas/funcionales
mayores permanecen abiertas. El flujo de
[sesiones y revisiones](../reference/flujo-tareas-pruebas-pr.md) exige review medium
por PR y barrido acumulado de todas las PR al cerrar cada funcionalidad.

## Incremento de amortización reutilizable — 31/10/2026

Usuario eligió estrategias actuales antes del catálogo de capacidades. Rama
`refactor/amortizacion-reutilizable`, base `56026d0`; preparación arquitectónica,
sin nueva habilitación funcional. Contrato y estrategias puros, condiciones y
resultado inmutables, tabla adaptadora y decisiones compartidas con anticipos.
Se mantienen ambos órdenes de operaciones históricos y la serialización.
ADR 0034 documenta límites y diagrama. Solo Act/360; no habilita Bullet/Revolvente.

Base verificada limpia/sincronizada; 92 pruebas dirigidas/770 aserciones aprobadas
antes del cambio y tras extracción inicial. Fixtures capturados de `56026d0` con
datos sintéticos; cuatro pruebas nuevas/24 aserciones aprobadas. Evidencia final,
PR y revisiones finales registradas a continuación.

El usuario reporta aproximadamente cuatro créditos históricos de demo. Su
revisión/tratamiento puede ser acotado, pero no se ha inspeccionado ni modificado
esa cartera. El catálogo, diagnóstico histórico y demás implementaciones siguen
en espera. La tarea amplia de arquitectura continúa abierta.

### Evidencia final del incremento — PR #62

[PR #62](https://github.com/jata-imk/kronik/pull/62), código `c5b8422`, base
`56026d0`. Implementado e integrado en `15bdc8d`, sin despliegue.

- `php artisan test --compact`: 587 aprobadas, una omitida, 41996 aserciones.
- E2E `solicitudes-dual.spec.js`: 1 aprobado sobre SQLite aislada, con PDF,
  desembolso, pago/reverso, anticipo, liquidación y reapertura. Sin BD operativa.
- Build, Pint de 14 archivos y `git diff --check` aprobados. Seis documentos y
  68 enlaces locales revisados sin roturas. Advertencias de recursos/chunks ya
  existentes; sin cambios de dependencias. Hook pre-push repitió suite/build
  aprobados sin omitir controles para `c5b8422`.
- `/review medium` mediante `codex review --base 56026d0 -c
  model_reasoning_effort=medium`: sin hallazgos accionables; revisor verificó
  70 pruebas dirigidas. Barrido acumulado contra `9106a6e` de #59/#60/#61/#62:
  sin regresiones accionables, 86 pruebas dirigidas verificadas por el revisor.
  Ambas revisiones cubren el contenido de código `c5b8422`.
- Auditoría revisada sin nuevos eventos; prueba de anticipo verifica reintentos,
  actor/sujeto, contexto y ausencia de metadatos sensibles. Sin migraciones,
  seeders nuevos, variables ni cambios de UI. CI y revisión del cierre documental
  se consultan en PR #62; no confundir CI con despliegue o medición de capacidad.

Este incremento completa la extracción de estrategias actuales, no toda la tarea
arquitectónica. Próximo incremento por elegir en Notion: catálogo de capacidades
u otros contratos pendientes; no iniciarlos en esta sesión. Revisar separadamente
los aproximadamente cuatro créditos demo. Bullet aún requiere mecanismo de
reducción de plazo y definición funcional de mora. Solo Act/360 habilitada.

### Cierre e integración autorizada — 31/10/2026

PR #62 integrada con merge, conservando historial y árbol aprobado `f48d739`.
[CI del head](https://github.com/jata-imk/kronik/actions/runs/41260792874) aprobada.
Reviews medium individual, acumulada contra `9106a6e` y del cierre documental
sin hallazgos accionables. La igualdad de árboles preserva esa evidencia sin
presentarla como nueva ejecución. CI de main y cierre documental se consultan
en las PR correspondientes. Este cierre solo actualiza estados/textos: no requiere
pruebas funcionales nuevas; se revisan contenido y enlaces locales.

En Notion se completa el incremento y los criterios demostrados (versión de motor
independiente del formato y ejemplo ejecutable simple/disposición). La iniciativa,
la tarea arquitectónica y la validación transversal siguen abiertas por capacidades
todavía pendientes; no se acredita línea operativa, capacidad ni despliegue.

## Catálogo backend/Vue — 31/10/2026

[PR #64](https://github.com/jata-imk/kronik/pull/64), rama
`feat/catalogo-capacidades-credito`, base funcional `d002037`, código `ea1a0cd`.
Implementado y publicado, sin integración ni despliegue. Mantiene métodos,
periodicidades, Act/360 y aplicaciones contractuales del prepago actuales.
Catálogo puro, compatibilidad explícita por motor y resolvedor desde snapshot;
opciones/etiquetas/ayudas compartidas con Vue. Conserva orden contractual de
opciones, borradores incompletos, snapshots, hashes y reproducción histórica.

- Hook pre-push ejecutado en secuencia: 599 backend aprobadas, una omitida y
  42047 aserciones, más build aprobado. Sin omitir controles. Primera ejecución
  simultánea con pruebas del revisor tuvo colisiones de archivos documentales;
  repetición secuencial aprobada. No se modificó lógica para ocultar esos fallos.
- Frontend: 114 pruebas aprobadas en 43 archivos. Pint y diff-check aprobados;
  siete documentos/74 enlaces locales revisados sin roturas.
- E2E aislados: tres de Productos (incluido móvil), uno de Solicitudes y uno
  dual integral aprobados. El dual incluye PDF, desembolso, pago/reverso, anticipo,
  liquidación y reapertura. Sin base operativa ni proveedores externos.
- Solicitudes actualiza expectativas a la etiqueta backend `Mensual` y conserva
  el título real al pasar a móvil, sin asumir el prefijo histórico de folios.
  Un intento intermedio tuvo `ERR_NO_BUFFER_SPACE` de Chromium; repetición
  aislada aprobada. Se revisaron captura, contexto y traza de los fallos.
- Review medium y barrido de esta única PR contra `d002037`, código `ea1a0cd`:
  sin hallazgos accionables; 58 pruebas backend dirigidas y dos frontend aprobadas
  por el revisor. Su ejecución amplia simultánea tuvo fallos de fixtures compartidos;
  la suite principal secuencial aprobada es la evidencia de regresión completa.
- Auditoría: sin eventos adicionales por consulta/resolución; pruebas verifican
  ausencia de actividad y persistencia ante rechazo. Se conservan pruebas de
  actor/contexto/idempotencia de activación y pagos. Matriz y ADR 0035 actualizados.
- Sin migraciones, seeders nuevos ni variables. Advertencias existentes de recursos,
  Browserslist y tamaño de chunks no impiden build. CI se consulta en la PR.
- [CI del código `ea1a0cd`](https://github.com/jata-imk/kronik/actions/runs/41265945206)
  aprobada, incluidos backend/frontend y folios en MariaDB aislada. Review medium
  del cierre contra `ea1a0cd` (evidencia y aserción del título): sin hallazgos
  accionables. El cierre no modifica código ejecutable; CI del head final en la PR.

La iniciativa, arquitectura amplia y validación transversal siguen abiertas. La
consulta completa de versiones y mejoras de política de originación conservan su
tarea separada. Bullet mantiene pendientes reducción de plazo y mora; diagnóstico
de créditos demo y demás capacidades continúan en espera.

## Alcance Bullet y ampliaciones autorizadas — 04/11/2026

El usuario recibió respuesta funcional y acepta seguir primero la recomendación
de Bullet base: anticipos conservando vencimiento, liquidación anticipada, las dos
políticas de intereses y frecuencias actuales. Se retira de ese alcance la reducción
de plazo por anticipo parcial Bullet, sustituyendo la intención del 29/10; los
métodos actuales conservan sus opciones. Siguen pendientes las respuestas sobre
cobro del devengado aún no exigible y base de mora de intereses vencidos, además
de precisar el corte de fecha del abono si difiere del comportamiento vigente.

El usuario confirma incluir frecuencia diaria y capitalización en ampliaciones
separadas después del Bullet base. Son capacidades futuras autorizadas, todavía
sin reglas completas ni implementación. Orden recomendado: aceptar Bullet base,
añadir exigibilidad diaria y después capitalización; no se fijan fechas ni se
reprograman otras iniciativas automáticamente.

Periodicidad diaria requiere calendario, catálogo/compatibilidad, validación, UI,
revisión del CAT y pruebas históricas; no cambia por sí sola Act/360. Capitalización
requiere definición de conceptos/evento/base futura, evidencia de origen,
idempotencia y reversos, sin reinterpretar contratos anteriores. La conversación
no habilita estas capacidades ni introduce cambios al código financiero.

# Core crediticio extensible

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
plazo fuera de Bullet base; diaria y capitalización separadas. Solo mora conserva
decisiones de activación, compatibilidad de bases y validación contractual pendientes.

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

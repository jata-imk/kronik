# Core crediticio extensible

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

## Estado actual

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

## Decisiones pendientes

- Tratamiento de cartera histórica afectada por F-01/F-02; el adaptador conserva
  su algoritmo y no corrige saldos anteriores automáticamente.
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

Integración de ambos incrementos completada. Las demás tareas permanecen en espera
por indicación del usuario. Al retomarlas, leer Notion y esta nota: la separación
completa de estrategias/capacidades y el diagnóstico de cartera histórica siguen
pendientes. Cerrar definición funcional Bullet antes de implementar sus reglas.
Act/365 Fixed y 30/360 requieren definición, compatibilidad por motor y pruebas de
segmentación/contexto contractual antes de habilitarlas. Despliegue y QA operativo
posterior no se han realizado por esta entrega.

## Cierre

Incrementos integrados en `main` mediante PR #59 y #60, con revisiones medium
individuales, barrido conjunto contra `9106a6e` y CI de main aprobada. Sin
despliegue. La extensión autorizada el 31/10 queda implementada; las tareas técnicas/funcionales
mayores permanecen abiertas. El flujo de
[sesiones y revisiones](../reference/flujo-tareas-pruebas-pr.md) exige review medium
por PR y barrido acumulado de todas las PR al cerrar cada funcionalidad.

## Incremento de amortizaci?n reutilizable ? 31/10/2026

Usuario eligi? estrategias actuales antes del cat?logo de capacidades. Rama
`refactor/amortizacion-reutilizable`, base `56026d0`; preparaci?n arquitect?nica,
sin nueva habilitaci?n funcional. Contrato y estrategias puros, condiciones y
resultado inmutables, tabla adaptadora y decisiones compartidas con anticipos.
Se mantienen ambos ?rdenes de operaciones hist?ricos y la serializaci?n.
ADR 0034 documenta l?mites y diagrama. Solo Act/360; no habilita Bullet/Revolvente.

Base verificada limpia/sincronizada; 92 pruebas dirigidas/770 aserciones aprobadas
antes del cambio y tras extracci?n inicial. Fixtures capturados de `56026d0` con
datos sint?ticos; cuatro pruebas nuevas/24 aserciones aprobadas. Evidencia final,
PR y revisiones se registrar?n al completar validaci?n.

El usuario reporta aproximadamente cuatro cr?ditos hist?ricos de demo. Su
revisi?n/tratamiento puede ser acotado, pero no se ha inspeccionado ni modificado
esa cartera. El cat?logo, diagn?stico hist?rico y dem?s implementaciones siguen
en espera. La tarea amplia de arquitectura contin?a abierta.

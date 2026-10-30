# Core crediticio extensible

## Referencias

- Notion: [iniciativa y roadmap](https://app.notion.com/p/40961db7db7f819a8af1d7c3cfec2df7).
- Rama: `feat/core-crediticio-extensible`, base main `9106a6e`.
- PR: [#59](https://github.com/jata-imk/kronik/pull/59).
- Código del incremento: `78de880`, base funcional/revisada `9106a6e`.
- ADR: 0032; relacionados 0006, 0012, 0020 y 0023.

## Objetivo

Consolidar integridad y responsabilidades reutilizables para Bullet, Revolvente y
Nómina. Alcance/calendario en Notion; contratos en la referencia del core.

## Estado actual

- 29/10/2026: roadmap autorizado; iniciativa y cuatro tareas técnicas creadas en
  Notion. Ocho tareas existentes coordinadas/reprogramadas, sin borrar historial.
- Documentación de arquitectura/contraste creada. Primer incremento de motor 2
  implementado localmente: numeradores Act/360, guardas de amortización y selección
  por evidencia. Nuevas activaciones congelan versión; históricos conservan motor 1.
- No acredita Bullet, Revolvente, Nómina, proyecciones ni capacidad implementados.
- Fechas objetivo y predecesoras guardadas en Notion; se conserva revisión de
  auditoría del 31/10 y PLD/SIC/corte mensual del 16/01/2027.
- Integración y despliegue pendientes; QA automatizada local ejecutada, sin alterar
  el VPS. El usuario pidió terminar solo este incremento en esta sesión; las demás
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

## Evidencia

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

Revisar checks y decidir integración de PR #59; no se ha integrado ni desplegado.
Las demás tareas quedan en espera por indicación del usuario. Al retomarlas en
otra sesión, leer Notion y esta nota: cerrar definición funcional Bullet antes de
implementar comportamiento dependiente. La separación completa de estrategias y
capacidades del roadmap continúa pendiente.

## Cierre

Incremento inicial publicado en PR #59 y revisado medium, pendiente de integración
y despliegue. Las tareas técnicas/funcionales permanecen abiertas; este incremento
no completa toda la iniciativa. El flujo de
[sesiones y revisiones](../reference/flujo-tareas-pruebas-pr.md) exige review medium
por PR y barrido acumulado de todas las PR al cerrar cada funcionalidad.

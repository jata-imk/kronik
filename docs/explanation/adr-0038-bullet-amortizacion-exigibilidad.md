# ADR 0038: amortización y proyección de exigibilidad Bullet

- Estado: implementación local del primer incremento; operación integral pendiente.
- Fecha: 2026-11-05.
- Complementa ADR 0032, 0034 y 0035.

## Contexto

El cálculo reutilizable presupone capital positivo en todos los periodos.
Bullet requiere capital intermedio cero y dos políticas de interés. El catálogo
operativo todavía no puede ofrecerlo: falta integrar pagos, anticipos y mora.
Las bases y el disparador de mora ya fueron aclarados en Notion. No se cambia la historia para admitirlo.

## Decisión

Añadir Bullet al resolvedor de estrategias, con permiso explícito de capital
intermedio cero en la estrategia. Las dos estrategias actuales devuelven falso;
capital negativo, exceso de principal, calendario inválido y residuo final
conservan sus guardas. Bullet requiere motor 2 y Act/360.

Amortización conserva segmentos con fechas y numeradores. Una política pura
separada proyecta interés periódico o al vencimiento. Al vencimiento acumula
numeradores y redondea una sola vez; conserva intervalos sin pago para que el
CAT no adelante el flujo final. No decide mora, distribución ni cobro.

No se agrega Bullet al catálogo backend/Vue en este incremento. La presencia
del enum y la estrategia no permite activación ni simulación HTTP. Los métodos
actuales conservan serialización y cifras, verificadas con sus fixtures.

## Consecuencias

El resultado interno amplía metadatos, sin cambiar toArray ni snapshots actuales.
No hay migraciones, nuevos permisos, variables o eventos auditables por cálculo.
Los siguientes incrementos deben congelar política/bases y preservar el cargo,
numerador y contabilización del interés anterior al regenerar por anticipo.
Este ADR no acredita que MotorPagos ya trate obligaciones Bullet ni que se hayan
implementado anticipo, mora, persistencia o editor.

## Evidencia

[Casos vigentes](../reference/bullet-casos-definicion.md),
[contrato del core](../reference/core-crediticio.md) y
[Agent Note](../agent-notes/core-crediticio-extensible.md).
BulletAmortizacionTest cubre ambas políticas, redondeo, calendarios y exclusión
operativa; AmortizacionReutilizableTest y AnticiposCaracterizacionTest preservan
las cifras anteriores. Pruebas no equivalen a despliegue ni QA operativo.

La preparación posterior de condiciones/editor se documenta en
[ADR 0039](adr-0039-condiciones-bullet-y-habilitacion.md), manteniendo bloqueo operativo.

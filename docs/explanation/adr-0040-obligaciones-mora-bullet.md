# ADR 0040: obligaciones y mora Bullet en el motor de pagos

- Estado: implementado internamente en `feat/bullet-base`, PR #73; operación pendiente.
- Fecha: 2026-11-05.
- Complementa ADR 0038 y 0039; conserva las decisiones funcionales de Notion.

## Contexto

El motor existente vincula atraso y devengo a capital de cuota. En Bullet el
interés puede vencer con capital intermedio cero. Además, sumar saldo insoluto
o capital inicial por cada cuota vencida duplicaría la base. Las tres bases y
el disparador por obligación vencida impagada ya están aclarados; no se reabren.

## Decisión

Extender `MotorPagos` mediante un recorrido diario específico para el método
exacto Bullet, reutilizando cargos, impuestos, numeradores, aplicación,
persistencia y reversos existentes. Conservar el recorrido anterior para los
contratos previos. No crear otro motor ni tablas de obligaciones paralelas.

La política contractual asigna devengo al cargo periódico o final. La obligación
de capital/interés pendiente dispara mora; no exige capital vencido. Las bases
globales se toman una vez por día; la base por obligación agrega solo remanentes
elegibles. Los numeradores y cargos mantienen separado ordinario, moratorio,
capital, comisiones e impuestos.

La gracia retroactiva conserva evidencia diaria: obligaciones impagadas, capital,
numerador y ordinario pagado de ese día, base global y cargo ordinario que podría
sustituirse. Al activarse, se retira ordinario provisional antes de calcular la
base corriente. Si cruzó otra fecha de pago, se corrigen también sus bases
diferidas. Un pago posterior no reemplaza la base de días anteriores; las
obligaciones cubiertas durante gracia descartan su mora diferida.

El estado Bullet exige método, capital inicial y control de gracia. Un acumulador
perdido se rechaza, sin inferirlo del importe truncado. Se mantiene la restricción
de parcialidades durante B+sustitución para impedir compensaciones implícitas de
intereses ya pagados.

## Consecuencias

Los indicadores distinguen capital e interés vencidos sin reclasificar capital
futuro. El estado nuevo usa los arrays cifrados existentes; los contratos
históricos no reciben marcas ni defaults. Sin nuevas migraciones, variables,
permisos ni eventos de cálculo. Los eventos reales de pago/reverso siguen siendo
`CreditPaymentRecorded` y `CreditPaymentReversed`, con su emisor de aplicación.

Bullet permanece fuera del catálogo operativo. El bloqueo inicial de anticipo y
liquidación internos se sustituye por la preservación definida en
[ADR 0041](adr-0041-anticipos-liquidacion-bullet.md), sin habilitar el ciclo.
La evidencia sintética de persistencia prueba la integración interna y auditoría,
sin acreditar originación, concurrencia MariaDB, despliegue ni cierre de Bullet.
Diaria y capitalización conservan sus entregas separadas.

## Evidencia

`BulletObligacionesTest` cubre oráculos por fecha/base, gracia y sustitución,
vencimientos superpuestos, pagos y acumuladores. `BulletPagosTest` prueba previa
sin escritura, persistencia cifrada, reintento, consulta y reverso compensatorio,
actor/contexto y ausencia de valores reservados en actividad. La regresión de
los métodos previos conserva sus pruebas y fixtures.

Contrato canónico en [core crediticio](../reference/core-crediticio.md#obligaciones-y-mora-bullet),
casos en [Bullet](../reference/bullet-casos-definicion.md) y estado de entrega en
la [Agent Note](../agent-notes/core-crediticio-extensible.md).

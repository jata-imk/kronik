# ADR 0041: anticipo Bullet con devengado conservado y liquidación reversible

- Estado: implementado en PR #73; el bloqueo operativo provisional queda sustituido por [ADR 0042](adr-0042-habilitacion-ciclo-bullet-qa.md).
  Integración y despliegue se registran en la Agent Note.
- Fecha: 2026-11-06.
- Complementa ADR 0023 y 0040; no modifica las definiciones moratorias aceptadas.

## Contexto

El anticipo de los métodos anteriores cobra todo el devengado, archiva sus cargos
y genera cuotas nuevas. En Bullet parcial el interés todavía no exigible debe
conservar importe y vencimiento. Recrearlo desde importes redondeados perdería
numeradores, identidad de obligaciones y contadores de contabilización; además,
reiniciar el motor desde el saldo reducido sustituiría el capital inicial usado
por una de las bases moratorias.

## Decisión

Reutilizar `MotorAnticipos`, distribución proporcional, comisiones de evento,
impuestos y persistencia/reverso de `PagoService`. Distinguir el método exacto
Bullet sin cambiar el recorrido histórico. Exigir método, motor 2, Act/360 y
condiciones explícitas; la evidencia incompleta se rechaza sin reconstrucción.

En el anticipo parcial se cubren exigibles y cargos del evento y el remanente
reduce capital en la obligación final. El interés no exigible conserva cargo,
numerador, pagos y fecha. Se conserva `capital_inicial` y el control diario de
gracia; las obligaciones cubiertas se descartan del cálculo diferido mediante
el avance vigente, sin reiniciar el control ni cambiar sus reglas.

El capital se ofrece temporalmente a la distribución en la fecha del abono y
después recupera su vencimiento contractual. Las claves de cuotas y cargos no
cambian. La contabilización acumulada se actualiza al importe generado y la
persistencia registra solo su diferencia respecto al snapshot anterior. Consultar
o cotizar no persiste devengos, calendarios ni actividad.

La nueva versión del cronograma conserva fechas y números de las cuotas futuras.
La estrategia existente distribuye capital al final; `InteresPorPeriodo` proyecta
el nuevo saldo desde el día siguiente al abono. Cada obligación suma numerador
conservado y futuro antes de redondear. Al vencimiento se acumulan los segmentos
sin cobro intermedio. Las comisiones periódicas y su fiscalidad se conservan;
la proyección incluye el interés previo pendiente sin volverlo a contabilizar.

Liquidar vuelve pagaderos todos los devengos pendientes y el capital, incluye
cargos e impuestos aplicables y retira comisiones periódicas futuras. Exige el
importe exacto y deja el crédito liquidado, sin generación posterior. Se conservan
las fechas originales de los cargos en el estado y las asignaciones de la operación.

El reverso conserva las restricciones y compensaciones existentes. Restaura el
estado anterior, crea una nueva versión del calendario original de la operación
y reabre la liquidación; no borra pagos, recibos, movimientos ni cronogramas.

## Consecuencias y límites

Sin tablas, migraciones, variables, permisos, endpoints ni UI nuevos. Se mantienen
`CreditPaymentRecorded` y `CreditPaymentReversed`, sin actividad adicional por
cálculo ni metadatos financieros sensibles. Actor, sujeto y contexto se verifican
en integración sintética, junto con reintentos y ausencia de doble contabilización.

Activación, simulación y originación Bullet siguen bloqueadas. Las pruebas usan
evidencia sintética exclusivamente en BD de pruebas; no acreditan ciclo de
originación ni concurrencia financiera MariaDB. No hay integración en main ni
despliegue. Diaria, capitalización y reducción de plazo Bullet quedan separadas.

Contrato canónico en [core crediticio](../reference/core-crediticio.md),
[casos de aceptación](../reference/bullet-casos-definicion.md) y evidencia de
entrega en la [Agent Note](../agent-notes/core-crediticio-extensible.md).

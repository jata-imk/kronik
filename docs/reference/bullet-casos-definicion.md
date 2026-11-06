# Bullet: decisiones y casos de aceptación

Estado funcional al 05/11/2026. Prevalece la sección vigente de la
[definición en Notion](https://app.notion.com/p/40861db7db7f81d1ab37cc46ea320c3c).
La preparación histórica del 29/10 y las preguntas de PR #65 quedan superadas
en anticipo, reducción de plazo y corte de fecha. Definición no equivale a
implementación integral; evidencia en la [Agent Note](../agent-notes/core-crediticio-extensible.md).

## Alcance confirmado

Capital al vencimiento, intereses periódicos o al vencimiento; tasa fija, MXN,
Act/360, semanal, cada 15 días y mensual. Reutilizar calendario sin ajuste por
inhábiles, ancla mensual y fin de mes. La última cuota con capital e interés no
es otra modalidad. Anticipo parcial conserva vencimiento; reducción de plazo
no entra en Bullet base. Diaria y capitalización son ampliaciones separadas.

Orden vigente: cuota más antigua y comisión → moratorio → ordinario → capital,
con impuesto proporcional por concepto. Anticipo cubre exigibles y cargos
aplicables; el remanente reduce principal. El interés devengado no exigible
conserva importe y vencimiento, sin cobro obligatorio ni capitalización.
El capital reducido genera intereses desde el día siguiente.

Liquidación cubre capital, devengos, cargos e impuestos aplicables y excluye
interés futuro. Reverso mantiene último movimiento admisible sin posteriores,
compensación e historia conservada. No ampliar fechas ni límites por esta entrega.

## Oráculos independientes

Fixture: 12000 MXN, 24 % anual, disposición 01/01/2027 y vencimiento 01/03/2027.
Sin comisiones y fiscalidad no_causa exclusivamente para aislar aritmética;
no prescribe fiscalidad comercial. Antes del anticipo se generan 8 diarios.

| Caso | Periódicos | Al vencimiento |
| --- | --- | --- |
| Normal | 248 el 01/02; 12224 el 01/03 | 12472 el 01/03 |
| Abono de 100 el 01/02 | Quedan 148 de interés exigible y capital 12000 no vencido | No existe interés exigible; un pago ordinario sin deuda exigible se rechaza |
| Anticipo de 3120 el 16/01 | Capital 8880 y 120 conservados; 214.72 el 01/02 y 9045.76 el 01/03 | Capital 8880 y 120 conservados; 9260.48 el 01/03 |
| Liquidación el 16/01, sin anticipo previo | 12120 | 12120 |
| Liquidación el 20/01, tras anticipo | 9023.68 | 9023.68 |
| Vencimiento final y abono de 100 | Pagado enero, quedan 124 de interés y capital 12000 | Quedan 372 de interés y capital 12000 |
| Reverso admisible | Restaura principal, devengos y calendario; conserva compensaciones | Igual, incluida reapertura tras liquidación |

Al 16/01: 12000 × 24 × 15 / 36000 = 120. El abono completo de 3120
reduce principal a 8880. Desde el 17/01 genera 5.92 diarios: 16 días hasta
01/02 = 94.72 y 28 hasta 01/03 = 165.76. Los 120 anteriores mantienen
su fecha; interés restante total 380.48. El total del escenario es 12380.48.
Liquidar el 20/01 añade cuatro días a 5.92: 8880 + 120 + 23.68 = 9023.68.

Caso con exigible: pagados 100 del interés de enero, anticipo de 3148 el mismo
01/02 cubre los 148 restantes y anticipa 3000 de capital; quedan 9000.

## Mora: precisión funcional recibida el 05/11

Ordinario y moratorio tendrán bases independientes. Las candidatas son saldo
insoluto, capital inicial y pendiente del vencimiento. Esta última representa
remanente impagado: 248 menos 100 = 148. No equivale al capital vencido actual.
Ninguna lista de candidatas acredita soporte ni admite cualquier combinación.

José confirma durante la implementación: la obligación exigible vencida e impagada
activa mora, incluso si es interés y el capital aún no vence. Las tres bases
moratorias son saldo insoluto, capital inicial y amortización/obligación vencida.
En la última, solo entra el remanente impagado: 148 en el caso anterior.
La política periódica/al vencimiento decide cuándo el interés es exigible.
No duplicar bases globales por varias obligaciones atrasadas.

Esta respuesta cierra la consulta funcional de activación. No acredita bases
ordinarias adicionales ni validación jurídica realizada por el agente. Conservar
gracia y coexistencia/sustitución existentes; las capacidades operativas requieren
implementar y probar su compatibilidad antes de ofrecerlas.

La UI propone «Igual que el ordinario» solo cuando sea compatible y muestra
ambas bases efectivas. Activación congela ambas; no guarda una referencia mutable.
Probar atraso de interés, pago parcial, anticipo posterior, varias obligaciones
vencidas, vencimiento final, liquidación y reverso para cada combinación aprobada.

## Diseño y verificación

Reutilizar estrategias, calendario, impuestos, aplicación y editor. Separar
amortización, devengo, exigibilidad y mora. Preservar numeradores y cargo original
al regenerar: no perder interés, duplicarlo ni contabilizarlo de nuevo.
Para al vencimiento, redondear el acumulado y conservar intervalos sin pago para CAT.

El primer incremento contiene estrategia y proyección pura de ambas políticas.
El segundo prepara condiciones/capacidades/editor en borradores según el
[contrato de Productos](productos-crediticios.md#preparar-condiciones-bullet), sin
ofrecer Bullet operativo. Pagos, anticipo conservando devengado y las reglas de
mora confirmadas necesitan integración y pruebas.

Regresión requerida: calendarios, bisiestos, centavos, métodos actuales,
históricos sin normalización, borradores, payload incompatible, consulta sin
escritura, permisos/contexto original, auditoría, idempotencia, previa obsoleta,
concurrencia MariaDB y E2E aislados. Casos numéricos aceptados no sustituyen
validación del ciclo completo.

Relacionadas: [core crediticio](core-crediticio.md),
[productos](productos-crediticios.md),
[ADR 0032](../explanation/adr-0032-core-crediticio-extensible.md).

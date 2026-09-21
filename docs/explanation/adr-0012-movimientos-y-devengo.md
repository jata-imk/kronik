# ADR 0012: movimientos, devengo y aplicación de pagos

- Estado: aceptado
- Fecha: 2026-09-20
- Alcance: arquitectura objetivo, no autorización de operación real

## Contexto

El simulador de productos genera proyecciones; no representa un saldo real ni
registra caja. Confundir ambos impide manejar parcialidades, anticipos y reversos.

## Decisión

Separar cronograma versionado, términos formalizados, desembolso, pago y
asignaciones/movimientos. Reutilizar motor y Decimal (BCMath); no floats monetarios.
Interés ordinario diario sobre capital real, días reales/360, precisión interna
alta y redondeo al contabilizar. Mora separada sobre capital vencido.

Política fija aprobada: cuota más antigua primero; dentro de cuota, comisión,
mora, interés ordinario y capital. No hacer configurable este orden en V1.
Anticipo reduce intereses futuros; conservar reducción de plazo/cuota del producto.

Transacciones y bloqueos de filas serializan movimientos por crédito. Claves de
idempotencia únicas verifican además que el payload sea el mismo. Reversos son
compensaciones con actor/motivo, nunca eliminación. Conservar relación a original.
Fecha de desembolso distinta de la formalizada exige nueva tabla y formalización.
La tabla anexa es HTML controlado por servidor, no tablas libres de Quill.

## Consecuencias

Fixtures independientes verifican actual/360, bisiestos, redondeo, parciales,
reversos, anticipos y conservación de montos. MariaDB verifica concurrencia real.
Impuestos, gracia/coexistencia de intereses, retroactividad, excedentes y contratos
son puertas de habilitación; no asumir IVA cero ni reglas legales universales.

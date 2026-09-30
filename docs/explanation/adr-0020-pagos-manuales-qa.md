# ADR 0020 — Pagos manuales QA y evidencia compensatoria

Fecha: 2026-09-30. Estado: aceptado para P6 QA; pendiente de integración.

## Contexto

P6 completa el primer recorrido vertical de la iniciativa 04–07. El calendario
firmado es una proyección inmutable, no la fuente de intereses devengados. Las
decisiones funcionales (fiscalidad, atraso, parcialidad B y fechas pasadas) están
aprobadas en Notion, Backlog 07. No se modifican los estados ni la formalización.

## Decisión

- Motor puro decimal: conserva capital por cuota y acumuladores sin redondeo
  diario; redondea para aplicar y registrar conceptos. Las consultas no escriben.
- La política de atraso forma parte de la versión de producto y de su evidencia
  congelada. No se infiere ni se completa retrospectivamente en créditos viejos.
- Pago y reverso son registros inmutables con evidencia cifrada antes/después,
  distribución por concepto/impuesto, huella, actor y fechas efectiva/de captura.
- Bloqueo transaccional por crédito; previa identificada por huella e idempotencia
  del envío. Referencia única reservada incluso después de un reverso.
- El libro registra capital inicial, devengos, impuestos y abonos como movimientos
  con signo. Los devengos se contabilizan al aplicar un pago, no mediante la vista
  ni un proceso diario. La situación al día se calcula desde la última evidencia.
- Reverso V1 solamente del último registro si es un pago y no hay movimientos con
  fecha efectiva posterior. Compensa sus movimientos y restaura el estado anterior,
  conserva la fecha efectiva original y la captura real. No ejecuta reembolso.
  No permite encadenar correcciones de historia anterior después de otro reverso.
- `pay creditos` y `reverse payments creditos` separados. La sucursal activa y
  `ORIGINACION_PAGOS_QA_HABILITADOS` se validan también en el servicio, incluso para
  Super Admin. La bandera está cerrada por defecto y no habilita dinero real.

## Consecuencias y límites

La lectura del crédito distingue exigible, capital pendiente y calendario firmado.
No se agrega estado contractual ni se declara liquidación anticipada. No se
aceptan excedentes, anticipos o pagos históricos con movimientos posteriores.
No hay conciliación bancaria, CFDI, cuentas de saldo a favor ni fiscalidad implícita.
Gracia B + sustitución rechaza parcialidad aplicada a una cuota durante su gracia;
la compensación trazable que permitirá ese caso sigue diferida en Notion.

Los snapshots aumentan almacenamiento pero permiten auditar y revertir sin editar
recibos. La huella detecta cambios accidentales; no sustituye controles de acceso
a BD, protección de llaves ni copias de seguridad. Pruebas secuenciales de previa
desactualizada no acreditan concurrencia real multiconexión por sí solas.

## Verificación

Motor, conservación de centavos, gracia/sustitución, permisos, fechas, duplicados,
reverso, integridad e invalidación de previa; recorrido Playwright aislado hasta
pago/reverso. Resultados finales y PR se registran en Agent Note 07.

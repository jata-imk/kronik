# ADR 0023: anticipos, liquidación y calendarios conservados

Estado: aceptada para QA. Fecha: 10/10/2026.

## Decisión financiera

Capturar el total recibido. Cubrir vencidos y todo interés ordinario/moratorio
devengado, impuestos y comisiones exigibles; después cargos del evento y capital
futuro. El mínimo contractual aplica al capital anticipado. No crear saldo a
favor ni recortar excedentes. Liquidación exige coincidencia exacta con cotización
y deja capital y cargos aplicables en cero.

Comisiones: evento explícito (anticipo, liquidación anticipada u ordinaria), base
porcentual (monto solicitado original, capital anticipado o capital insoluto
anterior) y fiscalidad congelada. Una comisión histórica ambigua bloquea; no se
reinterpreta. Resolver cargos sobre capital anticipado en centavos mediante
búsqueda monótona. Si no hay distribución exacta, rechazar e informar vecinos.

BCMath, días reales/360 y redondeo half-up al contabilizar. Principal reducido
deja de devengar desde el día siguiente. Retirar intereses futuros y comisiones
periódicas de cuotas eliminadas; conservar cargos exigibles. Reducir pago
conserva fechas/cantidad. Reducir plazo conserva cuota base o capital periódico
según método, ajustando última cuota. Restricción B + sustitución durante gracia
permanece vigente.

## Conservación y control

Contrato y calendario originales inmutables. Cada anticipo/liquidación conserva
versión nueva, snapshot antes/después y movimientos. La previa vincula captura
y estado; un movimiento concurrente la invalida. Registro bajo bloqueo del
crédito, idempotencia y referencia única. Permisos separados prepay/settle,
flags pagos/anticipos QA. Pago ordinario rechaza anticipo inyectado por esa ruta.

Reverso compensatorio solo del último movimiento admisible: conservar recibos,
restaurar calendario con nueva versión y reabrir crédito liquidado. No ejecutar
reembolso. Consultar no registra devengos. Un crédito liquidado no genera
devengos futuros ni admite pagos nuevos.

Gráficas con las mismas fechas, suponiendo pago puntual; distinguir proyecciones
de movimientos registrados. Contabilidad, conciliación bancaria, CFDI y dinero
real quedan fuera de esta entrega.

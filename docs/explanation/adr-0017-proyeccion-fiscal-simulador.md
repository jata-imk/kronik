# ADR 0017: proyección fiscal explícita en el simulador

- Estado: aceptado para QA, previo a formalización con impuestos.
- Fecha: 2026-09-28.
- Precedentes: ADR 0008, 0015 y 0016.

## Contexto

La fiscalidad por concepto está versionada desde PR #24. El usuario pide completar
el recorrido funcional de QA sin esperar un dictamen contable, conservando una
lista de validaciones profesionales para operación real. No se habilita dinero.

## Decisión

Se extiende el simulador existente con `incluir_impuestos`, booleano optativo y
falso por defecto para compatibilidad. La interfaz permite seleccionarlo de forma
explícita. Desmarcado devuelve `fiscalidad.estado=no_calculada`, sin total fiscal;
no convierte datos ausentes en impuesto cero. Marcado exige uso fiscal y tratamiento
definido de ordinarios y comisiones aplicadas. Los conceptos opcionales no elegidos,
moratorios y cargos por eventos no se generan en esta simulación de curso normal.

`ImpuestoConceptoService` utiliza Decimal/BCMath y calcula sobre el importe del
concepto redondeado a centavos, multiplicado por tasa/100 y redondeado half-up.
Conserva tratamiento, tasa, base, importe del concepto e impuesto en el desglose.
Exento y no causa producen cero explícito, conservando sus clasificaciones.
No definido, tasa ausente o base distinta de `importe_concepto` bloquean la
proyección con un mensaje español. No se calcula impuesto sobre principal.

Regla de proyección QA para comisiones iniciales: el impuesto sigue la modalidad
del cargo. Separado incrementa el pago inicial; retenido disminuye efectivo;
financiado incrementa el saldo a amortizar y consume el límite del producto. El
interés se calcula sobre ese saldo financiado. La fila cero presenta todos los
impuestos iniciales pero no convierte los financiados/retenidos en pago separado.
El impuesto financiado se recupera como parte del capital, nunca se suma de nuevo
como impuesto de cada cuota. Cada cuota sí suma impuesto ordinario y de sus
comisiones periódicas, con acumulados obtenidos de las filas visibles.

La cuota base conserva el motor existente; el pago final puede variar al añadir
impuestos. Se mantiene separado el CAT base existente, calculado sobre sus flujos
obligatorios anteriores a impuestos. No se presenta como CAT del flujo fiscal
personalizado ni se cambia su algoritmo en este incremento.

La respuesta identifica uso de prueba/institucional, pero una declaración del
operador no acredita aprobación profesional. La interfaz muestra desglose, avisos
y error persistente, conserva captura y descarta respuestas obsoletas al modificar
el escenario. No calcula importes monetarios en JavaScript.

## Consecuencias y límites

- Sin migración, nuevos permisos ni banderas de operación.
- Reutiliza autorización del simulador; peticiones antiguas conservan sus números.
- No altera producto, snapshot ni paquete contractual. Los paquetes P4a y llamadas
  de originación continúan anteriores a impuestos hasta integrar explícitamente
  el nuevo motor en el siguiente incremento; no recalcular históricos.
- No calcula mora, causación por cobro, acreditamiento, retenciones tributarias,
  CFDI ni declaraciones. Retención de una comisión al desembolsar no equivale a
  retención fiscal a terceros.
- La aplicación de impuestos en pagos parciales, mora, anticipos y reversos se
  implementará en sus flujos, no se infiere de esta tabla de curso normal.
- Validación contable/jurídica pendiente para operación real: véase la lista
  profesional en `docs/how-to/qa-simulacion-fiscal.md`. No bloquea QA sintético.

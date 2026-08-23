# ADR 0007: Flujos de comisiones y redondeo de crédito simple

- Estado: aceptado
- Fecha: 2026-08-16

## Contexto

Una comisión inicial puede pagarse por separado, descontarse de la disposición o financiarse. Estos casos alteran de forma distinta el efectivo entregado, el saldo que genera intereses y los flujos usados para CAT. Además, una tabla operativa debe cerrar a centavos sin ocultar el cálculo matemático de la cuota.

## Decisión

- La fecha de disposición pertenece al escenario de simulación. Su valor inicial usa la zona horaria configurada para la financiera.
- Las comisiones iniciales obligatorias declaran `pago_separado`, `descuento_desembolso` o `financiada`.
- Una comisión financiada aumenta el saldo inicial y consume el límite máximo del producto. Una comisión descontada reduce el efectivo entregado. Una comisión pagada por separado no aumenta el saldo.
- Las comisiones porcentuales usan como base el monto solicitado antes de comisiones financiadas.
- El calendario V1 conserva semanal (+7 días), quincenal (+15 días) y mensual (ancla de día), con interés por días reales/360.
- El motor calcula con precisión decimal. La tabla operativa redondea half-up a centavos en cada periodo y ajusta el último capital para extinguir cualquier residual.
- La fila cero representa la disposición y separa saldo financiado, efectivo entregado y comisiones iniciales. Cada pago muestra saldo inicial, capital, interés, comisiones, saldo final y acumulados.
- El detalle de fórmulas y sustituciones solo se expone en entorno local o a Super Admin. No es una fórmula contractual.
- `incluye_cat` no es una decisión libre del cliente HTTP. El backend lo deriva: una comisión obligatoria de inicio o cada pago entra al CAT base; una opcional o condicionada a evento/liquidación queda fuera.
- El simulador puede incorporar, a elección del usuario, comisiones opcionales de inicio o cada pago. Estas modifican el escenario operativo, pero el CAT base se calcula por separado con cargos obligatorios.
- Los cargos por mora, incumplimiento, prepago y liquidación quedan fuera del CAT base porque su supuesto es cumplimiento oportuno. El caso de una comisión opcional requerida para obtener una tasa preferencial exige variantes comerciales y queda fuera de V1.
- El CAT permanece en un servicio separado y se presenta como `CAT base del producto`, sin IVA y separado de los totales de escenarios opcionales.

## Consecuencias

- Los contratos y originación deberán persistir la modalidad de cada comisión mediante el snapshot de versión.
- El saldo máximo se valida después de incorporar comisiones financiadas.
- Agregar otras bases de cálculo, convenciones, IVA, días inhábiles o periodicidades requiere una decisión explícita; no se infiere de catálogos de reporte crediticio.
- Las versiones históricas conservan el valor de CAT capturado en su snapshot; la derivación se aplica a productos y borradores nuevos sin reescribir historia.
- El motor de tabla es independiente de HTTP y Vue, por lo que las combinaciones de periodicidad y amortización se prueban sin base externa.

## Fuentes

- Banxico, Circular 21/2009: https://www.banxico.org.mx/marco-normativo/normativa-emitida-por-el-banco-de-mexico/circular-21-2009/%7B29285862-EDE0-567A-BAFB-D261406641A3%7D.pdf
- Banxico, explicación del CAT: https://www.banxico.org.mx/sistema-financiero/d/%7B5BD610E5-EE24-04AA-A21E-53B2176C2228%7D.pdf
- Héctor Manuel Vidaurri Aguirre, *Matemáticas financieras*, 6.ª ed., Cengage Learning, capítulo de amortización.

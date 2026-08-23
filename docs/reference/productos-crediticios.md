# Productos crediticios

## Alcance V1

El catálogo es global para la instalación. Crédito simple admite montos, tasas anuales, días de tolerancia de mora, periodicidades semanal/quincenal/mensual, cuota nivelada o capital fijo, prepago, liquidación y comisiones del catálogo interno. Crédito revolvente permanece diferido.

Las periodicidades de Buró de Crédito o Círculo de Crédito son códigos de reporte. No amplían por sí mismas las frecuencias contractuales que soporta el motor V1.

## Convenciones de cálculo

- Dinero y tasas se procesan como decimales, nunca como `float`.
- Las tasas se capturan como porcentaje nominal anual: `36` significa 36 %, no `0.36`.
- El factor del periodo es `tasa_anual / 100 × días_reales / 360`.
- En cuota nivelada con factores variables, la cuota exacta resuelve el valor presente de los pagos. La tabla redondea half-up a centavos por periodo y el último capital extingue el saldo.
- En capital fijo se divide el principal entre el número de pagos y se aplica el mismo criterio de residual final.
- El plazo es número de pagos. Las fechas avanzan +7 días, +15 días o por ancla mensual. El fin de mes permanece como fin de mes; un día inexistente se ajusta solo ese mes y luego recupera el ancla. V1 no ajusta días inhábiles.
- La mora se configura como tasa anual y se calcula sobre capital vencido cuando el módulo de cobranza la aplique.

## Disposición y comisiones

La fecha de disposición forma parte del escenario; el formulario inicia con la fecha de la financiera. La tabla incluye una fila cero para distinguir:

- saldo financiado;
- efectivo entregado al cliente;
- comisión pagada por separado;
- comisión descontada de la disposición;
- comisión financiada.

Una comisión financiada genera intereses y cuenta contra el monto máximo. Las comisiones porcentuales usan el monto solicitado antes de sumar comisiones financiadas. Cada pago muestra saldo inicial, capital, interés, comisiones, pago total, saldo final y acumulados.

`Obligatoria` indica que el cargo se aplica sin contratar un servicio adicional. `Incluir en CAT base` es un indicador de solo lectura derivado por el servidor:

- obligatoria al inicio o en cada pago: incluida;
- opcional al inicio o en cada pago: excluida y seleccionable en el simulador;
- evento, mora, incumplimiento, prepago o liquidación: excluida y no simulada en la tabla base.

Una opcional seleccionada modifica los flujos, intereses y costo total del escenario. No modifica el CAT base. Las comisiones opcionales asociadas a una tasa preferencial requieren variantes comerciales y no están soportadas en V1.

## Versiones

`borrador → programada/activa → retirada`. Solo el borrador sin uso es editable. Programar vuelve inmutable la versión inmediatamente; la versión activa anterior continúa disponible hasta la fecha empresarial indicada. `productos:activar-versiones` revisa vigencias cada cinco minutos.

Retirar una versión únicamente la excluye de nuevas originaciones. Los créditos existentes conservan el snapshot y pueden seguir consultándose o simularse históricamente.

Backlog 04 debe llamar `ProductoVersionService::registrarUso($version, 'solicitudes', $id)` dentro de la transacción de captura.

## CAT y diagnóstico

El CAT se muestra solo cuando la versión indica que aplica. Incluye los flujos obligatorios del curso normal; los cargos opcionales, IVA, mora, incumplimiento y prepago quedan fuera. Se etiqueta como `CAT base del producto`, es informativo y requiere revisión legal antes de uso contractual o publicitario.

En local o para Super Admin, el simulador permite abrir “Fórmulas y sustituciones”. Allí se muestran convenciones, factores y valores intermedios para diagnóstico; esa explicación no sustituye documentación contractual ni regulatoria.

## Permisos

`read`, `create`, `update`, `activate`, `retire`, `version`, `simulate` y `manage commissions` sobre `productos-crediticios`.

## Fuentes

- Banxico, Circular 21/2009: https://www.banxico.org.mx/marco-normativo/normativa-emitida-por-el-banco-de-mexico/circular-21-2009/%7B29285862-EDE0-567A-BAFB-D261406641A3%7D.pdf
- Banxico, explicación del CAT: https://www.banxico.org.mx/sistema-financiero/d/%7B5BD610E5-EE24-04AA-A21E-53B2176C2228%7D.pdf
- CONDUSEF, glosario RECO: https://registros.condusef.gob.mx/reco/glosario.php
- Héctor Manuel Vidaurri Aguirre, *Matemáticas financieras*, 6.ª ed., Cengage Learning, capítulo de amortización.

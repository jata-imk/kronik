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
- El plazo es número de pagos. Las fechas avanzan +7 días, +15 días o por ancla mensual. V1 no ajusta días inhábiles.
- La mora se configura como tasa anual y se calcula sobre capital vencido cuando el módulo de cobranza la aplique.

## Disposición y comisiones

La fecha de disposición forma parte del escenario; el formulario inicia con la fecha de la financiera. La tabla incluye una fila cero para distinguir:

- saldo financiado;
- efectivo entregado al cliente;
- comisión pagada por separado;
- comisión descontada de la disposición;
- comisión financiada.

Una comisión financiada genera intereses y cuenta contra el monto máximo. Las comisiones porcentuales usan el monto solicitado antes de sumar comisiones financiadas. Cada pago muestra saldo inicial, capital, interés, comisiones, pago total, saldo final y acumulados.

## Versiones

`borrador → programada/activa → retirada`. Solo el borrador sin uso es editable. `productos:activar-versiones` procesa vigencias diariamente.

Backlog 04 debe llamar `ProductoVersionService::registrarUso($version, 'solicitudes', $id)` dentro de la transacción de captura.

## CAT y diagnóstico

El CAT se muestra solo cuando la versión indica que aplica. Incluye los flujos obligatorios marcados para CAT; los cargos opcionales o por eventos no ocurridos quedan fuera. Es informativo, sin IVA y para comparación; requiere revisión legal antes de uso contractual o publicitario.

En local o para Super Admin, el simulador permite abrir “Fórmulas y sustituciones”. Allí se muestran convenciones, factores y valores intermedios para diagnóstico; esa explicación no sustituye documentación contractual ni regulatoria.

## Permisos

`read`, `create`, `update`, `activate`, `retire`, `version`, `simulate` y `manage commissions` sobre `productos-crediticios`.

## Fuentes

- Banxico, Circular 21/2009: https://www.banxico.org.mx/marco-normativo/normativa-emitida-por-el-banco-de-mexico/circular-21-2009/%7B29285862-EDE0-567A-BAFB-D261406641A3%7D.pdf
- Banxico, explicación del CAT: https://www.banxico.org.mx/sistema-financiero/d/%7B5BD610E5-EE24-04AA-A21E-53B2176C2228%7D.pdf
- CONDUSEF, glosario RECO: https://registros.condusef.gob.mx/reco/glosario.php
- Héctor Manuel Vidaurri Aguirre, *Matemáticas financieras*, 6.ª ed., Cengage Learning, capítulo de amortización.

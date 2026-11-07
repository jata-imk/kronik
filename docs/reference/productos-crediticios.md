# Productos crediticios

## Alcance V1

El catálogo es global para la instalación. Crédito simple admite montos, tasas anuales, días de tolerancia de mora, periodicidades semanal/quincenal/mensual, cuota nivelada, capital fijo o Bullet, prepago, liquidación y comisiones del catálogo interno. Crédito revolvente permanece diferido.

Las periodicidades de Buró de Crédito o Círculo de Crédito son códigos de reporte. No amplían por sí mismas las frecuencias contractuales que soporta el motor V1.

## Convenciones de cálculo

Métodos, periodicidades, Act/360 y aplicación del prepago proceden del
[catálogo backend de capacidades](core-crediticio.md#catálogo-de-capacidades).
El editor conserva sus valores; al desactivar prepago parcial se deshabilitan sus
dependencias sin borrarlas. Versiones con evidencia no soportada se consultan con
advertencia, sin habilitar cálculo ni sustituir sus códigos.

`reglas.convencion_interes` conserva la selección contractual. Act/360 sigue siendo
la única opción habilitada; el [punto de extensión de convenciones](core-crediticio.md#convenciones-de-días)
prepara otras bases sin ofrecerlas todavía en el editor ni cambiar contratos.

Las activaciones/programaciones nuevas congelan `motor_calculo_version = 2`.
Las versiones históricas sin marca reproducen el motor 1; para nuevas condiciones
corregidas se duplica/activa una versión, sin editar historia. El contrato exacto,
los acumuladores y los límites están en [versión del motor](core-crediticio.md#contratos-históricos-y-versión-de-motor).

- Dinero y tasas se procesan como decimales, nunca como `float`.
- Las tasas se capturan como porcentaje nominal anual: `36` significa 36 %, no `0.36`.
- El interés Act/360 equivale a `capital × tasa_anual × días_reales / 36000`.
  El motor 2 conserva el numerador y evita dividir/truncar días antes de multiplicar.
- En cuota nivelada con factores variables, la cuota exacta resuelve el valor presente de los pagos. La tabla redondea half-up a centavos por periodo y el último capital extingue el saldo.
- En capital fijo se divide el principal entre el número de pagos y se aplica el mismo criterio de residual final.
- El motor 2 rechaza combinaciones que generen capital no positivo o superior al
  saldo en los métodos actuales; no devuelve tablas con intereses/capital negativos
  para simular una amortización inválida. Bullet permite explícitamente capital intermedio cero y principal final positivo.
- El plazo define periodos de duración; en Bullet al vencimiento no implica pagos intermedios. Las fechas avanzan +7 días, +15 días o por ancla mensual. El fin de mes permanece como fin de mes; un día inexistente se ajusta solo ese mes y luego recupera el ancla. V1 no ajusta días inhábiles.
- La mora usa las bases contractuales compatibles con cada método; las tres bases Bullet y su disparador se describen en el contrato del core.

## Preparar condiciones Bullet

El editor existente prepara y activa/programa Bullet con condiciones explícitas.
La versión activa y vigente está disponible en Solicitudes; borradores compatibles
pueden simularse. Las condiciones se congelan al activar/programar. La habilitación
del ciclo se documenta en [ADR 0042](../explanation/adr-0042-habilitacion-ciclo-bullet-qa.md),
que sustituye el bloqueo provisional de ADR 0039. Ver [recorrido QA](../how-to/qa-bullet.md).

| Condición de reglas | Valores compatibles para Bullet |
| --- | --- |
| `metodos_amortizacion` | Solo `bullet` en esta etapa; sin mezcla con métodos actuales |
| `politica_interes_bullet` | `periodicos` o `al_vencimiento`, selección explícita |
| `base_ordinaria` | `saldo_insoluto`; no se habilita Flat |
| `base_moratoria` | `saldo_insoluto`, `capital_inicial`, `obligacion_vencida`; selección explícita |
| `aplicacion_prepago` | `conservar_fecha`, sin reducción de plazo |

La frecuencia y el número de periodos definen duración/vencimiento del capital.
En periódicos también determinan pagos de interés; al vencimiento no son pagos
intermedios. Se conserva el calendario actual. Las bases de mora se preparan con
el disparador aprobado por obligación vencida impagada y el cálculo vigente. «Usar base ordinaria para mora» copia el valor efectivo cuando es
compatible, sin almacenar una referencia a la otra base.

Cambiar método limpia condiciones incompatibles y avisa; no selecciona base
moratoria automáticamente. El backend rechaza incompatibilidades incluso con
payload manipulado. Guardar, actualizar y copiar conservan condiciones por
versión y usan los eventos auditables existentes. La consulta presenta valores
originales o «Sin registrar», sin rellenar condiciones ausentes.

Migración `2026_11_05_220000_add_bullet_conditions_to_product_rules`: dos columnas
nullable, sin backfill ni cambios a snapshots/hashes. Solo ejecutada en bases de
prueba aisladas para esta entrega. Nuevas activaciones de métodos actuales
explicitan la base ordinaria cuando fue guardada; ausencia histórica se interpreta
sin reescribir evidencia. No hay seeders ni variables nuevas.

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

## Consulta y paginación

El [contrato transversal de contexto y listados](contexto-sucursal-consultas.md)
describe parámetros de consulta, paginadores y selección independiente de la
página, conservando los permisos y la naturaleza global o por equipo del módulo.

## Consulta completa

Ver versión abre el mismo editor en consulta, también para borradores sin permiso
de actualizaci?n. Incluye condiciones, periodicidades, reglas, comisiones, fiscalidad
y atraso, preservando valores históricos. Ver [contrato](consulta-edicion-historicos.md).
Al volver por historial a una URL sin modo de formulario se cierra la consulta;
las respuestas de validación conservan la captura del mismo borrador editable.

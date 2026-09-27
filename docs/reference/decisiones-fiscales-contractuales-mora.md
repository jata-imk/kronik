# Decisiones funcionales: fiscalidad, contratos y gracia de mora

- Aprobadas por el usuario: 2026-09-27.
- Fuente de alcance: [iniciativa 04–07 en Notion](https://app.notion.com/p/3e161db7db7f817a8c89ec30767d4666).
- Estado: especificación aceptada para P4–P6; no acredita implementación ni habilitación real.

Se aplica el diseño vigente: monolito, versiones inmutables, revisiones de solicitud,
formalización separada de desembolso y movimientos trazables. Este acuerdo no
modifica arquitectura, estados, estructura de base de datos ni flujo de créditos.
Es una decisión de producto, no una determinación fiscal o jurídica universal.

## Tratamiento fiscal por institución, producto y concepto

Los intereses ordinarios, los moratorios y cada comisión tienen configuración
independiente. No se impone un tratamiento universal a todas las instituciones
ni se hereda implícitamente el tratamiento de una comisión a las demás.

| Tratamiento | Significado funcional |
| --- | --- |
| Gravado | Requiere tasa de impuesto y base de cálculo explícitas. |
| Exento | Decisión fiscal explícita; no se deduce de un dato faltante. |
| No causa impuesto | Se conserva como clasificación distinta de exento. |
| No definido | Dato pendiente; no se convierte en exento, no causa ni tasa cero. |

Cuando corresponda IVA al financiamiento, la base configurada considera los
intereses y demás contraprestaciones distintas del principal, conforme al
tratamiento aplicable definido por la institución. El capital prestado no se
toma automáticamente como base de IVA. No confundir una comisión calculada como
porcentaje del principal con el impuesto calculado sobre esa comisión.

La definición definitiva corresponde a la institución, idealmente validada por
su contador o asesor fiscal. QA puede utilizar configuraciones fiscales de prueba
identificadas expresamente; no constituyen validación para operación real.
Un tratamiento pendiente debe comunicarse como tal y no producir importes fiscales
definitivos basados en supuestos silenciosos. Se conservan las puertas de
habilitación ya acordadas y el versionado existente.

## Contratos y conservación histórica

Se reutilizan las plantillas contractuales del sistema. Al formalizar deben quedar
congeladas las condiciones, la tabla correspondiente y la versión contractual usada.
Cambios posteriores en productos o plantillas no modifican créditos formalizados.
Este requisito extiende a P4 los patrones existentes: no afirma que P4 ya esté implementado.

Cada institución proporciona o aprueba su contenido contractual y autoriza su uso.
Activar una plantilla, generar un PDF o usar un perfil Super Admin no acredita por
sí mismo aprobación jurídica. Generar el documento tampoco equivale a firmarlo.
Se conserva la firma autógrafa digitalizada del alcance inicial.

Mientras falte aprobación jurídica, QA puede usar plantillas de prueba con
identificación visible: **«Documento de prueba QA — sin validez contractual»**.
Esto no concede autorización para usar esas plantillas en operación real.

## Días de gracia de mora

Los días siguen siendo configurables por producto. El comportamiento A es el
predeterminado y recomendado por la decisión de producto; B es una alternativa
seleccionable expresamente. No se cambia la tasa ni la base moratoria ya acordada.

Para una cuota que vence el día 10 con tres días de gracia:

| Situación | A: gracia efectiva | B: retroactividad condicionada |
| --- | --- | --- |
| Pago que cubre la cuota durante los días 11–13 | Sin mora. | Sin mora. |
| Se supera la gracia con capital vencido pendiente | Mora desde el 14, sin cobrar días 11–13. | Mora retroactiva desde el 11. |
| Pago posterior al periodo de gracia | Solo días posteriores a la gracia. | Incluye días desde el primero posterior al vencimiento. |

La alternativa B no significa cobrar mora durante la gracia a quien paga dentro
de ella. Tampoco autoriza contabilizar pagos o desembolsos con fechas retroactivas:
son decisiones diferentes. Las condiciones históricas se conservan según el
versionado existente; no se reescriben productos o créditos anteriores.

## Criterios de aceptación para los incrementos de implementación

- Probar los cuatro tratamientos fiscales sin equiparar sus clasificaciones.
- Exigir tasa y base cuando el concepto sea gravado; comprobar independencia
  entre intereses ordinarios, moratorios y varias comisiones del mismo producto.
- Comprobar que ningún dato fiscal ausente se transforme silenciosamente en cero
  o exención y que el principal no se use automáticamente como base de IVA.
- Mostrar la identificación de prueba en configuración fiscal y documentos QA.
- Verificar que cambiar producto/plantilla no altere las condiciones, tabla o
  versión contractual ya formalizadas; distinguir PDF generado de evidencia firmada.
- Probar A predeterminado y B explícito; vencimiento el 10, pago dentro de 11–13,
  cruce de la gracia al 14 y pago posterior. Agregar gracia cero, cambio de mes
  y año bisiesto, conservando las convenciones del motor existente.
- Mensajes esperados: «Define el tratamiento fiscal de este concepto» y
  «Indica la tasa y la base del impuesto para este concepto gravado».

Estos son criterios pendientes de implementar y probar, no resultados de pruebas ejecutadas.

## Lo que este acuerdo no resuelve

Siguen pendientes coexistencia de interés ordinario y moratorio, fechas retroactivas
de operaciones, excedentes y devoluciones. Se mantienen las decisiones existentes
sobre reversos compensatorios, orden de aplicación, actual/360 y precisión decimal.
No se define aquí una nueva prioridad de aplicación para impuestos ni se resuelven
por inferencia casos fiscales o de parcialidad no especificados; deberán detallarse
antes de implementar los cálculos afectados.

La configuración fiscal concreta y el contenido contractual aprobado siguen siendo
responsabilidades de cada institución, no preguntas de producto ya sin respuesta.

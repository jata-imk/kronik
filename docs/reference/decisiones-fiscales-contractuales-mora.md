# Decisiones funcionales: fiscalidad, contratos y gracia de mora

- Aprobadas por el usuario: 2026-09-27.
- Fuente de alcance: [iniciativa 04–07 en Notion](https://app.notion.com/p/3e161db7db7f817a8c89ec30767d4666).
- Estado: especificación aceptada para P4–P6. Configuración fiscal por versión
  implementada como prerrequisito (ADR 0016); cálculo fiscal y habilitación real pendientes.

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
de ella. Por sí misma no autoriza fechas retroactivas de operaciones; la autorización
de pagos pasados se registra por separado más abajo, sin extenderla a desembolsos.
Las condiciones históricas se conservan según el
versionado existente; no se reescriben productos o créditos anteriores.

## Continuación: atraso, pagos pasados y excedentes

Decisiones adicionales expresas del usuario del 27/09/2026:

- **Coexistencia configurable, únicamente dos modalidades en V1:**
  - Ambos intereses: durante la mora siguen devengándose ordinario y moratorio,
    cada uno según su respectiva base.
  - Moratorio sustituye al ordinario: desde que una obligación entra en mora,
    deja de generar ordinario bajo la regla definida y empieza a generar moratorio.
  La selección corresponde al contrato/producto, no se deduce de sus tasas.
  Se conservan versionado, condiciones congeladas, bases y convenciones existentes.
  Límites y comportamientos especiales quedan como extensión futura del motor,
  no como una tercera modalidad incompleta ni un bloqueo de definición para V1.
- **Pagos con fecha pasada admitidos:** permitir registrar hoy un pago recibido
  antes. Conservar fecha efectiva del pago y fecha real de captura/actor sin
  falsificar el momento del registro. No confundirlo con mora B ni extender este
  permiso a desembolsos retroactivos. En V1 se bloquea el pago con fecha pasada
  si ya existen movimientos del mismo crédito con fecha efectiva posterior a la
  del pago propuesto. No se implementa recálculo de movimientos posteriores ni
  se permite eludir el bloqueo registrando una fecha efectiva distinta de la real.
  Sin movimientos posteriores puede admitirse, sujeto a las demás validaciones
  del crédito. Los controles de periodos cerrados, cuando apliquen, no se omiten
  por esta autorización.
  Se mantiene la inmutabilidad y corrección trazable ya acordada: no sobrescribir
  ni eliminar movimientos para hacer encajar una fecha pasada.
- **Excedentes fuera del sistema inicialmente:** no implementar por ahora una
  cuenta de saldo a favor ni convertir automáticamente el excedente en anticipo.
  No ocultar ni recortar silenciosamente el importe recibido, ni inventar pagos
  o devoluciones. La gestión de la excepción se realiza fuera del sistema.
  El cierre de este alcance no modifica los reversos compensatorios ya aprobados
  ni adelanta P8 de anticipos/liquidación.

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
- Verificar que la coexistencia de intereses provenga de condiciones explícitas
  del producto/contrato conservadas históricamente, no de una regla universal.
- En un pago pasado, conservar fecha efectiva y fecha real de captura distintas;
  comprobar trazabilidad e idempotencia sin editar movimientos anteriores.
- Probar ambas modalidades de intereses con sus respectivas bases y transición
  a mora de la obligación; no ofrecer límites especiales en V1.
- Pago propuesto del día 5 con movimiento del mismo crédito del día 10: rechazar
  sin crear pago ni modificar saldos/movimientos. Mensaje:
  «No puedes registrar este pago con esa fecha porque el crédito tiene movimientos posteriores».
- Pago pasado sin movimientos posteriores: admitir si cumple las demás validaciones.
  Verificar el bloqueo en servidor, incluida la concurrencia con otro movimiento.
- Comprobar que no se cree saldo a favor o anticipo automático por un excedente,
  ni se descarte silenciosamente parte del importe comunicado por el operador.

Estos son criterios pendientes de implementar y probar, no resultados de pruebas ejecutadas.

## Lo que este acuerdo no resuelve

Quedan cerradas las dos modalidades V1 de coexistencia, la admisión de pagos
pasados solo sin movimientos posteriores y la exclusión inicial de excedentes.
Límites especiales y recálculo por inserción de pagos entre movimientos históricos
no forman parte de V1. No se han autorizado
desembolsos retroactivos ni un flujo nuevo de devoluciones. Se mantienen las
decisiones sobre reversos compensatorios, orden de aplicación, actual/360 y precisión decimal.
La combinación con gracia y los controles de periodos cerrados deben concretarse
según las reglas aplicables antes de habilitar los casos afectados; no inventarlos.
No se define aquí una nueva prioridad de aplicación para impuestos ni se resuelven
por inferencia casos fiscales o de parcialidad no especificados; deberán detallarse
antes de implementar los cálculos afectados.

La configuración fiscal concreta y el contenido contractual aprobado siguen siendo
responsabilidades de cada institución, no preguntas de producto ya sin respuesta.

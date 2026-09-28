# ADR 0016: configuración fiscal dentro de la versión de producto

- Estado: aceptado para configuración, previo al motor fiscal de P4b.
- Fecha: 2026-09-27.
- Precedentes: ADR 0008, 0013 y 0015; decisiones funcionales fiscales aceptadas.

## Contexto

Cada institución debe definir independientemente los impuestos de intereses
ordinarios, moratorios y comisiones. El simulador y los paquetes QA existentes
son anteriores a impuestos. No se puede interpretar ausencia de configuración
como exención ni reescribir versiones ya usadas.

## Decisión

Se extiende la versión existente, sin nuevo módulo ni nuevos estados de crédito:
`producto_versiones.fiscalidad` contiene uso (`prueba`/`institucional`), referencia
y conceptos `ordinario`/`moratorio`. Cada comisión conserva su propio JSON
`fiscalidad`. Son columnas anulables, sin rellenado de históricos.

Cada concepto contiene `tratamiento`, `tasa` y `base`. Tratamientos permitidos:
`no_definido`, `gravado`, `exento`, `no_causa`. Solo gravado acepta y exige tasa
y base; no se infiere ninguna de ellas. El porcentaje admite 0–100 y ocho
decimales como límite técnico de captura, no como regla legal. Cero explícito
no equivale a ausencia. La base soportada en este incremento es
`importe_concepto`: importe íntegro del interés o comisión, nunca el principal.
Bases especiales no se simulan mediante fórmulas libres: se mantienen pendientes
para extender el motor con sus pruebas antes de utilizarlas.

El uso aplica al conjunto de conceptos. Institucional requiere una referencia
de respaldo declarada por el operador; no implica aprobación fiscal automática.
Una instalación sigue correspondiendo a una institución, según el diseño actual.

Se reutilizan permisos de lectura, creación, edición y versionado de productos.
La pestaña Fiscalidad edita borradores; un drawer consulta cualquier versión.
La validación se comparte entre request y servicio, con errores en español.
La duplicación copia configuración y la activación la incorpora al snapshot/hash.
Las comisiones también rechazan mutaciones Eloquent de versiones no editables;
las escrituras deben continuar pasando por el servicio transaccional.

Omitir campos fiscales desde clientes anteriores conserva lo guardado. Para
comisiones se identifica el concepto estable antes de recrear las filas del
borrador. Una eliminación expresa del concepto sigue eliminando su configuración
del borrador, no del histórico. Actualizar bloquea la versión y vuelve a verificar
su editabilidad dentro de la transacción.

## Consecuencias y límites

- No cambia simulación, CAT, paquete QA, firma, desembolso ni pagos.
- Se permite guardar y activar configuración incompleta; esto no habilita dinero.
  El motor/formalización futuros deben bloquear conceptos aplicables sin definir
  y distinguir prueba de operación institucional validada.
- Los snapshots anteriores no se recalculan. Los nuevos pueden conservar
  `fiscalidad: null`, que significa pendiente, no impuesto cero.
- La fiscalidad no agrega permisos para consultar información reservada de SIC/PLD.
- El rollback elimina únicamente las columnas nuevas y perdería su configuración;
  hacer respaldo antes de revertir en un entorno con datos útiles.
- Pendientes: cálculo fiscal decimal, bases especiales, autorización contractual,
  firma, desembolso y aplicación de pagos. No promover paquetes QA mediante flags.

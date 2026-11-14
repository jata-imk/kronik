# Core crediticio: responsabilidades y contratos de evolución

## Bullet: cálculo, condiciones y obligaciones

La estrategia Bullet distribuye cero capital intermedio y liquida principal
al final. `ExigibilidadInteresBullet` proyecta intereses periódicos o al vencimiento
por separado; conserva numeradores y los intervalos sin pago para CAT. Requiere
motor 2, sin ampliar compatibilidad histórica. Las estrategias actuales mantienen
sus guardas de capital positivo y su serialización.

Bullet está habilitado en el catálogo operativo para motor 2/Act/360 y ambas
políticas, con las combinaciones explícitas de las [condiciones](productos-crediticios.md#preparar-condiciones-bullet).
Simulación, CAT, solicitud, paquete, firma y desembolso conservan la misma política.
La validación del ciclo y su estado de integración/despliegue se registran en la
[Agent Note](../agent-notes/core-crediticio-extensible.md), sin equiparar pruebas
aisladas con aceptación en QA compartido. Ver [ADR 0042](../explanation/adr-0042-habilitacion-ciclo-bullet-qa.md).

El adaptador de tabla recibe ?PoliticaInteresBullet, obligatoria para Bullet y
ausente para los otros métodos. Aplica exigibilidad antes de serializar, también
en los flujos CAT, conservando intervalos sin pago. El catálogo excluye Bullet del
motor histórico; no añade defaults a evidencias anteriores. Activación/programación
y uso validan combinaciones antes de persistir. Las condiciones y los hashes
de créditos firmados permanecen inmutables.

### Obligaciones y mora Bullet

`MotorPagos` identifica Bullet por el método exacto del cronograma y exige
motor 2, Act/360 y condiciones explícitas compatibles. Conserva capital inicial,
método y evidencia diaria de gracia en el estado, sin agregar tablas ni cambiar
el algoritmo de los contratos anteriores. La ausencia de esos datos o de un
numerador ya utilizado se rechaza; no se reconstruyen desde importes redondeados.

El ordinario se acumula sobre todo el capital insoluto en el cargo de su fecha
exigible: siguiente vencimiento periódico o vencimiento final. Los importes
proyectados de la tabla no se convierten en deuda. Hasta el vencimiento el cargo
no admite pagos ordinarios. Pagar interés no amortiza capital.

Desde el día posterior al vencimiento, un remanente de capital o interés ordinario
activa atraso. `capital_vencido` conserva su significado; `interes_vencido`,
`obligacion_vencida` y `dias_atraso` distinguen interés impagado con capital futuro.
La gracia afecta el cargo moratorio, sin ocultar los días de atraso.

| Base moratoria | Importe diario antes del nuevo devengo |
| --- | --- |
| `obligacion_vencida` | Suma de capital e interés ordinario vencidos impagados de las obligaciones elegibles |
| `saldo_insoluto` | Principal pendiente total, una sola vez si hay alguna obligación elegible |
| `capital_inicial` | Principal inicial conservado, una sola vez si hay alguna obligación elegible |

Comisiones, impuestos y moratorio se mantienen separados y no engrosan esas
bases. Los impuestos siguen la fiscalidad y distribución proporcional de cada
concepto. Sin remanente de capital/interés vencido no se genera nueva mora;
el moratorio pendiente por sí solo no vuelve a dispararla. Un residuo exclusivo
de impuesto sigue siendo exigible, sin activar mora ni suspender el ordinario.

Gracia efectiva cobra desde el primer día posterior a la gracia. Retroactiva
conserva la base real de cada día y la cobra al incumplirse la gracia, descartando
las obligaciones cubiertas durante ella. Las bases globales no se repiten por
varias cuotas. Con `ambos` continúa el ordinario; con `sustituye` se suspende el
ordinario del principal desde la activación. La sustitución retroactiva retira
solo los numeradores ordinarios de los días de gracia, del cargo donde se
generaron; conserva el interés que ya originó el atraso. Si la gracia cruza otro
vencimiento, también retira ese ordinario provisional de las bases diferidas.

Se mantiene el límite B+sustitución: cubrir completamente la obligación afectada
durante gracia; no devolver una distribución parcial que luego requiera compensar
interés pagado. El anticipo y la liquidación internos conservan este límite.
El ciclo habilitado reutiliza estos motores con las condiciones congeladas.
BulletCicloQaTest origina por servicios reales; las pruebas sintéticas anteriores
se conservan para aislar obligaciones y mora. MariaDB multiconexión y E2E aportan
evidencia complementaria, registrada por commit en la Agent Note.
Decisión y consecuencias en [ADR 0040](../explanation/adr-0040-obligaciones-mora-bullet.md).

El resumen Bullet añade interes_devengado_pendiente e interes_no_exigible:
ordinario generado pendiente de pago, sin impuestos, total y con vencimiento
posterior a la consulta respectivamente. Reutiliza interes_vencido,
obligacion_vencida y dias_atraso, este último por obligaciones. Consulta y
previa no persisten devengos ni calendarios.

### Anticipo y liquidación Bullet internos

El anticipo parcial cubre exigibles y cargos aplicables antes de reducir principal.
El ordinario todavía no exigible conserva cargo, numerador y vencimiento; no se
cobra obligatoriamente, elimina ni capitaliza. La obligación final conserva su
fecha y refleja el capital pendiente. No se permite reducir plazo Bullet.

El estado conserva claves de cuotas/cargos, `capital_inicial`, pagos, impuestos
y control de gracia. Cada registro contabiliza solo diferencias de devengos;
preservar el interés no exige crear otro cargo ni otro movimiento por su saldo
ya contabilizado. Un anticipo posterior o en la misma fecha no reinicia contadores.

La nueva versión del cronograma proyecta el nuevo saldo desde el día siguiente
al abono y conserva fechas/números futuros. Suma numeradores anteriores y futuros
por obligación antes de redondear; al vencimiento acumula los intervalos sin cobro.
Incluye el devengado conservado pendiente y la fiscalidad/comisiones aplicables,
sin convertir la proyección en deuda registrada. Los métodos anteriores conservan
el cobro de devengado y regeneración definidos en ADR 0023.

Liquidar cubre capital, devengos, cargos e impuestos aplicables, excluyendo interés
y comisiones periódicas futuros. Exige coincidencia exacta con la cotización y
detiene la generación posterior. El reverso admisible restaura estado y calendario
mediante compensaciones y nueva versión, incluida reapertura tras liquidación.

Se mantienen las interfaces y eventos de pago/reverso, permisos separados y
restricciones de fecha/QA existentes. Previa y consulta no escriben. La evidencia
incluye ahora originación real aislada, además de casos sintéticos; los resultados
financieros concurrentes se acreditan por separado en MariaDB. Decisión en [ADR 0041](../explanation/adr-0041-anticipos-liquidacion-bullet.md).

## Catálogo de capacidades

Integrado mediante [PR #64](https://github.com/jata-imk/kronik/pull/64) en
`ee94025`, con árbol idéntico al head aprobado `efdddf4`. El estado posterior de
habilitación y QA se conserva en la Agent Note de la iniciativa.

`CatalogoCapacidadesCredito` declara opciones `{value, label, ayuda}` para métodos,
periodicidades, convenciones y aplicaciones del prepago. `paraMotor()` mantiene
compatibilidad explícita para motores 1 y 2; solo Act/360 está habilitada.
La guarda y el catálogo comparten `VersionMotorCredito::admiteConvencion`.

Productos y captura de Solicitudes reciben `capacidadesCredito` con `catalogo`
y `configuracion` (motor actual, opciones y predeterminados actuales). Cada versión
incluye `capacidades`: `motor_calculo_version`, `opciones` y `problemas`.
Las periodicidades incluyen límites y plazo predeterminado. Estos datos de
presentación quedan fuera del snapshot y no constituyen condiciones persistidas.

El resolvedor usa el snapshot existente; para borradores sin snapshot usa
reglas/periodicidades y motor actual. Ausencia histórica de marcas conserva motor 1
y Act/360; nulos explícitos o códigos desconocidos bloquean operación y muestran
advertencia en consulta. Se conservan valores originales, snapshots y hashes.

Guardar/copiar productos y congelar versiones valida capacidades en servicio.
Simular/enviar solicitudes comprueba las opciones contractuales del snapshot.
Se conserva el guardado incompleto del borrador. Cambiar producto limpia selecciones
incompatibles con aviso. Quincenal conserva cada 15 días, sin habilitar catorcenal
ni quincena calendario. Prepago conserva su aplicación contractual por operación.
Decisión: [ADR 0035](../explanation/adr-0035-catalogo-capacidades-credito.md).
Estado y evidencia en la Agent Note; este contrato no acredita despliegue.

Dirección aprobada el 29/10/2026: [ADR 0032](../explanation/adr-0032-core-crediticio-extensible.md).
El [roadmap de Notion](https://app.notion.com/p/40961db7db7f819a8af1d7c3cfec2df7)
conserva alcance, dependencias y fechas. Esta referencia no acredita nuevas
capacidades por sí sola. Integridad Act/360 y punto de extensión de convenciones
integrados en `main` el 31/10/2026: [PR #59](https://github.com/jata-imk/kronik/pull/59)
en `872d464` y [PR #60](https://github.com/jata-imk/kronik/pull/60) en `942541a`.
Solo Act/360 está habilitada. No hay despliegue acreditado; la arquitectura
completa y las capacidades futuras siguen pendientes. Evidencia en la
[Agent Note](../agent-notes/core-crediticio-extensible.md).

## Implementación actual y objetivo

| Responsabilidad | Base actual | Regla de evolución aprobada |
| --- | --- | --- |
| Producto/condiciones | Versiones y snapshots conservados | Congelar condiciones efectivas, capacidades y versión del motor |
| Estructura financiera | Crédito simple y desembolso único | Simple y línea revolvente; saldo global o por disposición |
| Amortización | Cuota nivelada y capital fijo | Estrategias reutilizables; Bullet con capital intermedio cero |
| Intereses | Motor histórico y motor 2 con numeradores; contrato de convenciones por fechas, solo Act/360 habilitada | Extender políticas independientemente de frecuencia de pago |
| Exigibilidad | Cargos fechados y atraso de capital | Obligaciones diferenciadas; intereses pueden vencer antes del capital |
| Clasificación comercial | Catálogo de productos | Nómina no impone otra fórmula ni otro configurador |
| Cobranza/recepción | Registro manual QA junto a aplicación | Retenciones, remesas, recepción y aplicación distinguibles |
| Consultas | Estado calculado desde evidencia cifrada | Proyecciones relacionales reconstruibles y fechadas |
| Auditoría | Cobertura técnica integrada por #72 y QA confirmada | Mantener matriz, contexto original y pruebas por entrega |

## Invariantes y límites de integración

- Importes y tasas viajan como decimales. El backend calcula resultados; la UI no
  recalcula intereses ni distribuciones de pago.
- La amortización distribuye principal; el interés define su costo; la exigibilidad
  determina cuándo se debe pagar. Ninguna dimensión sustituye a otra.
- Cuota base y total con impuestos/cargos no son equivalentes. Comisiones y
  fiscalidad permanecen por concepto; no inferir exención o IVA universal.
- El CAT es una estrategia separada y conserva sus supuestos específicos;
  días reales para devengo no implican reemplazar el CAT por una TIR de fechas.
- Una operación conserva previo, resultado, versión de cálculo, fecha efectiva,
  fecha de captura, actor y contexto. Las proyecciones no modifican esa evidencia.
- Consultar no registra devengos. Si se añaden avances/cortes programados, su
  idempotencia y contabilización deben impedir cobrar o registrar dos veces.
- Activar un producto nuevo no cambia contratos existentes. No usar condiciones
  comerciales actuales para reconstruir su historia.
- Los componentes puros no emiten eventos de auditoría. Aplicación conserva los
  eventos de negocio una vez por operación, también ante reintentos.

## Reutilización exigida

Una estrategia amortiza tanto un simple como una disposición con condiciones
equivalentes. Una línea puede contener disposición A nivelada y B Bullet si el
producto lo habilita; no se devenga simultáneamente sobre la línea y otra vez
sobre sus disposiciones. Las dos operativas revolventes comparten primitivas,
no necesariamente reglas de mínimo, calendario o distribución.

La disponibilidad distingue límite, principal comprometido y reservas aplicables.
Pagar intereses no devuelve capital al cupo. Revertir un pago que permitió otra
disposición requiere revisar dependencias de toda la línea; la restricción actual
de último pago de un simple no demuestra que esa corrección sea admisible.

## Recepción, Nómina y conciliación

```mermaid
flowchart LR
    R[Retención reportada] --> V[Verificación y evidencia]
    M[Remesa recibida] --> C[Conciliación]
    V --> C
    D[Recepción directa confirmada] --> A[Aplicación común]
    C --> A
    A --> O[Obligaciones y movimientos]
```

Las flechas representan responsabilidades propuestas, no módulos implementados.
El efecto contractual de una retención antes de recibir dinero debe definirse
explícitamente. «En tránsito» es estado operativo, no medio de recepción ni caja.
Separar calendario del trabajador, plazo de remesa y gracia del trabajador.

En una remesa: recibido = asignado + pendiente de asignar; impedir sobreasignación,
duplicación y aplicación dos veces al llegar dinero ya conciliado. La coexistencia
posterior de pagos directos exige resolver retención pendiente/remesa posterior y
excedentes con reglas aprobadas.

## Contratos históricos y versión de motor

`VersionMotorCredito` distingue algoritmo de formato. `motor_calculo_version` se
congela en snapshots nuevos de producto y se conserva en simulación y cronogramas
generados por anticipos. `MotorPagos::iniciar` rechaza producto/calendario de
versiones diferentes. Una marca desconocida o nula explícita se rechaza en español.

| Evidencia | Algoritmo |
| --- | --- |
| Snapshot histórico sin marca | 1: reproducción del algoritmo anterior, incluidos sus límites conocidos |
| Snapshot con marca 1 | Algoritmo histórico |
| Borrador sin snapshot | Simulación con motor actual 2 |
| Nueva activación o programación | Congela motor 2; no actualiza versiones anteriores |

El motor 2 conserva el numerador `capital × tasa_porcentual × días` y divide entre
36000 después de acumular. Tabla, devengo y anticipos comparten `InteresPorPeriodo`
y la estrategia `DiasReales360`; el refactor del 31/10 no cambia sus cifras.
Los campos de numerador del estado no son cantidades cobradas: permiten conservar
precisión entre consultas/pagos. Si se pierde un acumulador ya usado, el motor
rechaza avanzar; no lo inventa multiplicando un interés truncado.

En los dos métodos actuales, el motor 2 rechaza calendarios no crecientes,
principal inferior a un centavo y amortizaciones no positivas o superiores al
saldo. Esta guarda pertenece a los métodos actuales; no es una prohibición global
de capital cero para Bullet. Los mensajes se devuelven en `metodo` en español.

La ausencia de marca no se escribe sobre registros inmutables. No se recalcula
historia. Para originar con las correcciones de un producto histórico se debe
crear/activar una nueva versión; simular el anterior reproduce el motor 1. La
cartera previa puede conservar diferencias F-01/F-02; desplegar este código no
las corrige. El 04/11/2026 el usuario confirmó la revisión satisfactoria de su
cartera de uno o dos clientes sin crédito activo o de prueba, sin tratamiento
correctivo requerido. Se cierra ese seguimiento concreto, sin acreditar una
inspección nueva de BD ni generalizar el resultado a otras carteras. No cambiar
marcas, saldos o recibos manualmente para migrarla. Revisar compatibilidad del
código antes de retroceder una aplicación que ya conserve estados del motor 2.

Se conservan las restricciones vigentes de referencia reservada, fechas pasadas,
reverso y gracia. Su ampliación necesita decisión funcional y pruebas propias.

## Convenciones de días

Decisión: [ADR 0033](../explanation/adr-0033-convenciones-dias-base-fija.md).
`reglas.convencion_interes` sigue siendo el único campo contractual de selección
del producto. `ConvencionInteres` valida su valor y `ConvencionesDias` resuelve la
estrategia pura `ConvencionDias`, que cuenta entre fechas y declara la base anual
fija. `InteresPorPeriodo` combina esa información con capital y tasa porcentual,
sin decidir amortización, base de capital, periodicidad ni exigibilidad.

| Evidencia/entrada | Tratamiento |
| --- | --- |
| `dias_reales_360` | Única convención habilitada; días civiles reales, base 360 incluso en bisiestos |
| Ausencia de marca histórica | Interpretación Act/360 sin reescribir evidencia ni cambiar algoritmo |
| Nulo explícito, código desconocido o convención futura | Rechazo español en `convencion_interes`; no sustituir por Act/360 |
| Producto nuevo | El campo opcional acepta únicamente Act/360; omitirlo conserva el valor actual |
| Simulación/cronograma nuevo o regenerado | Explicita `convencion_interes`, conservando la del producto |

Guardar el producto valida `version.reglas.convencion_interes` mediante Form
Request y servicio. Activar valida las reglas antes de congelarlas; simular una
versión congelada toma esa selección del snapshot. Pagos y anticipos validan la
convención de ambas evidencias; avanzar valida la del estado antes de usar sus
acumuladores. Copiar una versión conserva la selección; no existe preferencia
global que cambie los contratos anteriores.

El contrato recibe fechas, no solamente un número de días. En Act/360 se cuentan
fechas civiles sin que cambios de hora alteren los días. Los acumuladores del motor
2 mantienen su significado y precisión. El contrato actual tiene denominador fijo;
no acredita soporte de Act/Act ni convenciones variables.

`VersionMotorCredito::validarConvencion` exige una combinación admitida en tabla,
inicio/avance de pagos y anticipos. Los motores 1 y 2 solo admiten Act/360; agregar
un caso al enum de convenciones no habilita su uso en esos algoritmos.

Act/365 Fixed y variantes específicas de 30/360 quedan pendientes. Antes de
habilitarlas, definir reglas y contexto contractual, compatibilidad de motor,
devengo por segmentos ante pagos/cambios de capital y equivalencia con la tabla.
En 30/360 no se debe presumir que sumar intervalos diarios equivale al periodo
completo. Probar febrero, fin de mes, bisiestos, periodos incompletos, pagos,
anticipos, liquidación y reversos. No basta cambiar el divisor o añadir un caso al
catálogo. El CAT conserva su cálculo independiente. La UI mantiene las opciones
actuales hasta contar con nuevas convenciones aprobadas y probadas.

## Definiciones pendientes antes de operación

| Capacidad | Definición necesaria |
| --- | --- |
| Bullet | Ciclo QA habilitado con dos políticas; estado de pruebas, integración y despliegue en Agent Note y guía QA |
| Revolvente | Mínimo, disponibilidad, reservas, distribución, corte/fecha límite y límites por disposición |
| Nómina | Reconocimiento entre descuento/recepción, remesa parcial/no recibida, reversos y bajas |
| Escalabilidad | Medición en entorno identificado con 10 mil créditos/líneas activos y disposiciones asociadas |

No elegir valores ni efectos pendientes por conveniencia de implementación.
Los ejemplos PDF son referencias de flujo, no resultados matemáticos obligatorios.

## Pruebas de aceptación de arquitectura

- Casos independientes de centavos, conservación de capital, tasas/plazos extremos,
  fin de mes y bisiestos; avance total frente a avance por intervalos.
- Equivalencia entre simple y disposición con condiciones iguales; combinación de
  estrategias autorizadas en disposiciones distintas.
- Pagos, anticipos, liquidación y reversos con conceptos/impuestos separados.
- Concurrencia multiconexión sobre MariaDB aislada; SQLite o secuencias de previas
  no acreditan contención simultánea.
- Reconstrucción de proyecciones, compatibilidad histórica, permisos/sucursales,
  Super Admin y auditoría sin metadatos sensibles.
- E2E y revisión escritorio/móvil por capacidad; métricas de consulta, memoria y
  tiempos con distribución de datos y hardware identificados, sin SLA inventado.

Relacionadas: [productos](productos-crediticios.md), [auditoría](auditoria-eventos.md),
[contraste de auditoría](../explanation/contraste-auditoria-core-crediticio-2026-10.md).

## Amortización reutilizable

Preparación arquitectónica integrada mediante [PR #62](https://github.com/jata-imk/kronik/pull/62)
en `15bdc8d` el 31/10/2026 (hora de México), código `c5b8422` y head revisado
`f48d739`. El árbol integrado coincide con el head aprobado. Sin despliegue.
[ADR 0034](../explanation/adr-0034-amortizacion-reutilizable.md).
`EstrategiaAmortizacion` separa preparación de base y distribución de principal.
`CuotaNivelada` y `CapitalFijo` se resuelven sin estado por método; tabla y ambos
modos de anticipo usan estas decisiones comunes.

`CalculadorAmortizacion::calcular(CondicionesAmortizacion)` recibe principal,
tasa porcentual, inicio, fechas, método, motor y convención. Devuelve
`ResultadoAmortizacion` con filas y base contractual inmutables. No conoce
créditos, líneas, actores, equipos, Eloquent ni auditoría. No crea calendarios,
no decide cuándo son exigibles los intereses ni cambia la base de devengo.

El adaptador `TablaAmortizacionCreditoSimple` conserva firma, arrays y errores
en `metodo`. Anticipos conserva errores en `pago`, la recuperación histórica de
`cuota_base`, el límite del calendario y su serialización. Reducir plazo conserva
base, limita la última amortización al saldo y rechaza un calendario insuficiente.
Los intereses históricos de tabla y de reducción de plazo mantienen sus órdenes
de operaciones originales. No se agrega versión de motor ni formato.

Pruebas sin Laravel muestran un simple y una disposición equivalente, además de
dos disposiciones simuladas con métodos distintos sin contaminar resultados.
No acredita Revolvente operativo ni su validación de combinaciones. El catálogo
de capacidades está integrado mediante PR #64; la revisión de la cartera de prueba
fue confirmada por el usuario el 04/11/2026. Bullet y exigibilidad independiente
conservan sus tareas pendientes.

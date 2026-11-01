# Bullet: casos para cerrar la definición funcional

Estado: contraste con código revisado el 01/11/2026 y propuesta numérica del 29/10.
No es una política completa de producto aprobada ni acredita implementación Bullet. Fuente de alcance:
[subtarea funcional](https://app.notion.com/p/40861db7db7f81d1ab37cc46ea320c3c).
La separación sigue [ADR 0032](../explanation/adr-0032-core-crediticio-extensible.md).

## Contraste con el proyecto y preguntas que siguen abiertas

Revisión del 01/11/2026 tras la aclaración del usuario. Sustituye el cuestionario
amplio del 31/10: no se vuelve a consultar lo que ya está decidido o implementado.
Esta entrega solo documenta evidencia y pendientes; no modifica reglas del motor.

El usuario crea y nombra sus productos, configurando las opciones soportadas por
la interfaz/API. El motor valida compatibilidad y conserva la versión contractual.
No hace falta pedir a contadores un catálogo de productos o autorización por nombre
comercial. El nombre no selecciona una fórmula: Bullet y su política de intereses
serán opciones explícitas cuando se implementen. Las dos políticas son alcance
aprobado. El usuario confirmó permitir anticipos conservando vencimiento; reducir
plazo también está incluido, pero su mecanismo sigue pendiente.

### Qué ya responde el código

| Tema | Regla existente que se conserva | Evidencia |
| --- | --- | --- |
| Configuración | Montos, tasas, plazos, métodos, comisiones, fiscalidad y permisos de prepago se configuran por versión de producto. Los nombres comerciales son libres. | `ProductoVersionService`, `CatalogoCapacidadesCredito`, `GuardarProductoRequest`. |
| Calendario | Semanal +7 días, quincenal +15 días y mensual con ancla y fin de mes; sin ajuste por inhábiles. Los días/centavos se prueban con datos sintéticos de tasa fija y Act/360. | `CalendarioCreditoSimple`, `TablaAmortizacionCreditoSimpleTest`. |
| Pago ordinario/parcial | Cuota más antigua primero; dentro de cada cuota: comisión, moratorio, ordinario y capital. Cada abono se reparte entre el concepto y su impuesto proporcionalmente. No existe una bolsa global de impuestos que se liquide primero. | `MotorPagos::resumen/aplicar`, `RepartoProporcional`, ADR 0012 y pruebas homónimas. |
| Anticipo actual | Se captura el total recibido; primero vencidos, devengos y cargos aplicables, luego capital futuro. El mínimo aplica al capital anticipado; el saldo reducido determina los intereses futuros desde el día siguiente. | `MotorAnticipos::calcular`, ADR 0023, `MotorAnticiposTest`. |
| Liquidación | Cubre deuda devengada, capital pendiente y cargos/impuestos aplicables, sin convertir interés futuro proyectado en deuda. Coincidencia exacta con previa; un cambio de captura/estado invalida su hash. | `MotorAnticipos`, `PagoService::previa/registrar`, `MotorAnticiposTest`. |
| Mora actual | Base de capital vencido; gracia efectiva o retroactiva y ambos intereses o sustitución se configuran en contrato. Existe un límite explícito a parcialidades durante gracia B con sustitución. | `MotorPagos::avanzar/aplicar`, `PoliticaMora`, ADR 0012 y decisiones fiscales/contractuales. |
| Comisiones/fiscalidad | Eventos y bases de comisión explícitos; tratamiento y tasa/base de impuesto por concepto. La institución configura lo que corresponda. No se abre una nueva definición fiscal universal para Bullet ni se incorpora CFDI/contabilización en este incremento. | `MotorAnticipos::comisiones`, `ImpuestoConceptoService`, ADR 0023. |
| Correcciones | Solo último movimiento admisible, sin posteriores; reversos compensatorios con historia/recibos conservados. Fechas entre desembolso y hoy, sin insertar pagos entre movimientos posteriores ni anticipos automáticos por excedentes. | `PagoService::reversar/validarFecha`, `MotorPagos::aplicar`, ADR 0023. |

Este contraste demuestra las reglas de los métodos actuales, no Bullet operativo.
La separación entre devengo, exigibilidad y principal necesita adaptación técnica
para pagos periódicos de solo interés. Esa adaptación no reabre las reglas comunes.

### Fechas de prueba y trabajo técnico

Nosotros elegimos fechas e importes sintéticos para verificar los resultados; no
necesitamos créditos reales ni tasas variables para diseñar/probar Bullet fijo.
Los ejemplos de abajo usan 24 % fijo y Act/360. Probaremos fin de mes, bisiesto,
parcialidades y periodos incompletos contra expectativas independientes.

La captura de duración/vencimiento y periodicidad de intereses se precisará al
planificar la interfaz, reutilizando el calendario vigente. No se introduce un
selector de calendarios arbitrarios ni se pide a contadores elegir fechas de QA.
Una necesidad de calendario distinta debe declararse como ampliación explícita.

### Tres consultas funcionales pendientes

1. **Anticipo Bullet y devengado aún no exigible.** Permitirlo y reducir principal
   para recalcular interés futuro ya está definido. La consulta es: ¿el anticipo
   cobra también el interés acumulado hasta ese día, como hace el motor actual,
   aunque su vencimiento contractual sea posterior, o el dinero se aplica solo a
   capital y el interés devengado conserva su vencimiento? Caso sin cargos/impuestos:
   al 16/01 hay 120 de interés acumulado. Con 3120 recibidos, el recorrido actual
   aplica 120 a interés y 3000 a capital, quedando 9000. Una alternativa de solo
   capital dejaría 8880 de principal y los 120 pendientes para su fecha contractual.
   No se adopta esa alternativa sin aprobación; no se borra interés ya generado.
2. **Reducción de plazo.** En Bullet no hay una amortización periódica de capital
   que permita eliminar cuotas al conservar su importe, como en capital fijo o
   cuota nivelada. Tras el anticipo, pueden quedar 9000 de capital con vencimiento
   01/03: el saldo menor por sí solo no determina si vence el 01/02, 15/02 u otra
   fecha. ¿Qué regla establece el nuevo vencimiento? ¿Quién lo elige y con qué
   límites? Explicar efecto sobre el siguiente interés y periodo final incompleto.
3. **Interés vencido antes de vencer el capital.** Las opciones de tasa/gracia y
   coexistencia ya existen. Falta definir si el impago de interés Bullet genera
   únicamente atraso de esa obligación mientras la base de mora sigue siendo
   capital vencido (todavía cero), o si se necesita otra base explícita. Caso:
   vencen 248 de interés el 01/02, se pagan 100 y quedan 148; el principal de 12000
   vence el 01/03. ¿Qué sucede desde el 02/02? No aplicar mora a principal aún no
   vencido ni capitalizar el interés por inferencia. Si se solicita otra base,
   precisar fórmula, inicio/gracia y efecto sin alterar contratos anteriores.

### Respuesta necesaria para cada consulta

Entregar regla, ejemplo con fechas/importes y saldo posterior. Indicar quién
valida y si una alternativa entra o se difiere. Para reducción de plazo, los
contadores o responsables funcionales deben proporcionar el criterio para obtener
la nueva fecha; nosotros construimos los casos que lo prueben. Lo que siga pendiente
bloquea únicamente las operaciones que dependan de esa decisión.

No se solicitan nuevamente configuración de productos, orden de pagos, fiscalidad
genérica, liquidación ni límites de reverso. Las pruebas de esos comportamientos
se mantienen. Bullet, Flat, tasas variables, capitalización, calendarios nuevos,
Revolvente y Nómina no se habilitan por revisar este documento.


## Datos sintéticos de referencia

Principal 12000 MXN, tasa fija 24 % anual, días reales/360. Disposición 01/01/2027,
vencimiento 01/03/2027. Para intereses periódicos: vencimientos 01/02 y 01/03.
Sin comisiones ni impuestos exclusivamente para aislar la aritmética del ejemplo;
no prescribe tratamiento fiscal para productos reales. Gracia/mora deben definirse por
separado. Las cifras se obtienen por las operaciones explícitas siguientes, sin
copiar resultados del motor ni de los PDF de referencia.

## Flujo normal

El principal genera 8 MXN diarios: `12000 × 24 / 36000`. Enero aporta 31 días
(248 MXN), febrero de 2027 aporta 28 (224 MXN). Interés total: 472 MXN.

| Política | Fecha | Capital exigible | Interés exigible | Pago total |
| --- | --- | --- | --- | --- |
| Periódicos | 01/02/2027 | 0.00 | 248.00 | 248.00 |
| Periódicos | 01/03/2027 | 12000.00 | 224.00 | 12224.00 |
| Al vencimiento | 01/03/2027 | 12000.00 | 472.00 | 12472.00 |

En ambas políticas el principal pendiente hasta su pago es 12000, no 12472.
Antes de vencer, el interés puede estar devengado sin ser exigible. El pago final
de capital e interés del último periodo no constituye otra modalidad mixta.

## Liquidación temprana

Al 16/01 transcurrieron 15 días: `12000 × 24 × 15 / 36000 = 120`. Si se admite
liquidación y no hay cargos/impuestos del escenario, la cotización es 12120.
El interés futuro proyectado no integra el principal ni la liquidación. Se conserva
el tratamiento del motor actual; este ejemplo será una prueba de regresión al
adaptar Bullet, sin solicitar nuevamente una decisión genérica de liquidación.

## Anticipo parcial: propuesta pendiente de aceptación

Decisión de José del 29/10/2026: **permitir también reducir plazo, definiendo esa
regla**. Queda incluida la capacidad; no está aprobado todavía el mecanismo para
determinar el nuevo vencimiento. Bullet no tiene una cuota de capital periódica
que permita reutilizar sin más la reducción de plazo de cuota nivelada.

Propuesta A: conservar vencimiento final y cobrar primero devengado conforme al
recorrido actual de anticipos. El 16/01 se reciben 3120: 120 de interés y 3000 de
capital. Quedan 9000; generan 6 diarios por los 44 días restantes: 264.

| Política | Flujo posterior propuesto |
| --- | --- |
| Periódicos | 96 de intereses el 01/02 y 9168 de capital/interés el 01/03 |
| Al vencimiento | 9264 de capital/interés el 01/03 |

El costo total del escenario sería 12384, incluidos los 3120 iniciales. El usuario
confirmó conservar vencimiento como capacidad; esta propuesta numérica presupone
cobrar el devengado durante el anticipo, punto específico todavía por revisar.
Reducir plazo también está incluido: falta determinar la nueva fecha mediante
una regla propia, sin trasladar automáticamente la de cuota nivelada.

## Interés vencido con capital todavía no vencido

En la política periódica, si el 01/02 solo se reciben 100, quedan 148 de interés
sin pagar y 12000 de principal no vencido. El seguimiento debe poder expresar
ambos hechos. No sumar esos 148 al principal. Definir expresamente:

- Qué días de atraso se muestran y qué obligación determina el indicador.
- Base de mora en esta situación nueva: conservar solo capital vencido (cero
  hasta el 01/03) o definir expresamente otra base. Las opciones de gracia y
  coexistencia ya existentes no se vuelven a someter a definición genérica.
- Adaptar técnicamente la aplicación de pagos conservando el orden existente,
  sin capitalización ni nuevas prioridades fiscales.

Decisión de José del 29/10/2026: **base y tratamiento de mora pendientes de revisión
funcional**. No seleccionar una alternativa por defecto ni habilitar operación
dependiente de esta decisión. El indicador de interés vencido y la prohibición
de capitalización implícita siguen formando parte del alcance.

## Trabajo técnico y regresión

- Planificar captura de duración/vencimiento y periodicidad de interés separadas
  de la amortización; reutilizar calendario actual y generar casos sintéticos.
- Conservar anclas, días naturales y ausencia de ajuste por inhábiles. No
  habilitar calendarios arbitrarios, catorcenal o quincena calendario por defecto.
- Reverso: mantener límites vigentes del último movimiento admisible. La
  restauración conserva recibos/cronogramas anteriores y nuevas versiones cuando
  corresponda; no editar ni borrar historia.
- Ampliar casos con conceptos/impuestos configurados de forma explícita,
  referencia duplicada, previa obsoleta, concurrencia y reverso de liquidación.

## Aceptación pendiente

- [x] Confirmar configuración libre del usuario y anticipos conservando vencimiento como capacidad.
- [ ] Planificar calendario/UI y preparar resultados independientes con fechas sintéticas.
- [ ] Resolver tratamiento del devengado no exigible durante el anticipo Bullet.
- [ ] Definir cómo se obtiene la nueva fecha al reducir plazo.
- [ ] Resolver atraso/mora de intereses sin capital vencido.
- [ ] Cubrir en regresión liquidación, fiscalidad, orden de pagos y reversos existentes al adaptar Bullet.
- [ ] Registrar aceptación funcional antes de implementar operaciones dependientes.

Relacionadas: [contratos del core](core-crediticio.md) y
[productos vigentes](productos-crediticios.md). Las cifras de esta ficha no son
condiciones comerciales ni determinación fiscal.

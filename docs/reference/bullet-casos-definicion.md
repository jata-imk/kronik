# Bullet: casos para cerrar la definición funcional

Estado: propuesta numérica para revisión, 29/10/2026. No es una política de
producto aprobada ni acredita implementación Bullet. Fuente de alcance:
[subtarea funcional](https://app.notion.com/p/40861db7db7f81d1ab37cc46ea320c3c).
La separación sigue [ADR 0032](../explanation/adr-0032-core-crediticio-extensible.md).

## Revisión con contadores: qué está decidido y qué deben responder

Preparación solicitada por José el 31/10/2026, después de integrar el catálogo
backend/Vue (PR #64). Esta revisión no habilita Bullet ni aprueba los escenarios.
El objetivo es que negocio y contabilidad definan qué entra en la primera entrega,
qué se rechaza y qué se difiere. Una regla contractual o de cumplimiento puede
requerir además revisión del responsable de producto o jurídico correspondiente.

Ya está aprobado como alcance del sistema:

- Capital al vencimiento, con dos políticas: intereses periódicos o al vencimiento.
  Cada versión del producto deberá autorizar la política correspondiente.
- Anticipos con reducción de plazo incluidos, todavía sin mecanismo aprobado.
- Separar principal, interés devengado, exigible, vencido, pagado y proyectado;
  comisiones e impuestos por concepto. Sin capitalización implícita.
- Reutilizar el core y conservar condiciones versionadas e historia. La base
  técnica actual es MXN, tasa fija y Act/360; no se han habilitado reglas Bullet.

Ya hay ejemplos independientes de flujo normal, liquidación temprana y una
propuesta de anticipo con vencimiento conservado, desarrollados abajo. Falta la
aceptación funcional y completar resultados de atraso, reversos y fiscalidad.

| Tema | Pregunta para decidir qué entra | Caso que deben devolver resuelto |
| --- | --- | --- |
| Producto y políticas | ¿Qué productos ofrecerán intereses periódicos, al vencimiento o ambas alternativas? ¿Qué límites de monto, tasa y duración aplican? Confirmar tasa fija/Act/360 para la primera entrega. | Identificar producto y política; confirmar el flujo normal de 12000 al 24 % del ejemplo. Las dos políticas son alcance del sistema, no una obligación de ofrecerlas juntas en todos los productos. |
| Calendario | ¿Cómo se fija la primera fecha y el vencimiento final? ¿Qué frecuencias actuales se usarán? ¿Se aceptan periodos incompletos? ¿Se mueve el cobro en inhábiles y cambia también el periodo de interés? | Disposición en fin de mes, febrero bisiesto y periodo final incompleto: fechas exactas, días y cantidades. Distinguir cada 15 días de quincena calendario; las frecuencias nuevas requieren su propio incremento. |
| Pagos parciales | ¿Cuál es el orden entre impuestos, comisiones, interés vencido, interés devengado aún no exigible y principal? ¿Se exige pagar devengado para abonar a capital? | Pago de 100 el 01/02 cuando vencen 248 de interés: importe aplicado a cada concepto y saldo posterior. Indicar si queda remanente y cómo se trata. |
| Anticipo conservando vencimiento | ¿Se permite? ¿Un abono anunciado de 3000 significa capital neto o dinero total recibido? ¿Cuándo se paga el interés devengado y cómo se presenta en el recibo? | Aceptar o corregir el ejemplo del 16/01: recibido 3120, interés 120, capital 3000, saldo 9000 y nuevo flujo hasta el 01/03. |
| Anticipo reduciendo plazo | ¿Quién determina la nueva fecha, con qué fórmula o regla y qué límites/autorización? ¿Cómo cambia el siguiente pago periódico y el periodo final incompleto? | Con 9000 de principal pendiente después del anticipo, dar una nueva fecha concreta, explicar cómo se obtuvo y detallar el flujo restante para ambas políticas. No existe una cuota periódica de capital que determine por sí sola esa fecha. |
| Atraso y mora | Si solo vence interés, ¿qué obligación genera atraso y sobre qué base se calcula mora, a qué tasa y desde qué día? ¿Hay gracia? ¿El ordinario continúa, se sustituye o coexiste con moratorio? ¿Qué cambia al vencer el principal? | Resolver desde el 02/02 los 148 de interés pendientes con 12000 de principal todavía no vencido, y resolver por separado el impago del vencimiento final. Mostrar conceptos y bases sin sumarlos al principal. |
| Liquidación anticipada | ¿Se permite en ambas políticas? ¿Qué devengado, vencido, comisiones e impuestos se cobran? ¿Se elimina todo el interés futuro proyectado? ¿Hasta qué fecha es válida la cotización? | Aceptar o corregir la cotización aritmética de 12120 al 16/01, sin cargos/impuestos del ejemplo; resolver también una liquidación después de un pago parcial o con interés vencido. |
| Comisiones y fiscalidad | Para esta entidad y producto, ¿qué tratamiento, tasa, base, momento de reconocimiento/cobro y comprobante corresponde a cada concepto? ¿Qué cargos aplican a anticipo, atraso y liquidación? | Una tabla por concepto con fundamento o referencia aprobada y un recibo completo. No asumir IVA universal, exención ni tasa cero a partir del ejemplo sin impuestos. |
| Correcciones y reversos | ¿Aceptan los límites actuales de fecha y reverso del último movimiento admisible? ¿Qué evidencia y autorización requiere una corrección? | Revertir un anticipo y una liquidación: saldos, obligaciones y calendario que se restauran. Si necesitan corregir movimientos con operaciones posteriores, declararlo como ampliación, no asumir que ya se permite. |

### Formato de respuesta solicitado

Para cada tema, responder **entra / no entra / se difiere**, por producto y
política. Si entra, entregar regla exacta, condiciones/límites, ejemplo con fechas
y desglose de capital, ordinario, moratorio, comisiones, impuestos y total. Indicar
saldo posterior, próximo vencimiento, responsable que valida y fecha de aceptación.
Si se difiere, indicar qué operación debe bloquearse mientras no exista regla.

Para lo fiscal/contable, identificar entidad y producto aplicables y conservar la
referencia de la decisión; una opinión sobre un ejemplo no aprueba todas las
operaciones ni sustituye la validación contractual correspondiente.

### Qué falta antes de implementar

1. Recibir y conciliar estas respuestas, especialmente reducción de plazo y mora.
2. Completar los casos numéricos de ambas políticas, incluidos atraso, pagos
   parciales, anticipo, liquidación y reverso con conceptos e impuestos separados.
3. Registrar aceptación funcional y alcance de la primera entrega en Notion.
4. Planificar desde esos casos la separación de obligaciones/exigibilidad, campos,
   validaciones y UI. Recepción, conciliación y aplicación conservan sus límites.

Ninguna fila queda aceptada por la sola preparación de este cuestionario. Flat,
tasas variables, capitalización, calendarios arbitrarios, Revolvente y Nómina
permanecen fuera de esta preparación.

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
El interés futuro proyectado no integra el principal ni la liquidación. Confirmar
que la política de liquidación conserva este tratamiento antes de implementarlo.

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

El costo total del escenario sería 12384, incluidos los 3120 iniciales. Esta
propuesta no autoriza por sí misma conservar fecha ni reducir plazo. Si se desea
reducir plazo, definir cómo se determina el nuevo vencimiento y qué opciones se
permiten; no trasladar automáticamente la regla de cuota nivelada.

## Interés vencido con capital todavía no vencido

En la política periódica, si el 01/02 solo se reciben 100, quedan 148 de interés
sin pagar y 12000 de principal no vencido. El seguimiento debe poder expresar
ambos hechos. No sumar esos 148 al principal. Definir expresamente:

- Qué días de atraso se muestran y qué obligación determina el indicador.
- Base, tolerancia y política de mora: conservar mora solo sobre capital vencido
  o autorizar otra regla validada. No elegirla implícitamente.
- Qué ocurre al pagar después y cómo se distribuye el importe por concepto.

Decisión de José del 29/10/2026: **base y tratamiento de mora pendientes de revisión
funcional**. No seleccionar una alternativa por defecto ni habilitar operación
dependiente de esta decisión. El indicador de interés vencido y la prohibición
de capitalización implícita siguen formando parte del alcance.

## Calendario y correcciones por cerrar

- Definir primera fecha de interés y vencimiento final/duración, no solo número
  de cuotas. Precisar si el último periodo incompleto se admite y cómo se presenta.
- Acordar inhábiles, anclas de meses cortos/bisiestos y significado de cada 15 días
  frente a quincena calendario. No habilitar calendarios arbitrarios por defecto.
- Reverso: mantener límites vigentes del último movimiento admisible. La
  restauración conserva recibos/cronogramas anteriores y nuevas versiones cuando
  corresponda; no editar ni borrar historia.
- Ampliar casos con conceptos/impuestos configurados de forma explícita,
  referencia duplicada, previa obsoleta, concurrencia y reverso de liquidación.

## Aceptación pendiente

- [ ] Acordar calendario de la primera entrega y resultados por fecha.
- [ ] Resolver anticipo parcial y su efecto en vencimiento e intereses futuros.
- [ ] Resolver atraso/mora de intereses sin capital vencido.
- [ ] Confirmar liquidación, correcciones y límites actuales que se conservan.
- [ ] Registrar aceptación funcional antes de implementar operaciones dependientes.

Relacionadas: [contratos del core](core-crediticio.md) y
[productos vigentes](productos-crediticios.md). Las cifras de esta ficha no son
condiciones comerciales ni determinación fiscal.

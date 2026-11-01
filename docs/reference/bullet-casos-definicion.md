# Bullet: casos para cerrar la definición funcional

Estado: propuesta numérica para revisión, 29/10/2026. No es una política de
producto aprobada ni acredita implementación Bullet. Fuente de alcance:
[subtarea funcional](https://app.notion.com/p/40861db7db7f81d1ab37cc46ea320c3c).
La separación sigue [ADR 0032](../explanation/adr-0032-core-crediticio-extensible.md).

## Datos sintéticos de referencia

Principal 12000 MXN, tasa fija 24 % anual, días reales/360. Disposición 01/01/2027,
vencimiento 01/03/2027. Para intereses periódicos: vencimientos 01/02 y 01/03.
Sin comisiones y con tratamiento `no_causa` exclusivamente como fixture aritmético;
no prescribe fiscalidad para productos reales. Gracia/mora deben definirse por
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

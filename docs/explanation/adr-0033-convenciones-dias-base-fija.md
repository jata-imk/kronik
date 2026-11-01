# ADR 0033: punto de extensión para convenciones de días de base fija

- Estado: aceptado; Act/360 es la única convención habilitada.
- Fecha: 2026-10-31.
- Complementa [ADR 0032](adr-0032-core-crediticio-extensible.md) y
  [ADR 0006](adr-0006-versionado-productos-y-motor-calculo.md).
- Alcance y seguimiento: [tarea de arquitectura en Notion](https://app.notion.com/p/40961db7db7f81c7a4fdc08b65f271ee).

## Contexto

El usuario solicita preparar la extensión a financieras que utilicen Act/365 Fixed
o una variante de 30/360. La versión del producto ya conserva
`reglas.convencion_interes = dias_reales_360`; duplicar esa selección en otro campo
o convertirla en una preferencia global podría crear condiciones contradictorias.
El motor 2 corrige precisión Act/360 y conserva numeradores; el motor histórico
debe seguir reproduciendo sus resultados.

## Decisión

1. Reutilizar el campo existente como condición contractual versionada.
   `ConvencionInteres` enumera exclusivamente convenciones habilitadas. Marcas
   desconocidas o explícitamente nulas se rechazan en español. La ausencia de
   marca se interpreta como Act/360, sin escribir sobre el original.
2. Separar `ConvencionDias`, contrato de conteo por fechas y denominador anual
   fijo, de `InteresPorPeriodo`, que recibe capital y tasa porcentual. La primera
   implementación es `DiasReales360`, resuelta por `ConvencionesDias`. Los
   componentes son puros y no consultan productos, preferencias o Eloquent.
3. Tabla, devengo y anticipos del motor 2 usan fechas de inicio/fin y selección
   explícita. Se conserva el numerador completo antes de dividir por
   `100 × baseAnual`; la amortización aporta el capital, y exigibilidad/mora siguen
   siendo responsabilidades distintas.
4. Los cronogramas nuevos explicitan `convencion_interes`. Producto y cronograma
   se validan antes de iniciar pagos o calcular anticipos. La simulación consume
   la convención del snapshot cuando existe; no la sustituye por reglas mutables.
5. Mantener algoritmo y formato actuales: la refactorización no cambia cifras
   Act/360 ni acumuladores ya generados. El motor 1 conserva sus fórmulas previas.
   Copiar un producto conserva su convención; crear uno sin selección conserva
   el valor predeterminado actual. No se habilita un selector nuevo en la UI.
6. `VersionMotorCredito::validarConvencion` mantiene una lista explícita de
   compatibilidad: motores 1 y 2 admiten únicamente Act/360. Añadir una estrategia
   o un caso al catálogo no amplía esa lista automáticamente.

## Consecuencias y límites

El contrato actual sirve a convenciones de denominador fijo. No pretende cubrir
Act/Act ni reglas que necesiten una base variable por subperiodo. Ampliarlo
requiere una decisión propia y evidencia compatible con sus acumuladores.

Act/365 Fixed y las variantes 30/360 aún no se implementan ni se ofrecen en el
catálogo operativo. La próxima habilitación debe definir variante, fechas
incluidas/excluidas, febrero, fin de mes, vencimiento y periodos incompletos, así
como conservar el contexto contractual que necesite la estrategia.

En particular, el avance diario actual solo está validado para Act/360. En 30/360,
sumar conteos de días de segmentos arbitrarios puede diferir del conteo del
periodo completo. No basta añadir un caso al enum/resolver: revisar segmentación
por pagos, cambios de capital, gracia, mora y reanudación de devengos. También se
debe definir compatibilidad por versión de motor; el nombre `Act360Racional` no
autoriza utilizar el motor 2 con otras convenciones.

Para cada convención futura, probar resultados independientes y equivalencia
entre tabla y deuda real, anticipos/liquidación, impuestos, redondeo y reversos.
Las reglas regulatorias del CAT mantienen su estrategia independiente.

No se añaden migraciones, configuración global, nuevas acciones auditables ni
eventos por cada cálculo. Los eventos de activación y pago conservan sus emisores
y metadatos; las pruebas verifican ausencia de activaciones ante entradas inválidas
y preservación de actor/contexto/idempotencia en operaciones aceptadas.

## Referencias

- [Contrato canónico del core](../reference/core-crediticio.md#convenciones-de-días).
- [Documentación primaria de convenciones de OpenGamma Strata](https://strata.opengamma.io/day_counts/),
  consultada el 31/10/2026: Act/360, Act/365 Fixed y distintas reglas 30/360.
  Se usa para distinguir conceptos; no implica adoptar su biblioteca ni habilitar
  una variante contractual.

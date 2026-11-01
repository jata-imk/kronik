# ADR 0035: catálogo de capacidades actuales del crédito

- Estado: aceptado; implementación local, pendiente de integración.
- Fecha: 2026-10-31.
- Complementa [ADR 0032](adr-0032-core-crediticio-extensible.md), [ADR 0033](adr-0033-convenciones-dias-base-fija.md) y [ADR 0034](adr-0034-amortizacion-reutilizable.md).

## Contexto

Productos y Solicitudes duplicaban métodos, periodicidades, etiquetas y listas de
validación. Una capacidad soportada puede ser incompatible con un motor o no estar
autorizada por una versión contractual.

## Decisión

`CatalogoCapacidadesCredito` declara descriptores y compatibilidad explícita por
motor. La guarda y el catálogo comparten `VersionMotorCredito::admiteConvencion`.
Añadir un descriptor no habilita automáticamente un algoritmo histórico.
`CapacidadesProducto` usa el snapshot existente y las relaciones para borradores
sin snapshot. Valida antes de congelar condiciones y presenta la intersección
contractual sin escribir sobre la evidencia.

Vue recibe catálogo, configuración actual y opciones por versión. La captura
incompleta sigue permitida; simular/enviar valida condiciones efectivas.
Las incidencias históricas permiten consultar y bloquean cálculo sin sustituir
códigos desconocidos por otra capacidad.

## Consecuencias y límites

No cambia algoritmo, formato financiero, snapshot ni hash. Ausencia histórica de
motor significa motor 1; ausencia de convención significa Act/360. Nulos explícitos
y códigos desconocidos se rechazan. No hay endpoint, administrador, migración,
seeder ni variable nueva. Solo opciones actuales; Bullet, Revolvente, Nómina,
Flat y otras convenciones permanecen pendientes. Amortización, interés,
exigibilidad, recepción y aplicación conservan responsabilidades separadas.

Resolver o consultar capacidades no genera actividad. Los eventos de negocio
permanecen en aplicación. Contrato en la [referencia del core](../reference/core-crediticio.md#catálogo-de-capacidades).

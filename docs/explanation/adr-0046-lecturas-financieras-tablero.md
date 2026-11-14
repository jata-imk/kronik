# ADR 0046: lecturas financieras derivadas del core

- Fecha: 2026-11-13.
- Estado: aceptado; implementación local para revisión, sin integración ni despliegue.

## Contexto

El tablero requiere saldos, atrasos y programación sin descifrar y recalcular
toda la cartera por cada visita. El motor ya conserva evidencia suficiente para
crédito simple y ambas políticas Bullet; las tablas de movimientos no equivalen
al saldo exigible al día y sus reversos genéricos pierden el tipo original.

El usuario eligió proyecciones reconstruibles y confirmación atómica con la
operación. Aprobó hoy/cierres mensuales, historia corregida por fecha efectiva,
atraso contractual distinguido de gracia y diseño dentro del sistema Kronik.

## Decisión

Mantener el monolito modular y el motor actual. Un lector compartido selecciona
evidencia y un proyector traduce resultados a dos tablas: resumen por crédito/corte
y obligaciones agrupadas por fecha/naturaleza. Conservar principal, ordinario,
moratorio, comisiones e impuestos separados, junto con cursor y huellas de origen.

`ProyeccionCreditoService` concentra escrituras, reconstrucción e invalidación.
Desembolsos y pagos lo ejecutan dentro de su transacción y bloqueo actuales.
La falla real del derivado revierte la operación y su actividad. Los contratos
sin política de mora conservan la compatibilidad de desembolso existente y una
lectura explícitamente no disponible; no se les asignan políticas nuevas.

El avance programado usa checkpoints cifrados verificados, sin contabilizar
devengos. La reconstrucción desde originales contrasta resumen y obligaciones.
Una lectura incompleta no acredita saldo cero ni vigencia. Las operaciones y
previas no usan proyecciones como fuente de autoridad.

Los cierres mensuales de lectura son corregibles y reconstruibles. No sustituyen
la entrega separada de cortes regulatorios inmutables ni cierres contables.

El tablero consulta SQL autorizado. Vue compone componentes y lógica reactiva;
no calcula obligaciones ni sustituye la autorización backend. La actualización
de proyecciones pasa a ser regla obligatoria del repositorio.

## Consecuencias

Dos tablas nuevas, un índice temporal de pagos y mantenimiento del programador.
El costo del motor se paga al actualizar/reconstruir, con medición por volumen;
la visita al tablero usa agregaciones y detalles paginados. El paso del día puede
dejar un corte pendiente hasta terminar su preparación, siempre identificado.

No se introducen reglas financieras, productos futuros, contabilidad, SIC ni
operación real. Consultar y calcular derivados no emiten eventos duplicados de
negocio; la reconstrucción administrativa registra su resultado global, como
sistema y sin inferir un usuario iniciador desde una sesión mutable de consola.

Contrato canónico: [tablero financiero](../reference/tablero-financiero.md).
Evidencia y estado: [Agent Note](../agent-notes/tablero-propuesta-visual.md).
